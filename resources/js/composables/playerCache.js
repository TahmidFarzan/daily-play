const PLAYER_CACHE_KEY = 'daily-play.player'
const PLAYER_CACHE_TTL = 24 * 60 * 60 * 1000

let expirationTimerId = null

const hasWindow = typeof window !== 'undefined'

const parseCache = (raw) => {
    try {
        const parsed = JSON.parse(raw)

        if (!parsed || typeof parsed !== 'object') return null
        if (!parsed.player || typeof parsed.player !== 'object') return null
        if (typeof parsed.player.slug !== 'string' || parsed.player.slug.trim() === '') return null
        if (!Number.isFinite(parsed.expires_at)) return null

        return parsed
    } catch {
        return null
    }
}

const writeCache = (payload) => {
    if (!hasWindow) return

    try {
        window.localStorage.setItem(PLAYER_CACHE_KEY, JSON.stringify(payload))
    } catch {
        return
    }
}

const removeStoredCache = () => {
    if (!hasWindow) return

    try {
        window.localStorage.removeItem(PLAYER_CACHE_KEY)
    } catch {
        return
    }
}

const clearExpirationTimer = () => {
    if (expirationTimerId !== null) {
        if (hasWindow) {
            window.clearTimeout(expirationTimerId)
        }

        expirationTimerId = null
    }
}

const schedulePlayerCacheExpiration = (expiresAt) => {
    clearExpirationTimer()

    if (!hasWindow) return

    const remaining = expiresAt - Date.now()

    if (remaining <= 0) {
        removeStoredCache()
        return
    }

    expirationTimerId = window.setTimeout(() => {
        expirationTimerId = null
        removeStoredCache()
    }, remaining)
}

export const getPlayerCache = () => {
    if (!hasWindow) return null

    const raw = window.localStorage.getItem(PLAYER_CACHE_KEY)

    if (!raw) return null

    const parsed = parseCache(raw)

    if (!parsed) {
        removeStoredCache()
        return null
    }

    if (Date.now() >= parsed.expires_at) {
        schedulePlayerCacheExpiration(parsed.expires_at)
        return null
    }

    schedulePlayerCacheExpiration(parsed.expires_at)

    return parsed.player
}

export const setPlayerCache = (player, ttlMs = PLAYER_CACHE_TTL) => {
    if (!player || typeof player !== 'object') return null
    if (typeof player.slug !== 'string' || player.slug.trim() === '') return null

    const cachedAt = Date.now()
    const expiresAt = cachedAt + ttlMs

    writeCache({
        player,
        cached_at: cachedAt,
        expires_at: expiresAt,
    })

    schedulePlayerCacheExpiration(expiresAt)

    return player
}

export const removePlayerCache = () => {
    clearExpirationTimer()
    removeStoredCache()
}

export const stopPlayerCacheExpiration = () => {
    clearExpirationTimer()
}

export const subscribePlayerCacheChanges = (listener) => {
    if (!hasWindow || typeof listener !== 'function') return () => {}

    const handleStorage = (event) => {
        if (event.key === PLAYER_CACHE_KEY && event.newValue === null) {
            listener()
        }
    }

    window.addEventListener('storage', handleStorage)

    return () => {
        window.removeEventListener('storage', handleStorage)
    }
}

export { PLAYER_CACHE_KEY, PLAYER_CACHE_TTL }