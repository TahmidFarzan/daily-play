const STORAGE_NAMESPACE = 'daily_play_gameplay'
const STATE_VERSION = 1

const hasWindow = typeof window !== 'undefined'

const storageKeyFor = (gamePlayId, playerId) =>
    `${STORAGE_NAMESPACE}_${gamePlayId}_${playerId}`

const expiresAtOf = (gamePlay) => {
    for (const field of ['ends_at', 'endsAt']) {
        const value = gamePlay?.[field]

        if (value) {
            const time = new Date(value).getTime()

            if (Number.isFinite(time)) return time
        }
    }

    return null
}

const readRaw = (key) => {
    if (!hasWindow) return null

    try {
        return window.localStorage.getItem(key)
    } catch {
        return null
    }
}

const writeRaw = (key, value) => {
    if (!hasWindow) return

    try {
        window.localStorage.setItem(key, value)
    } catch {
        return
    }
}

const removeRaw = (key) => {
    if (!hasWindow) return

    try {
        window.localStorage.removeItem(key)
    } catch {
        return
    }
}

const isValidSnapshot = (snapshot) => {
    if (!snapshot || typeof snapshot !== 'object') return false
    if (snapshot.version !== STATE_VERSION) return false

    const gamePlay = snapshot.gamePlay
    const player = snapshot.player

    if (!gamePlay || !player) return false
    if (!Number.isFinite(gamePlay.id)) return false
    if (!Number.isFinite(player.id) || typeof player.slug !== 'string') return false
    if (!Number.isFinite(snapshot.durationMs)) return false
    if (!Number.isFinite(snapshot.expiresAt)) return false

    return true
}

const isExpired = (snapshot) =>
    Number.isFinite(snapshot?.expiresAt) && Date.now() >= snapshot.expiresAt

export function useGamePlayPersistence(gamePlayRef, playerRef) {
    const currentGamePlayId = () => gamePlayRef.value?.id ?? null

    const currentPlayerId = () => playerRef.value?.id ?? null

    const currentKey = () =>
        storageKeyFor(currentGamePlayId(), currentPlayerId())

    const parseSnapshot = (key) => {
        const raw = readRaw(key)

        if (!raw) return null

        try {
            const parsed = JSON.parse(raw)

            return isValidSnapshot(parsed) ? parsed : null
        } catch {
            return null
        }
    }

    const cleanupStale = (gamePlayId, playerId) => {
        if (!hasWindow) return

        try {
            const keys = Object.keys(window.localStorage)

            for (const key of keys) {
                if (!key.startsWith(`${STORAGE_NAMESPACE}_`)) continue

                const remainder = key.slice(STORAGE_NAMESPACE.length + 1)
                const match = /^(\d+)_(\d+)$/.exec(remainder)

                if (!match) continue

                const keyGamePlayId = Number(match[1])
                const keyPlayerId = Number(match[2])

                if (keyPlayerId !== playerId) continue

                if (keyGamePlayId === gamePlayId) {
                    const snapshot = parseSnapshot(key)

                    if (!snapshot || isExpired(snapshot)) {
                        removeRaw(key)
                    }
                } else {
                    removeRaw(key)
                }
            }
        } catch {
            return
        }
    }

    const restore = () => {
        const gamePlayId = currentGamePlayId()
        const playerId = currentPlayerId()

        if (!gamePlayId || !playerId) return null

        cleanupStale(gamePlayId, playerId)

        const key = currentKey()
        const snapshot = parseSnapshot(key)

        if (!snapshot) return null

        if (snapshot.gamePlay.id !== gamePlayId || snapshot.player.id !== playerId) {
            removeRaw(key)
            return null
        }

        if (isExpired(snapshot)) {
            removeRaw(key)
            return null
        }

        return snapshot
    }

    const save = (gameState) => {
        const gamePlayId = currentGamePlayId()
        const playerId = currentPlayerId()
        const gamePlay = gamePlayRef.value

        if (!gamePlayId || !playerId || !gamePlay) return

        const snapshot = {
            version: STATE_VERSION,
            savedAt: Date.now(),
            gamePlay: { id: gamePlayId },
            player: {
                id: playerId,
                slug: playerRef.value?.slug ?? null,
            },
            expiresAt: expiresAtOf(gamePlay),
            durationMs: gameState?.durationMs ?? 0,
            game: gameState?.game ?? null,
        }

        writeRaw(currentKey(), JSON.stringify(snapshot))
    }

    const clear = () => {
        const key = currentKey()

        if (key) {
            removeRaw(key)
        }
    }

    return {
        restore,
        save,
        clear,
    }
}
