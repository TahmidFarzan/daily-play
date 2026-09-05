<script setup>
import { Head, router as inertiaJsRoute } from '@inertiajs/vue3'
import Layout from '@/pages/layouts/PublicLayout.vue'
import GameBoard from '@/components/games/GameBoard.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'
import PlayerModal from '@/components/players/PlayerModal.vue'
import { formatDate, formatTime , formatDateTime} from '@/composables/useDateTime'
import { formatDurationMs, formatHumanDuration, isValidGameplayDurationMs, MAX_GAMEPLAY_DURATION_MS, useGamePlayTimer } from '@/composables/useGamePlayTimer'
import { progressionColor, softTintColor } from '@/composables/progressColors'
import {
    getPlayerCache,
    setPlayerCache,
    removePlayerCache,
    stopPlayerCacheExpiration,
    subscribePlayerCacheChanges,
} from '@/composables/playerCache'
import { useGamePlayPersistence } from '@/composables/useGamePlayPersistence'

import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import apiClient from '@/config/axios'


import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faArrowLeft,
    faCircleCheck,
    faGamepad,
    faHouse,
    faRotateLeft,
    faSpinner,
    faStopwatch,
    faUser,
    faXmark,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faArrowLeft, faCircleCheck, faGamepad, faHouse, faRotateLeft, faSpinner, faStopwatch, faUser, faXmark)

defineOptions({
    layout: Layout,
})

const { gamePlay } = defineProps({
    gamePlay: { type: Object, default: null },
})

const game = computed(() => gamePlay?.game ?? {})
const board = computed(() => gamePlay?.board ?? {})
const difficulty = computed(() => gamePlay?.game_difficulty ?? null)

const dailyDateLabel = computed(() =>
    formatDateTime(gamePlay.start_at) + " - " + formatDateTime(gamePlay.end_at),
)

const {
    elapsedSeconds,
    elapsedMs,
    isPaused,
    start: startTimer,
    stop: stopTimer,
    pause: pauseTimer,
    resume: resumeTimer,
} = useGamePlayTimer()

const playerReady = ref(false)
const playerLoading = ref(true)
const playerError = ref(null)
const playerModalOpen = ref(false)
const currentPlayer = ref(null)

const gamePlayRef = computed(() => gamePlay)
const restorationReady = ref(false)
const restoredGameState = ref(null)
const latestGameState = ref(null)

const {
    restore: restorePersistence,
    save: savePersistence,
    clear: clearPersistence,
} = useGamePlayPersistence(gamePlayRef, currentPlayer)

const initialState = computed(() =>
    restorationReady.value ? restoredGameState.value : null,
)

const playedAt = ref(null)
const playStartedLabel = computed(() =>
    playedAt.value ? `${formatDate(playedAt.value)} at ${formatTime(playedAt.value, 'h:i a')}` : '',
)

const solved = ref(false)
const showResult = ref(false)
const navigating = ref(false)
const finalSolveTime = ref(null)
const backtrackCount = ref(0)

const scoreState = ref('idle')
const scoreError = ref('')
const scoreResult = ref(null)

const currentPlayerIdInResult = computed(() => scoreResult.value?.current_player?.id ?? null)

const isCurrentPlayerInTopRankers = computed(() =>
    Array.isArray(scoreResult.value?.top_rankers)
        && scoreResult.value.top_rankers.some(
            (ranker) =>
                ranker.player_id === currentPlayerIdInResult.value
                || ranker.player?.id === currentPlayerIdInResult.value,
        )
)

let resultTimer = null

const formattedElapsed = computed(() => formatDurationMs(Math.round(elapsedMs.value)))

const backtrackStyle = computed(() => ({
    color: progressionColor(backtrackCount.value),
    backgroundColor: softTintColor(backtrackCount.value),
    borderColor: progressionColor(backtrackCount.value),
}))

const solvedBacktrackLabel = computed(() =>
    backtrackCount.value > 0 ? `${backtrackCount.value} Backtracks` : 'No backtracks',
)

const handleBacktrackCount = (count) => {
    backtrackCount.value = count
}

let isRestoringGamePlay = true

const isGameplayActive = () =>
    restorationReady.value
    && !solved.value
    && document.visibilityState === 'visible'
    && document.hasFocus()

const syncTimerActivity = () => {
    if (solved.value || !restorationReady.value) return

    const active = isGameplayActive()

    if (active && isPaused.value) {
        resumeTimer()
    } else if (!active && !isPaused.value) {
        pauseTimer()
    }
}

