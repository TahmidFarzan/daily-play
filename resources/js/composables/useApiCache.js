import { toRaw, unref } from 'vue'

const memoryCache = new Map()
const pendingRequests = new Map()

const normalizeAppName = (value) => {
    return String(value ?? 'app')
        .toLowerCase()
        .trim()
        .replace(/[\s_]+/g, '-')
        .replace(/[^a-z0-9-]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '') || 'app'
}

export const apiCacheVersion = 'v2'
export const apiCachePrefix = `${normalizeAppName(import.meta.env.VITE_APP_NAME)}-front-end-${apiCacheVersion}`

export const apiCacheTTL = Object.freeze({
    DEFAULT: 60,
    SYSTEM_SHORT: 60,
    SYSTEM_MEDIUM: 180,
    SYSTEM_LONG: 1800,

    API_USER: 30,
    API_PLAYER: 300,

    MODEL_CURSOR_PAGINATION: 30,
    MODEL_PAGINATION: 30,
})

export const apiCacheKey = Object.freeze({
    DEFAULT: "api:get",

    API_MULTI_SELECT: "api:multiselect",
    API_TINYMCE: "api:tinymce",

    API_USER: "api:user",
    API_PLAYER: "api:player",

    MODEL_CURSOR_PAGINATION: "model-cursor-pagination",
    MODEL_PAGINATION: "model-pagination",

})

const cacheEnabled = import.meta.env.VITE_APP_CACHE_ENABLED !== 'false'

const hasWindow = () => typeof window !== 'undefined'

const canUseLocalStorage = () => {
    if (!hasWindow()) return false

    try {
        const testKey = `${apiCachePrefix}:storage_test`
        window.localStorage.setItem(testKey, '1')
        window.localStorage.removeItem(testKey)
        return true
    } catch {
        return false
    }
}

const storageAvailable = canUseLocalStorage()

const safeParse = (value) => {
    if (!value) return null

    try {
        return JSON.parse(value)
    } catch {
        return null
    }
}

const safeStringify = (value) => {
    try {
        return JSON.stringify(value)
    } catch {
        return null
    }
}

const getDefaultTtl = () => {
    const envTimeout = Number(import.meta.env.VITE_APP_CACHE_TIMEOUT)

    if (Number.isFinite(envTimeout) && envTimeout > 0) {
        return envTimeout > 1000
            ? Math.max(1, Math.floor(envTimeout / 1000))
            : Math.max(1, Math.floor(envTimeout))
    }

    return apiCacheTTL.DEFAULT
}

const resolveTtl = (ttl) => {
    const seconds = Number(ttl ?? getDefaultTtl())
    return Number.isFinite(seconds) && seconds > 0 ? seconds : getDefaultTtl()
}

const normalizeNamespace = (namespace = apiCachePrefix) => {
    return String(namespace || apiCachePrefix).replace(/:+$/g, '')
}

const resolveReactiveValue = (value) => {
    const unwrapped = typeof value === 'function' ? value() : unref(value)
    const rawValue = toRaw(unwrapped)

    if (Array.isArray(rawValue)) {
        return rawValue.map(resolveReactiveValue)
    }

    if (rawValue && typeof rawValue === 'object') {
        return Object.keys(rawValue).sort().reduce((sorted, key) => {
            const childValue = rawValue[key]

            if (typeof childValue !== 'function') {
                sorted[key] = resolveReactiveValue(childValue)
            }

            return sorted
        }, {})
    }

    return rawValue
}

const normalizeKey = (key) => {
    if (typeof key === 'string') return key

    const serialized = safeStringify(resolveReactiveValue(key))
    return serialized || String(key)
}

const makeKey = (key, namespace = apiCachePrefix) => {
    const cacheNamespace = normalizeNamespace(namespace)
    const rawKey = normalizeKey(key).replace(/^:+/g, '')

    return `${cacheNamespace}:${rawKey}`
}

const buildApiKey = (identity = apiCacheKey.DEFAULT, request = {}) => {
    const requestSnapshot = resolveReactiveValue(request)
    const serializedRequest = safeStringify(requestSnapshot) || ''

    return `${normalizeKey(identity)}:${serializedRequest}`
}

const makeApiKey = (identity = apiCacheKey.DEFAULT, request = {}, namespace = apiCachePrefix, options = {}) => {
    void options

    return makeKey(buildApiKey(identity, request), namespace)
}

const isValidEntry = (entry) => {
    return Boolean(
        entry &&
        Object.prototype.hasOwnProperty.call(entry, 'value') &&
        entry.version === apiCacheVersion &&
        Number.isFinite(entry.expiresAt) &&
        Number.isFinite(entry.createdAt)
    )
}

const isExpired = (entry) => {
    return !isValidEntry(entry) || Date.now() > entry.expiresAt
}

const readFromStorage = (storageKey) => {
    if (!storageAvailable) return null

    const stored = window.localStorage.getItem(storageKey)
    const parsed = safeParse(stored)

    if (!parsed && stored) {
        window.localStorage.removeItem(storageKey)
    }

    return parsed
}

