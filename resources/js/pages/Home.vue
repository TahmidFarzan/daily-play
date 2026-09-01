<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/pages/layouts/PublicLayout.vue'
import ModelPagination from '@/components/common/pagination/Pagination.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'

import { computed } from 'vue'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faInfo, faGamepad } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faInfo, faGamepad)

defineOptions({
    layout: Layout,
})

const { games } = defineProps({
    games: { type: Object, default: null },
})

const paginationOnly = computed(() => {
    if (!games) return {}

    const { data, ...rest } = games

    return rest
})

const gameList = computed(() => games?.data ?? [])
</script>

<template>
    <Head title="Home" />

    <section class="w-full space-y-6">
        <header class="space-y-2 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-[var(--daily-play-text)] sm:text-4xl">
                Browse Games
            </h1>
            <p class="text-[var(--daily-play-text-muted)]">
                Pick a game to view its details or start playing.
            </p>
        </header>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <article
                v-for="game in gameList"
                :key="game.id"
                class="group flex flex-col overflow-hidden rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] shadow-sm transition duration-200 hover:shadow-lg"
            >
                <div class="aspect-[4/3] w-full overflow-hidden bg-gray-100">
                    <MediaRenderer
                        v-if="game?.logo"
                        :media="game.logo"
                        media-class="w-full h-full object-cover transition duration-200 group-hover:scale-105"
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center text-[var(--daily-play-text-muted)]"
                    >
                        <FontAwesomeIcon icon="gamepad" class="text-4xl" />
                    </div>
                </div>

                <div class="flex flex-1 flex-col gap-2 p-4">
                    <h2 class="font-semibold text-[var(--daily-play-text)]">
                        {{ game.name || 'Untitled Game' }}
                    </h2>

                    <p
                        class="line-clamp-2 flex-1 text-sm text-[var(--daily-play-text-muted)]"
                    >
                        {{ game.brief || 'No description available.' }}
                    </p>

                    <div class="mt-3 flex items-center gap-2">
                        <a
                            :href="route('games.details', { slug: game.slug })"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-[var(--daily-play-border)] px-3 py-2 text-sm font-medium text-[var(--daily-play-text)] transition hover:bg-gray-50"
                        >
                            <FontAwesomeIcon icon="info" class="text-xs" />
                            Details
                        </a>

                        <a
                            :href="route('games.play', { slug: game.slug })"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-[var(--daily-play-accent)] px-3 py-2 text-sm font-medium text-white transition hover:bg-[var(--daily-play-accent-hover)]"
                        >
                            <FontAwesomeIcon icon="gamepad" class="text-xs" />
                            Play
                        </a>
                    </div>
                </div>
            </article>
        </div>

        <div
            v-if="!gameList.length"
            class="rounded-2xl border border-dashed border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] p-10 text-center text-[var(--daily-play-text-muted)]"
        >
            <FontAwesomeIcon icon="gamepad" class="mb-3 text-4xl" />
            <p>No games available yet. Check back soon.</p>
        </div>

        <ModelPagination v-if="gameList.length" :pagination="paginationOnly" />
    </section>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
