<script setup>
import { computed, onMounted, ref } from 'vue'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faRotateLeft, faCircleCheck } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faRotateLeft, faCircleCheck)

const { board, disabled } = defineProps({
    board: { type: Object, default: null },
    disabled: { type: Boolean, default: false },
})

const gridStyle = computed(() => {
    const cols = board?.cols || 0

    return {
        gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))`,
    }
})

const cellKey = (row, col) => `${row}:${col}`

const clueMap = computed(() => {
    const map = {}

    for (const clue of board?.clues || []) {
        map[cellKey(clue.row, clue.col)] = clue.number
    }

    return map
})

const cells = computed(() => {
    const rows = board?.rows || 0
    const cols = board?.cols || 0
    const list = []

    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            list.push({
                row,
                col,
                clue: clueMap.value[cellKey(row, col)] ?? null,
            })
        }
    }

    return list
})

const startKey = computed(() => {
    const startClue = (board?.clues || []).find((clue) => clue.number === 1)

    return startClue ? cellKey(startClue.row, startClue.col) : null
})

const pathKeys = ref([])

const pathSet = computed(() => new Set(pathKeys.value))
const tipKey = computed(() => pathKeys.value[pathKeys.value.length - 1] ?? null)

const totalCells = computed(() => (board?.rows || 0) * (board?.cols || 0))
const isComplete = computed(() => pathKeys.value.length === totalCells.value)

const isAdjacent = (row, col) => {
    if (!tipKey.value) return false

    const [tipRow, tipCol] = tipKey.value.split(':').map(Number)

    return Math.abs(row - tipRow) + Math.abs(col - tipCol) === 1
}

const handleCellTap = (cell) => {
    if (disabled || isComplete.value) return

    const key = cellKey(cell.row, cell.col)

    if (pathSet.value.has(key)) return

    if (!isAdjacent(cell.row, cell.col)) return

    pathKeys.value.push(key)
}

const resetPath = () => {
    if (disabled) return

    pathKeys.value = startKey.value ? [startKey.value] : []
}

const cellClass = (cell) => {
    const key = cellKey(cell.row, cell.col)
    const base = 'flex aspect-square items-center justify-center rounded-md border text-sm font-semibold transition sm:text-base'

    if (cell.clue !== null) {
        return `${base} border-[var(--daily-play-accent)] bg-[var(--daily-play-accent)] text-white shadow-sm`
    }

    if (key === tipKey.value) {
        return `${base} border-[var(--daily-play-accent)] bg-[var(--daily-play-accent-soft)] text-[var(--daily-play-accent-active)]`
    }

    if (pathSet.value.has(key)) {
        return `${base} border-[var(--daily-play-border)] bg-[var(--daily-play-accent-soft)]`
    }

    return `${base} border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] text-[var(--daily-play-text-muted)] hover:border-[var(--daily-play-accent)]`
}

onMounted(() => {
    resetPath()
})
</script>

<template>
    <div class="flex w-full flex-col items-center gap-4">
        <div
            class="w-full max-w-[26rem]"
            :class="{ 'pointer-events-none opacity-60': disabled }"
        >
            <div
                class="grid gap-1 sm:gap-1.5"
                :style="gridStyle"
            >
                <button
                    v-for="cell in cells"
                    :key="cellKey(cell.row, cell.col)"
                    type="button"
                    :class="cellClass(cell)"
                    :aria-label="
                        cell.clue !== null
                            ? `Clue ${cell.clue}`
                            : `Cell row ${cell.row + 1}, column ${cell.col + 1}`
                    "
                    @click="handleCellTap(cell)"
                >
                    {{ cell.clue ?? '' }}
                </button>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                :disabled="disabled || pathKeys.length <= 1"
                class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--daily-play-border)] px-3 py-1.5 text-sm font-medium text-[var(--daily-play-text-muted)] transition hover:text-[var(--daily-play-accent)] disabled:opacity-50"
                @click="resetPath"
            >
                <FontAwesomeIcon icon="rotate-left" class="text-xs" />
                Reset
            </button>
        </div>

        <p
            v-if="!isComplete"
            class="text-center text-sm text-[var(--daily-play-text-muted)]"
        >
            Start at <span class="font-semibold text-[var(--daily-play-accent-active)]">1</span>
            and tap the next cell in order, without revisiting a cell.
        </p>

        <p
            v-else
            class="inline-flex items-center gap-1.5 font-medium text-[var(--daily-play-accent-active)]"
        >
            <FontAwesomeIcon icon="circle-check" />
            Board complete!
        </p>
    </div>
</template>