const persistGameplay = () => {
    if (isRestoringGamePlay || solved.value) return
    if (!latestGameState.value) return

    savePersistence({
        durationMs: Math.round(elapsedMs.value),
        game: latestGameState.value,
    })
}

const handleGameStateChange = (gameState) => {
    latestGameState.value = gameState

    if (isRestoringGamePlay || solved.value) return

    savePersistence({
        durationMs: Math.round(elapsedMs.value),
        game: gameState,
    })
}

const handlePageHide = () => {
    if (isRestoringGamePlay || solved.value) return

    persistGameplay()
}

const handleVisibilityChange = () => {
    if (isRestoringGamePlay || solved.value) return

    if (document.visibilityState === 'hidden') {
        persistGameplay()
        syncTimerActivity()
    } else {
        syncTimerActivity()
    }
}

const handleWindowBlur = () => {
    if (isRestoringGamePlay || solved.value) return

    persistGameplay()
    syncTimerActivity()
}

const handleWindowFocus = () => {
    if (isRestoringGamePlay || solved.value) return

    syncTimerActivity()
}

let persistenceTimer = null

const schedulePersistenceTimer = () => {
    if (persistenceTimer) {
        window.clearInterval(persistenceTimer)
    }

    persistenceTimer = window.setInterval(persistGameplay, 1000)
}

const activateGameplay = (player) => {
    currentPlayer.value = player

    const snapshot = restorePersistence()

    if (snapshot && snapshot.game) {
        restoredGameState.value = snapshot.game
        latestGameState.value = snapshot.game
        startTimer(Math.round(snapshot.durationMs ?? 0))
    } else {
        restoredGameState.value = null
        latestGameState.value = null
        startTimer()
    }

    playedAt.value = new Date()
    restorationReady.value = true
    playerReady.value = true
    playerLoading.value = false

    isRestoringGamePlay = false

    schedulePersistenceTimer()
    syncTimerActivity()
}

const verifyPlayer = async () => {
    const cached = getPlayerCache()

    if (!cached) {
        playerLoading.value = false
        playerModalOpen.value = true
        return
    }

    try {

        const response = await apiClient.get(route('players.get', {
            slug: cached.slug,
        }))

        const data = response?.data

        if (data?.status === 'success' && data?.data) {
            const backendPlayer = data.data
            setPlayerCache(backendPlayer)
            activateGameplay(backendPlayer)
            return
        }

        throw new Error('Invalid player response')
    } catch (error) {
        if (error?.response?.status === 404) {
            removePlayerCache()
            playerLoading.value = false
            playerModalOpen.value = true
            return
        }

        playerLoading.value = false
        playerError.value = 'Unable to verify your profile right now. Please try again.'
    }
}

const retryPlayer = () => {
    playerError.value = null
    playerLoading.value = true
    verifyPlayer()
}

const handlePlayerSaved = (player) => {
    setPlayerCache(player)
    playerModalOpen.value = false
    activateGameplay(player)
}

let unsubscribePlayerCacheChanges = () => {}

onMounted(() => {
    unsubscribePlayerCacheChanges = subscribePlayerCacheChanges(() => {
        if (playerReady.value || playerLoading.value) return

        playerError.value = null
        playerModalOpen.value = true
    })

    window.addEventListener('pagehide', handlePageHide)
    document.addEventListener('visibilitychange', handleVisibilityChange)
    window.addEventListener('blur', handleWindowBlur)
    window.addEventListener('focus', handleWindowFocus)

    verifyPlayer()
})

const handleSolved = (payload) => {
    if (solved.value) return

    solved.value = true
    stopTimer()
    restorationReady.value = false
    window.clearInterval(persistenceTimer)
    clearPersistence()

    finalSolveTime.value = formatHumanDuration(elapsedMs.value)
    backtrackCount.value = payload?.backtrackCount ?? backtrackCount.value

    submitScore()

    window.clearTimeout(resultTimer)
    resultTimer = window.setTimeout(() => {
        showResult.value = true
    }, 700)
}

const finalDurationMs = computed(() => Math.round(elapsedMs.value))