const writeToStorage = (storageKey, entry) => {
    if (!storageAvailable) return

    const serialized = safeStringify(entry)
    if (!serialized) return

    try {
        window.localStorage.setItem(storageKey, serialized)
    } catch {
        window.localStorage.removeItem(storageKey)
    }
}

const removeFromStorage = (storageKey) => {
    if (!storageAvailable) return

    window.localStorage.removeItem(storageKey)
}

const buildEntry = (value, ttlSeconds) => {
    const now = Date.now()

    return {
        value,
        expiresAt: now + (ttlSeconds * 1000),
        createdAt: now,
        version: apiCacheVersion,
    }
}

const normalizeOptions = (options = {}) => {
    return {
        namespace: options.namespace || apiCachePrefix,
        ttl: resolveTtl(options.ttl),
        force: Boolean(options.force),
        persist: options.persist !== false,
    }
}

export function useApiCache(defaultOptions = {}) {
    const getStorageKey = (key, options = {}) => {
        const mergedOptions = {
            ...defaultOptions,
            ...options,
        }

        return makeKey(key, mergedOptions.namespace)
    }

    const remove = (key, options = {}) => {
        const storageKey = getStorageKey(key, options)

        memoryCache.delete(storageKey)
        pendingRequests.delete(storageKey)
        removeFromStorage(storageKey)
    }

    const get = (key, options = {}) => {
        if (!cacheEnabled) return null

        const storageKey = getStorageKey(key, options)
        const memoryEntry = memoryCache.get(storageKey)

        if (memoryEntry) {
            if (!isExpired(memoryEntry)) return memoryEntry.value

            remove(key, options)
            return null
        }

        const resolvedOptions = normalizeOptions({
            ...defaultOptions,
            ...options,
        })
        const storedEntry = resolvedOptions.persist ? readFromStorage(storageKey) : null

        if (!storedEntry) return null

        if (isExpired(storedEntry)) {
            remove(key, options)
            return null
        }

        memoryCache.set(storageKey, storedEntry)

        return storedEntry.value
    }

    const set = (key, value, options = {}) => {
        if (!cacheEnabled) return value

        const resolvedOptions = normalizeOptions({
            ...defaultOptions,
            ...options,
        })
        const storageKey = getStorageKey(key, resolvedOptions)
        const entry = buildEntry(value, resolvedOptions.ttl)

        memoryCache.set(storageKey, entry)

        if (resolvedOptions.persist) {
            writeToStorage(storageKey, entry)
        }

        return value
    }

    const remember = async (key, fetcher, options = {}) => {
        const resolvedOptions = normalizeOptions({
            ...defaultOptions,
            ...options,
        })
        const storageKey = getStorageKey(key, resolvedOptions)

        if (!resolvedOptions.force) {
            const cachedValue = get(key, resolvedOptions)
            if (cachedValue !== null && cachedValue !== undefined) return cachedValue
        }

        if (pendingRequests.has(storageKey)) {
            return pendingRequests.get(storageKey)
        }

        const request = Promise.resolve()
            .then(fetcher)
            .then((value) => {
                if (value !== null && value !== undefined) {
                    set(key, value, resolvedOptions)
                }

                return value
            })
            .finally(() => {
                pendingRequests.delete(storageKey)
            })

        pendingRequests.set(storageKey, request)

        return request
    }

    const rememberApi = async (request, fetcher, options = {}) => {
        const cacheKey = buildApiKey(options.key || apiCacheKey.DEFAULT, request)

        return remember(cacheKey, fetcher, options)
    }

    const clear = () => {
        const namespacePrefix = `${normalizeNamespace(defaultOptions.namespace)}:`

        Array.from(memoryCache.keys())
            .filter((key) => key.startsWith(namespacePrefix))
            .forEach((key) => memoryCache.delete(key))

        Array.from(pendingRequests.keys())
            .filter((key) => key.startsWith(namespacePrefix))
            .forEach((key) => pendingRequests.delete(key))

        if (!storageAvailable) return

        Object.keys(window.localStorage)
            .filter((key) => key.startsWith(namespacePrefix))
            .forEach((key) => window.localStorage.removeItem(key))
    }

    const clearByPrefix = (prefix, options = {}) => {
        const normalizedPrefix = getStorageKey(prefix, options)

        Array.from(memoryCache.keys())
            .filter((key) => key.startsWith(normalizedPrefix))
            .forEach((key) => memoryCache.delete(key))

        Array.from(pendingRequests.keys())
            .filter((key) => key.startsWith(normalizedPrefix))
            .forEach((key) => pendingRequests.delete(key))

        if (!storageAvailable) return

        Object.keys(window.localStorage)
            .filter((key) => key.startsWith(normalizedPrefix))
            .forEach((key) => window.localStorage.removeItem(key))
    }

    return {
        get,
        set,
        remember,
        rememberApi,
        remove,
        clear,
        clearByPrefix,
        makeKey,
        makeApiKey,
    }
}
