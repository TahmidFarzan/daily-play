<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from '@/pages/layouts/PublicLayout.vue'

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
    <Head :title="`${game?.name || 'Game'} - Play`" />

    <section class="flex min-h-[60vh] w-full flex-col items-center justify-center space-y-6 text-center">
        <a
            v-if="game?.slug"
            :href="route('game.details', { slug: game.slug })"
            class="inline-flex items-center gap-2 text-sm font-medium text-[var(--daily-play-text-muted)] transition hover:text-[var(--daily-play-accent)]"
        >
            <FontAwesomeIcon icon="arrow-left" class="text-xs" />
            Back to details
        </a>

        <div class="rounded-2xl border border-dashed border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] p-10 shadow-sm">
            <FontAwesomeIcon
                icon="gamepad"
                class="mb-4 text-5xl text-[var(--daily-play-accent)]"
            />
            <h1 class="text-2xl font-bold text-[var(--daily-play-text)]">
                {{ game?.name || 'Game' }}
            </h1>
            <p class="mt-2 text-[var(--daily-play-text-muted)]">
                Gameplay is coming soon. Please check back later.
            </p>
        </div>
    </section>
</template>

<style scoped></style>
