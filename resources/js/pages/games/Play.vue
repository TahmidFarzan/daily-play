<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/pages/layouts/PublicLayout.vue'
import GameBoard from '@/components/games/GameBoard.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'
import { formatDate } from '@/composables/useDateTime'

import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft, faClock, faGamepad } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faArrowLeft, faClock, faGamepad)

defineOptions({
    layout: Layout,
})

const { dailyGame, serverNow } = defineProps({
    dailyGame: { type: Object},
    serverNow: { type: String, default: null },
})

const game = computed(() => dailyGame?.game ?? {})
const board = computed(() => dailyGame?.board ?? {})
const difficulty = computed(() => dailyGame?.gameDifficulty ?? null)

const endMs = computed(() =>
    dailyGame?.ends_at ? new Date(dailyGame.ends_at).getTime() : 0,
)

const clockOffsetMs = computed(() => {
    const serverMs = serverNow ? new Date(serverNow).getTime() : 0

    return serverMs ? serverMs - Date.now() : 0
})

const remainingSeconds = ref(0)
let tickerInterval = null

const computeRemainingSeconds = () => {
    if (!endMs.value) return 0

    return Math.max(0, Math.floor((endMs.value - clockOffsetMs.value - Date.now()) / 1000))
}

const updateRemaining = () => {
    remainingSeconds.value = computeRemainingSeconds()
}

const isExpired = computed(() => remainingSeconds.value <= 0)

const formattedTime = computed(() => {
    const total = remainingSeconds.value
    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const seconds = total % 60

    const pad = (value) => String(value).padStart(2, '0')

    if (hours > 0) {
        return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`
    }

    return `${pad(minutes)}:${pad(seconds)}`
})

const dailyDateLabel = computed(() =>
    dailyGame?.game_date ? formatDate(dailyGame.game_date) : '',
)

onMounted(() => {
    updateRemaining()

    tickerInterval = window.setInterval(updateRemaining, 1000)
})

onBeforeUnmount(() => {
    if (tickerInterval !== null) {
        window.clearInterval(tickerInterval)
    }
})
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
                            {{ difficulty?.name }}
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

            <div
                class="inline-flex items-center gap-2 self-start rounded-xl border border-[var(--daily-play-border)] bg-gray-50 px-4 py-2 sm:self-center"
            >
                <FontAwesomeIcon icon="clock" class="text-[var(--daily-play-text-muted)]" />
                <span
                    class="font-mono text-lg font-semibold tabular-nums text-[var(--daily-play-text)]"
                >
                    {{ formattedTime }}
                </span>
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
                :disabled="isExpired"
            />

            <div
                v-if="isExpired"
                class="absolute inset-0 z-10 flex items-center justify-center"
            >
                <div
                    class="rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] px-6 py-4 text-center shadow-lg"
                >
                    <p class="font-semibold text-[var(--daily-play-text)]">Time's Up!</p>
                    <p class="mt-1 text-sm text-[var(--daily-play-text-muted)]">
                        This daily puzzle has ended. Come back tomorrow for a new one.
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