const submitScore = async () => {
    if (scoreState.value === 'submitting' || scoreState.value === 'success') return

    const durationMs = finalDurationMs.value

    if (!isValidGameplayDurationMs(durationMs)) {
        scoreState.value = 'error'
        scoreError.value = `Your completion time is invalid (${formatDurationMs(durationMs)} > ${formatDurationMs(MAX_GAMEPLAY_DURATION_MS)}). Please finish the puzzle in under 24 hours and try again.`
        return
    }

    scoreState.value = 'submitting'
    scoreError.value = ''

    const targetSlug = gamePlay?.game?.slug

    try {
        const response = await apiClient.post(route('games.score.save', {
            slug: targetSlug,
        }), {
            player_id: currentPlayer.value?.id,
            duration_ms: durationMs,
            backtracks: backtrackCount.value,
        })

        const data = response?.data

        if (data?.status === 'success' && data?.data) {
            scoreResult.value = data.data
            scoreState.value = 'success'
            return
        }

        throw new Error(data?.message || 'Failed to save score.')
    } catch (error) {
        scoreState.value = 'error'
        scoreError.value = 'Failed to save your score. Please try again.'
    }
}

const closeResult = () => {
    showResult.value = false
}

const goHome = () => {
    if (navigating.value) return

    navigating.value = true
    inertiaJsRoute.visit(route('home'))
}

const onKeydown = (event) => {
    if (event.key === 'Escape' && showResult.value) {
        closeResult()
    }
}

onMounted(() => {
    window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
    unsubscribePlayerCacheChanges()
    stopPlayerCacheExpiration()
    window.removeEventListener('keydown', onKeydown)
    window.removeEventListener('pagehide', handlePageHide)
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    window.removeEventListener('blur', handleWindowBlur)
    window.removeEventListener('focus', handleWindowFocus)
    window.clearTimeout(resultTimer)
    window.clearInterval(persistenceTimer)
})
</script>

