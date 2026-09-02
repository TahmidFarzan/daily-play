import { onBeforeUnmount, ref } from 'vue'

export function useGamePlayTimer() {
    let startedAtMs = 0
    let stopped = false
    let ticker = null

    const elapsedSeconds = ref(0)
    const elapsedMs = ref(0)

    const measure = () => {
        const raw = Math.max(0, performance.now() - startedAtMs)

        elapsedMs.value = raw
        elapsedSeconds.value = Math.floor(raw / 1000)
    }

    const start = () => {
        startedAtMs = performance.now()
        stopped = false

        measure()
        window.clearInterval(ticker)
        ticker = window.setInterval(measure, 250)
    }

    const stop = () => {
        if (stopped) return

        measure()
        stopped = true
        window.clearInterval(ticker)
    }

    onBeforeUnmount(() => {
        window.clearInterval(ticker)
    })

    return {
        elapsedSeconds,
        elapsedMs,
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

export const formatDurationMs = (totalMs) => {
    const total = Math.max(0, Math.trunc(totalMs))
    const ms = total % 1000
    const totalSeconds = Math.floor(total / 1000)
    const seconds = totalSeconds % 60
    const totalMinutes = Math.floor(totalSeconds / 60)
    const minutes = totalMinutes % 60
    const hours = Math.floor(totalMinutes / 60)

    const mm = String(minutes).padStart(2, '0')
    const ss = String(seconds).padStart(2, '0')
    const mmm = String(ms).padStart(3, '0')

    return hours > 0
        ? `${hours}:${mm}:${ss}.${mmm}`
        : `${mm}:${ss}.${mmm}`
}