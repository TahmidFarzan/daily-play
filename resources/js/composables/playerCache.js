const PLAYER_CACHE_KEY = 'daily-play.player'

export const readCachedPlayer = () => {
    try {
        const raw = window.localStorage.getItem(PLAYER_CACHE_KEY)

        if (!raw) return null

        const parsed = JSON.parse(raw)

        if (!parsed || typeof parsed !== 'object' || !parsed.slug) return null

        return parsed
    } catch {
        return null
    }
}

export const setCachedPlayer = (player) => {
    if (!player) return

    try {
        window.localStorage.setItem(PLAYER_CACHE_KEY, JSON.stringify(player))
    } catch {
        return
    }
}

export const clearCachedPlayer = () => {
    try {
        window.localStorage.removeItem(PLAYER_CACHE_KEY)
    } catch {
        return
    }
}