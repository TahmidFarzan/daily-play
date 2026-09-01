import { onBeforeUnmount, ref } from 'vue'

export function useGamePlayTimer() {
    let startedAtMs = Date.now()
    let stopped = false
    let ticker = null

    const elapsedSeconds = ref(0)

    const update = () => {
        if (stopped) return

        elapsedSeconds.value = Math.max(0, Math.floor((Date.now() - startedAtMs) / 1000))
    }

    const start = () => {
        startedAtMs = Date.now()
        stopped = false

        update()
        window.clearInterval(ticker)
        ticker = window.setInterval(update, 500)
    }

    const stop = () => {
        if (stopped) return

        update()
        stopped = true
        window.clearInterval(ticker)
    }

    onBeforeUnmount(() => {
        window.clearInterval(ticker)
    })

    start()

    return {
        elapsedSeconds,
        start,
        stop,
    }
}

export const formatSolveDuration = (totalSeconds) => {
    const total = Math.max(0, Math.floor(totalSeconds))

    if (total < 60) {
        return `00:${String(total).padStart(2, '0')}s`
    }

    if (total < 3600) {
        const minutes = Math.floor(total / 60)
        const seconds = total % 60

        return `${minutes}:${String(seconds).padStart(2, '0')} min`
    }

    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const seconds = total % 60

    return `${hours}hr ${String(minutes).padStart(2, '0')}min ${String(seconds).padStart(2, '0')}s`
}