<template>
    <Head :title="`${game?.name || 'Game'} - Play`" />

    <section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
        <a
            v-if="game?.slug"
            :href="route('games.details', { slug: game.slug })"
            class="inline-flex items-center gap-2 text-sm font-medium text-[var(--daily-play-text-muted)] transition hover:text-[var(--daily-play-accent)]"
        >
            <FontAwesomeIcon icon="arrow-left" class="text-xs" />
            Back to game details
        </a>

        <div
            class="flex flex-col gap-4 rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-4">
                <div
                    class="hidden h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-[var(--daily-play-border)] bg-gray-100 sm:flex"
                >
                    <MediaRenderer
                        v-if="game?.logo"
                        :media="game.logo"
                        media-class="w-full h-full object-contain p-1"
                    />
                    <FontAwesomeIcon
                        v-else
                        icon="gamepad"
                        class="text-3xl text-[var(--daily-play-text-muted)]"
                    />
                </div>

                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-[var(--daily-play-text)] sm:text-3xl">
                            {{ game?.name || 'Game' }}
                        </h1>

                        <span
                            v-if="difficulty?.name"
                            class="rounded-full border border-[var(--daily-play-accent)] bg-[var(--daily-play-accent-soft)] px-2.5 py-0.5 text-xs font-semibold text-[var(--daily-play-accent-active)]"
                        >
                            {{ difficulty.name }}
                        </span>

                        <span
                            v-else
                            class="rounded-full border border-[var(--daily-play-accent)] bg-[var(--daily-play-accent-soft)] px-2.5 py-0.5 text-xs font-semibold text-[var(--daily-play-accent-active)]"
                        >
                            Normal
                        </span>
                    </div>

                    <p class="text-sm text-[var(--daily-play-text-muted)]">
                        <span v-if="dailyDateLabel">Daily puzzle &middot; {{ dailyDateLabel }}</span>
                        <span v-else>Daily puzzle</span>
                    </p>
                </div>
            </div>

            <div class="flex flex-col items-start gap-1.5 sm:items-end">
                <p
                    v-if="playedAt"
                    class="text-xs font-medium text-[var(--daily-play-text-muted)]"
                >
                    Play at: {{ playStartedLabel }}
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    <div
                        class="inline-flex items-center gap-2 rounded-xl border border-[var(--daily-play-border)] bg-gray-50 px-4 py-2"
                    >
                        <FontAwesomeIcon icon="stopwatch" class="text-[var(--daily-play-text-muted)]" />
                        <span
                            class="font-mono text-lg font-semibold tabular-nums text-[var(--daily-play-text)]"
                        >
                            {{ formattedElapsed }}
                        </span>
                    </div>

                    <div
                        v-if="backtrackCount > 0"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2"
                        :style="backtrackStyle"
                    >
                        <FontAwesomeIcon icon="rotate-left" class="text-sm" />
                        <span class="font-mono text-base font-semibold tabular-nums">
                            {{ backtrackCount }}
                        </span>
                        <span class="text-xs font-medium">Backtracks</span>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="game?.brief"
            class="rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] p-5 text-sm leading-relaxed text-[var(--daily-play-text-muted)] shadow-sm"
        >
            {{ game.brief }}
        </div>

        <div
            v-if="playerLoading"
            class="flex items-center justify-center gap-2 rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] px-5 py-4 text-sm text-[var(--daily-play-text-muted)] shadow-sm"
        >
            <FontAwesomeIcon icon="spinner" spin class="text-[var(--daily-play-accent)]" />
            Checking your profile&hellip;
        </div>

        <div
            v-else-if="playerError"
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[var(--daily-play-danger)] bg-[var(--daily-play-surface)] px-5 py-4 shadow-sm"
        >
            <p class="text-sm font-medium text-[var(--daily-play-danger)]">
                {{ playerError }}
            </p>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-[var(--daily-play-accent)] px-4 py-2 text-sm font-semibold text-[var(--daily-play-text-inverse)] transition hover:bg-[var(--daily-play-accent-hover)] active:bg-[var(--daily-play-accent-active)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--daily-play-accent)] focus-visible:ring-offset-2"
                @click="retryPlayer"
            >
                <FontAwesomeIcon icon="rotate-left" class="text-xs" />
                Try again
            </button>
        </div>

        <div
            v-if="playerReady && currentPlayer"
            class="flex items-center gap-3 rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] px-5 py-4 shadow-sm"
        >
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[var(--daily-play-accent-soft)]">
                <FontAwesomeIcon
                    icon="user"
                    class="text-lg text-[var(--daily-play-accent-active)]"
                />
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-[var(--daily-play-text)]">
                    {{ currentPlayer.name }}
                </p>
                <p
                    v-if="currentPlayer.email || currentPlayer.mobile"
                    class="truncate text-xs text-[var(--daily-play-text-muted)]"
                >
                    {{ [currentPlayer.email, currentPlayer.mobile].filter(Boolean).join(' · ') }}
                </p>
            </div>
        </div>

        <div class="relative">
            <GameBoard
                :game="game"
                :board="board"
                :disabled="solved || !playerReady"
                :initial-state="initialState"
                @completed="handleSolved"
                @backtrack-count="handleBacktrackCount"
                @state-change="handleGameStateChange"
            />
        </div>

        <div
            v-if="game?.how_to_play"
            class="rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] p-5 shadow-sm"
        >
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-[var(--daily-play-text-muted)]">
                How to Play
            </h2>
            <p class="whitespace-pre-line text-sm leading-relaxed text-[var(--daily-play-text)]">
                {{ game.how_to_play }}
            </p>
        </div>
    </section>

    <PlayerModal
        v-if="playerModalOpen"
        @saved="handlePlayerSaved"
    />

    <Teleport to="body">
        <Transition name="completion-modal">
            <div
                v-if="showResult"
                class="completion-overlay"
                role="presentation"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="daily-play-completion-title"
                    class="completion-panel"
                >
                    <button
                        type="button"
                        class="completion-close"
                        aria-label="Close"
                        @click="closeResult"
                    >
                        <FontAwesomeIcon icon="xmark" />
                    </button>

                    <div
                        class="check-badge mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[var(--daily-play-accent-soft)]"
                    >
                        <FontAwesomeIcon
                            icon="circle-check"
                            class="text-3xl text-[var(--daily-play-accent-active)]"
                        />
                    </div>

                    <h2
                        id="daily-play-completion-title"
                        class="mt-3 text-xl font-bold tracking-tight text-[var(--daily-play-text)] sm:text-2xl"
                    >
                        Puzzle Complete
                    </h2>

                    <div class="mt-5 space-y-3">
                        <div
                            class="stat-item rounded-xl border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-3 py-3"
                        >
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--daily-play-text-muted)]">
                                Time
                            </p>
                            <p
                                class="mt-1 font-mono text-xl font-semibold tabular-nums text-[var(--daily-play-text)] sm:text-2xl"
                            >
                                {{ finalSolveTime }}
                            </p>
                        </div>

                        <div
                            class="stat-item rounded-xl border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-3 py-2"
                        >
                            <p class="flex items-center justify-center gap-2 text-sm text-[var(--daily-play-text)]">
                                <span class="text-xs font-semibold uppercase tracking-wide text-[var(--daily-play-text-muted)]">
                                    Backtracks
                                </span>
                                <span class="font-mono text-base font-semibold tabular-nums">
                                    {{ solvedBacktrackLabel }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="scoreState === 'success' && scoreResult"
                        class="mt-5 flex items-center justify-center gap-2 rounded-xl border border-[var(--daily-play-accent)] bg-[var(--daily-play-accent-soft)] px-4 py-3 text-left"
                    >
                        <span class="text-xs font-semibold uppercase tracking-wide text-[var(--daily-play-text-muted)]">
                            Your Rank:
                        </span>
                        <span class="shrink-0 font-mono text-lg font-bold tabular-nums text-[var(--daily-play-accent-active)]">
                            #{{ scoreResult.rank }}
                        </span>
                    </div>

                    <div
                        v-if="scoreState === 'success' && scoreResult?.top_rankers?.length"
                        class="mt-5 rounded-xl border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] p-3 text-left sm:p-4"
                    >
                        <div class="mb-2.5 flex items-center justify-between gap-2 px-1">
                            <p class="text-sm font-semibold uppercase tracking-wide text-[var(--daily-play-text-muted)]">
                                Top Rankers
                            </p>
                            <p
                                v-if="scoreResult.top_rankers.length > 3"
                                class="text-xs text-[var(--daily-play-text-muted)]"
                            >
                                Scroll for more
                            </p>
                        </div>

                        <ul
                            class="space-y-2"
                            :class="{ 'top-rankers-scroll': scoreResult.top_rankers.length > 3 }"
                        >
                            <li
                                v-for="(entry, index) in scoreResult.top_rankers"
                                :key="`${entry.player_id}-${index}`"
                                class="rounded-lg px-2.5 py-2"
                                :class="{ 'bg-[var(--daily-play-accent-soft)] ring-1 ring-inset ring-[var(--daily-play-accent)]': entry.player_id === currentPlayerIdInResult }"
                            >
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="shrink-0 font-mono text-sm font-semibold tabular-nums text-[var(--daily-play-accent-active)]">
                                        #{{ entry.rank }}
                                    </span>
                                    <span
                                        class="min-w-0 flex-1 truncate text-sm font-semibold text-[var(--daily-play-text)]"
                                        :title="entry.player?.name || 'Player'"
                                    >
                                        {{ entry.player?.name || 'Player' }}
                                    </span>
                                </div>
                                <div class="mt-1 space-y-0.5 pl-6 text-xs text-[var(--daily-play-text-muted)]">
                                    <p class="flex items-center gap-1.5">
                                        <span class="font-medium uppercase tracking-wide">Time:</span>
                                        <span class="font-mono font-semibold tabular-nums text-[var(--daily-play-text)]">
                                            {{ formatHumanDuration(entry.duration_ms) }}
                                        </span>
                                    </p>
                                    <p class="flex items-center gap-1.5">
                                        <span class="font-medium uppercase tracking-wide">Backtracks:</span>
                                        <span class="font-mono font-semibold tabular-nums text-[var(--daily-play-text)]">
                                            {{ entry.backtracks }}
                                        </span>
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div
                        v-if="scoreState === 'success' && scoreResult && !isCurrentPlayerInTopRankers"
                        class="mt-5 rounded-xl border border-[var(--daily-play-accent)] bg-[var(--daily-play-accent-soft)] px-4 py-3 text-left"
                    >
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="shrink-0 font-mono text-lg font-bold tabular-nums text-[var(--daily-play-accent-active)]">
                                #{{ scoreResult.rank }}
                            </span>
                            <span
                                class="min-w-0 flex-1 truncate text-sm font-semibold text-[var(--daily-play-text)]"
                                :title="scoreResult?.current_player?.name || 'Player'"
                            >
                                {{ scoreResult?.current_player?.name || 'Player' }}
                            </span>
                        </div>

                        <div class="mt-1.5 space-y-0.5 text-xs text-[var(--daily-play-text-muted)]">
                            <p class="flex items-center gap-1.5">
                                <span class="font-medium uppercase tracking-wide">Time:</span>
                                <span class="font-mono font-semibold tabular-nums text-[var(--daily-play-text)]">
                                    {{ formatHumanDuration(scoreResult?.score?.duration_ms ?? 0) }}
                                </span>
                            </p>
                            <p class="flex items-center gap-1.5">
                                <span class="font-medium uppercase tracking-wide">Backtracks:</span>
                                <span class="font-mono font-semibold tabular-nums text-[var(--daily-play-text)]">
                                    {{ scoreResult?.score?.backtracks ?? backtrackCount.value }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="scoreState === 'submitting'"
                        class="mt-5 flex items-center justify-center gap-2 rounded-xl border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-4 py-3 text-sm text-[var(--daily-play-text-muted)]"
                    >
                        <FontAwesomeIcon icon="spinner" spin class="text-[var(--daily-play-accent)]" />
                        Submitting your score&hellip;
                    </div>

                    <div
                        v-if="scoreState === 'error'"
                        class="mt-4 space-y-2"
                    >
                        <p class="rounded-lg bg-[var(--daily-play-background)] px-3 py-2 text-sm font-medium text-[var(--daily-play-danger)]">
                            {{ scoreError }}
                        </p>

                        <button
                            type="button"
                            class="completion-retry inline-flex w-full items-center justify-center gap-2 rounded-lg border border-[var(--daily-play-accent)] bg-[var(--daily-play-accent-soft)] px-3 py-2 text-sm font-semibold text-[var(--daily-play-accent-active)] transition hover:bg-[var(--daily-play-accent)] hover:text-[var(--daily-play-text-inverse)]"
                            :disabled="scoreState === 'submitting'"
                            @click="submitScore"
                        >
                            <FontAwesomeIcon icon="rotate-left" class="text-xs" />
                            Try again
                        </button>
                    </div>

                    <button
                        type="button"
                        class="completion-home"
                        :disabled="navigating"
                        @click="goHome"
                    >
                        <FontAwesomeIcon icon="house" />
                        <span>{{ navigating ? 'Going home…' : 'Go to Home' }}</span>
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.check-badge {
    animation: check-pop 0.5s cubic-bezier(0.2, 1.4, 0.3, 1) 0.15s both;
}

.stat-item:nth-child(1) {
    animation: stat-in 0.4s ease-out 0.4s both;
}

.stat-item:nth-child(2) {
    animation: stat-in 0.4s ease-out 0.55s both;
}

.top-rankers-scroll {
    max-height: 15rem;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding-right: 0.25rem;
}

.top-rankers-scroll::-webkit-scrollbar {
    width: 6px;
}

.top-rankers-scroll::-webkit-scrollbar-thumb {
    border-radius: 9999px;
    background: var(--daily-play-border);
}

.top-rankers-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.completion-overlay {
    position: fixed;
    inset: 0;
    z-index: 60;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgb(15 23 42 / 0.5);
    overflow-y: auto;
}

.completion-panel {
    position: relative;
    width: 100%;
    max-width: 24rem;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
    border-radius: 1.25rem;
    border: 1px solid var(--daily-play-border);
    background: var(--daily-play-surface);
    padding: 1.5rem;
    padding-top: 2rem;
    text-align: center;
    box-shadow: var(--daily-play-shadow-lg);
}

.completion-close {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 2rem;
    width: 2rem;
    border-radius: 9999px;
    color: var(--daily-play-text-muted);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.completion-close:hover {
    background: var(--daily-play-accent-soft);
    color: var(--daily-play-accent-active);
}

.completion-close:focus-visible {
    outline: 2px solid var(--daily-play-accent);
    outline-offset: 2px;
}

.completion-home {
    margin-top: 1.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    border-radius: 0.75rem;
    border: none;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--daily-play-text-inverse);
    background: var(--daily-play-accent);
    cursor: pointer;
    transition: background-color 0.15s ease, transform 0.15s ease;
}

.completion-home:hover:not(:disabled) {
    background: var(--daily-play-accent-hover);
}

.completion-home:active:not(:disabled) {
    background: var(--daily-play-accent-active);
    transform: scale(0.98);
}

.completion-home:focus-visible {
    outline: 2px solid var(--daily-play-accent);
    outline-offset: 2px;
}

.completion-home:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.completion-retry {
    border: 1px solid var(--daily-play-accent);
}

.completion-retry:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.completion-modal-enter-active,
.completion-modal-leave-active {
    transition: opacity 0.25s ease;
}

.completion-modal-enter-active .completion-panel,
.completion-modal-leave-active .completion-panel {
    transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.completion-modal-enter-from,
.completion-modal-leave-to {
    opacity: 0;
}

.completion-modal-enter-from .completion-panel,
.completion-modal-leave-to .completion-panel {
    transform: scale(0.95);
}

@keyframes check-pop {
    0% {
        opacity: 0;
        transform: scale(0.4);
    }

    60% {
        transform: scale(1.12);
    }

    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes stat-in {
    from {
        opacity: 0;
        transform: translateY(6px);
    }

    to {
        opacity: 1;
        transform: none;
    }
}
</style>
