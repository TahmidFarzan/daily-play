<script setup>
import { computed } from 'vue'

import ZipBoard from '@/components/games/zip/ZipBoard.vue'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faGamepad } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faGamepad)

const { game, board, disabled } = defineProps({
    game: { type: Object, default: null },
    board: { type: Object, default: null },
    disabled: { type: Boolean, default: false },
})

const boardComponents = {
    zip: ZipBoard,
}

const resolvedComponent = computed(
    () => boardComponents[game?.slug] ?? null,
)
</script>

<template>
    <component
        :is="resolvedComponent"
        v-if="resolvedComponent"
        :game="game"
        :board="board"
        :disabled="disabled"
    />

    <div
        v-else
        class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] p-10 text-center"
    >
        <FontAwesomeIcon
            icon="gamepad"
            class="text-4xl text-[var(--daily-play-text-muted)]"
        />
        <p class="text-sm text-[var(--daily-play-text-muted)]">
            The board for this game isn't available yet. Check back soon.
        </p>
    </div>
</template>