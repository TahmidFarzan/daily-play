import { onBeforeUnmount, ref } from 'vue'

export function useGamePlayTimer() {
    let startedAtMs = 0
    let stopped = false
    let ticker = null
    let pausedElapsedMs = 0

    const elapsedSeconds = ref(0)
    const elapsedMs = ref(0)
    const isPaused = ref(false)

    const measure = () => {
        const raw = Math.max(0, performance.now() - startedAtMs)

        elapsedMs.value = raw
        elapsedSeconds.value = Math.floor(raw / 1000)
    }

    const start = (offsetMs = 0) => {
        const offset = Math.max(0, Number.isFinite(offsetMs) ? offsetMs : 0)

        startedAtMs = performance.now() - offset
        stopped = false
        isPaused.value = false

        measure()
        window.clearInterval(ticker)
        ticker = window.setInterval(measure, 250)
    }

    const stop = () => {
        if (stopped) return

        measure()
        stopped = true
        isPaused.value = false
        window.clearInterval(ticker)
    }

    const pause = () => {
        if (stopped || isPaused.value) return

        measure()
        isPaused.value = true
        pausedElapsedMs = elapsedMs.value
        window.clearInterval(ticker)
    }

    const resume = () => {
        if (stopped || !isPaused.value) return

        isPaused.value = false
        startedAtMs = performance.now() - pausedElapsedMs
        window.clearInterval(ticker)
        ticker = window.setInterval(measure, 250)
    }

    onBeforeUnmount(() => {
        window.clearInterval(ticker)
    })

    return {
        elapsedSeconds,
        elapsedMs,
        isPaused,
        start,
        stop,
        pause,
        resume,
    }
}

export const MAX_GAMEPLAY_DURATION_MS = 24 * 60 * 60 * 1000 - 1

export const isValidGameplayDurationMs = (value) =>
    Number.isInteger(value) && value >= 0 && value <= MAX_GAMEPLAY_DURATION_MS

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
        ? `${String(hours).padStart(2, '0')}:${mm}:${ss}:${mmm}`
        : `${mm}:${ss}:${mmm}`
}

export const formatHumanDuration = (totalMs) => {
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

    if (hours > 0) {
        return `${String(hours).padStart(2, '0')} h ${mm} m ${ss} s ${mmm} ms`
    }

    return `${mm} m ${ss} s ${mmm} ms`
}
