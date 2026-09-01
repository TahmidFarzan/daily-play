<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/pages/layouts/PublicLayout.vue'
import GameBoard from '@/components/games/GameBoard.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'
import { formatDate, formatTime } from '@/composables/useDateTime'
import { formatSolveDuration, useSolveTimer } from '@/composables/useSolveTimer'

import { computed, ref } from 'vue'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft, faCircleCheck, faGamepad, faStopwatch } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faArrowLeft, faCircleCheck, faGamepad, faStopwatch)

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

const { elapsedSeconds, stop: stopTimer } = useSolveTimer()

const solved = ref(false)
const finalSolveTime = ref(null)

const formattedElapsed = computed(() => formatSolveDuration(elapsedSeconds.value))

const handleSolved = () => {
    if (solved.value) return

    solved.value = true
    finalSolveTime.value = formatSolveDuration(elapsedSeconds.value)
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
            />
        </div>

        <div
            v-if="solved"
            class="inline-flex items-center gap-2 self-center rounded-2xl border border-[var(--daily-play-accent)] bg-[var(--daily-play-accent-soft)] px-5 py-3 shadow-sm"
        >
            <FontAwesomeIcon
                icon="circle-check"
                class="text-xl text-[var(--daily-play-accent-active)]"
            />
            <p class="font-semibold text-[var(--daily-play-text)]">
                Solved!
                <span class="ml-1 font-normal text-[var(--daily-play-text-muted)]">
                    Time needed
                    <span class="font-semibold text-[var(--daily-play-accent-active)]">
                        {{ finalSolveTime }}
                    </span>
                </span>
            </p>
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
