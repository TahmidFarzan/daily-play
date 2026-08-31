import apiClient from '@/config/axios'
import { apiCacheKey, apiCacheTTL, useApiCache } from '@/composables/useApiCache'

const { remember, rememberApi, remove } = useApiCache()

const fetchJson = async (url, params = {}) => {
    const response = await apiClient.get(url, { params })
    return response.data || []
}

export async function fetchFromApi(url, params = {}, options = {}) {
    try {
        if (options.cache === false) {
            return await fetchJson(url, params)
        }

        return await rememberApi(
            { url, params },
            () => fetchJson(url, params),
            {
                key: options.key || apiCacheKey.DEFAULT,
                ttl: options.ttl ?? apiCacheTTL.SYSTEM_SHORT,
                force: options.force ?? false,
            }
        )
    } catch (error) {
        console.error(`Failed to fetch from ${url}.`, error)
        return []
    }
}

export async function fetchUser(userSlugOrId) {
    if (!userSlugOrId) {
        return null
    }

    const cacheKey = `${apiCacheKey.API_USER}:${userSlugOrId}`

    try {
        return await remember(
            cacheKey,
            async () => {
                const response = await apiClient.get(
                    route('search.user', {
                        slugOrId: userSlugOrId,
                    })
                )
                return response.data || null
            },
            {
                ttl: apiCacheTTL.SYSTEM_SHORT,
            }
        )
    } catch (error) {
        console.error(`Failed to fetch user ${userSlugOrId}.`, error)
        remove(cacheKey)
        return null
    }
}

export async function postToApi(url, data = {}, config = {}) {
    try {
        const response = await apiClient.post(url, data, config)
        return response.data || null
    } catch (error) {
        console.error(`Failed to post to ${url}.`, error)
        return null
    }
}
