<script setup>
import { Head, router as inertiaJsRoute } from '@inertiajs/vue3'
import Layout from '@/pages/layouts/PublicLayout.vue'
import GameBoard from '@/components/games/GameBoard.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'
import { formatDate, formatTime } from '@/composables/useDateTime'
import { formatSolveDuration, useGamePlayTimer } from '@/composables/useGamePlayTimer'
import { progressionColor, softTintColor } from '@/composables/progressColors'

import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft, faCircleCheck, faGamepad, faHouse, faRotateLeft, faStopwatch, faXmark } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faArrowLeft, faCircleCheck, faGamepad, faHouse, faRotateLeft, faStopwatch, faXmark)

defineOptions({
    layout: Layout,
})

const { dailyGame } = defineProps({
    dailyGame: { type: Object, default: null },
})

const game = computed(() => dailyGame?.game ?? {})
const board = computed(() => dailyGame?.board ?? {})
const difficulty = computed(() => dailyGame?.game_difficulty ?? null)

const dailyDateLabel = computed(() =>
    dailyGame?.game_date ? formatDate(dailyGame.game_date) : '',
)

const playedAt = new Date()
const playStartedLabel = computed(() =>
    `${formatDate(playedAt)} at ${formatTime(playedAt, 'h:i a')}`,
)

const { elapsedSeconds, stop: stopTimer } = useGamePlayTimer()

const solved = ref(false)
const showResult = ref(false)
const navigating = ref(false)
const finalSolveTime = ref(null)
const backtrackCount = ref(0)

let resultTimer = null

const formattedElapsed = computed(() => formatSolveDuration(elapsedSeconds.value))

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

const handleSolved = (payload) => {
    if (solved.value) return

    solved.value = true
    finalSolveTime.value = formatSolveDuration(elapsedSeconds.value)
    backtrackCount.value = payload?.backtrackCount ?? backtrackCount.value
    stopTimer()

    window.clearTimeout(resultTimer)
    resultTimer = window.setTimeout(() => {
        showResult.value = true
    }, 700)
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
    window.removeEventListener('keydown', onKeydown)
    window.clearTimeout(resultTimer)
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
                <p class="text-xs font-medium text-[var(--daily-play-text-muted)]">
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

        <div class="relative">
            <GameBoard
                :game="game"
                :board="board"
                :disabled="solved"
                @completed="handleSolved"
                @backtrack-count="handleBacktrackCount"
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
                        class="mt-4 text-2xl font-bold tracking-tight text-[var(--daily-play-text)]"
                    >
                        Puzzle Complete
                    </h2>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div
                            class="stat-item rounded-xl border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-4 py-3"
                        >
                            <p class="text-xs font-medium uppercase tracking-wide text-[var(--daily-play-text-muted)]">
                                Time
                            </p>
                            <p
                                class="mt-1 font-mono text-xl font-semibold tabular-nums text-[var(--daily-play-text)]"
                            >
                                {{ finalSolveTime }}
                            </p>
                        </div>

                        <div
                            class="stat-item rounded-xl border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-4 py-3"
                        >
                            <p class="text-xs font-medium uppercase tracking-wide text-[var(--daily-play-text-muted)]">
                                Backtracks
                            </p>
                            <p class="mt-1 text-xl font-semibold text-[var(--daily-play-text)]">
                                {{ solvedBacktrackLabel }}
                            </p>
                        </div>
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

.completion-overlay {
    position: fixed;
    inset: 0;
    z-index: 60;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    background: rgb(15 23 42 / 0.4);
    overflow-y: auto;
}

.completion-panel {
    position: relative;
    width: 100%;
    max-width: 26rem;
    border-radius: 1.5rem;
    border: 1px solid var(--daily-play-border);
    background: var(--daily-play-surface);
    padding: 2rem;
    padding-top: 2.5rem;
    text-align: center;
    box-shadow: var(--daily-play-shadow-lg);
}

.completion-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 2.25rem;
    width: 2.25rem;
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
    margin-top: 1.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    border-radius: 0.75rem;
    border: none;
    padding: 0.75rem 1rem;
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
