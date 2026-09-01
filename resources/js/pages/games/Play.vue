<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/pages/layouts/PublicLayout.vue'
import GameBoard from '@/components/games/GameBoard.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'
import { formatDate, formatTime } from '@/composables/useDateTime'
import { formatSolveDuration, useGamePlayTimer } from '@/composables/useGamePlayTimer'

import { computed, ref } from 'vue'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft, faCircleCheck, faGamepad, faRotateLeft, faStopwatch } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faArrowLeft, faCircleCheck, faGamepad, faRotateLeft, faStopwatch)

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
const finalSolveTime = ref(null)
const backtrackCount = ref(0)

const formattedElapsed = computed(() => formatSolveDuration(elapsedSeconds.value))

const backtrackLevel = computed(() => {
    if (backtrackCount.value <= 4) {
        return 'border-[var(--daily-play-border)] text-[var(--daily-play-text-muted)]'
    }

    if (backtrackCount.value <= 6) {
        return 'border-amber-200 bg-amber-50 text-amber-700'
    }

    if (backtrackCount.value <= 8) {
        return 'border-orange-200 bg-orange-50 text-orange-700'
    }

    if (backtrackCount.value <= 10) {
        return 'border-rose-200 bg-rose-50 text-rose-700'
    }

    return 'border-pink-200 bg-pink-50 text-pink-700'
})

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
}
</script>

<template>
    <Head :title="`${game?.name || 'Game'} - Play`" />

    <section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
        <a
            v-if="game?.slug"
            :href="route('game.details', { slug: game.slug })"
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
                        :class="backtrackLevel"
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
            v-if="solved"
            aria-live="polite"
            class="success-card mx-auto w-full max-w-md rounded-2xl border border-[var(--daily-play-accent)] bg-[var(--daily-play-surface)] p-6 text-center shadow-[var(--daily-play-shadow-lg)]"
        >
            <div
                class="check-badge mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[var(--daily-play-accent-soft)]"
            >
                <FontAwesomeIcon
                    icon="circle-check"
                    class="text-3xl text-[var(--daily-play-accent-active)]"
                />
            </div>

            <h2 class="mt-4 text-2xl font-bold tracking-tight text-[var(--daily-play-text)]">
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
</template>

<style scoped>
.success-card {
    animation: success-card-in 0.45s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

.check-badge {
    animation: check-pop 0.5s cubic-bezier(0.2, 1.4, 0.3, 1) 0.15s both;
}

.stat-item:nth-child(1) {
    animation: stat-in 0.4s ease-out 0.4s both;
}

.stat-item:nth-child(2) {
    animation: stat-in 0.4s ease-out 0.55s both;
}

@keyframes success-card-in {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.97);
    }

    to {
        opacity: 1;
        transform: none;
    }
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