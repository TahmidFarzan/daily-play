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
