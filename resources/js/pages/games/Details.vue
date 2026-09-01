<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/pages/layouts/PublicLayout.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft, faGamepad } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faArrowLeft, faGamepad)

defineOptions({
    layout: Layout,
})

const { game } = defineProps({
    game: { type: Object, default: null },
})
</script>

<template>
    <Head :title="game?.name || 'Game Details'" />

    <section class="w-full space-y-6">
        <a
            :href="route('home')"
            class="inline-flex items-center gap-2 text-sm font-medium text-[var(--daily-play-text-muted)] transition hover:text-[var(--daily-play-accent)]"
        >
            <FontAwesomeIcon icon="arrow-left" class="text-xs" />
            Back to games
        </a>

        <div
            class="grid grid-cols-1 gap-6 overflow-hidden rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] p-6 shadow-sm lg:grid-cols-2"
        >
            <div class="flex h-full w-full items-center justify-center bg-gray-100 rounded-2xl overflow-hidden">
                <MediaRenderer
                    v-if="game?.logo"
                    :media="game.logo"
                    media-class="w-full h-full object-contain p-2"
                />
                <FontAwesomeIcon
                    v-else
                    icon="gamepad"
                    class="text-6xl text-[var(--daily-play-text-muted)]"
                />
            </div>

            <div class="flex flex-col gap-4">
                <h1 class="text-2xl font-bold tracking-tight text-[var(--daily-play-text)] sm:text-3xl">
                    {{ game?.name || 'Untitled Game' }}
                </h1>

                <p
                    v-if="game?.brief"
                    class="text-[var(--daily-play-text-muted)]"
                >
                    {{ game.brief }}
                </p>

                <div
                    v-if="game?.how_to_play"
                    class="rounded-xl border border-[var(--daily-play-border)] bg-gray-50 p-4"
                >
                    <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-[var(--daily-play-text-muted)]">
                        How to Play
                    </h2>
                    <p class="whitespace-pre-line text-[var(--daily-play-text)]">
                        {{ game.how_to_play }}
                    </p>
                </div>

                <a
                    v-if="game?.slug"
                    :href="route('game.play', { slug: game.slug })"
                    class="mt-auto inline-flex items-center justify-center gap-2 rounded-xl bg-[var(--daily-play-accent)] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[var(--daily-play-accent-hover)]"
                >
                    <FontAwesomeIcon icon="gamepad" />
                    Play {{ game?.name || 'Game' }}
                </a>
            </div>
        </div>
    </section>
</template>

<style scoped></style>
