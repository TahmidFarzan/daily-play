<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCircleCheck, faRotateLeft } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faCircleCheck, faRotateLeft)

const emit = defineEmits(['completed'])

const { board, disabled } = defineProps({
    board: { type: Object, default: null },
    disabled: { type: Boolean, default: false },
})

const rows = computed(() => board?.rows || 0)
const cols = computed(() => board?.cols || 0)

const gridGap = 4

const gridStyle = computed(() => ({
    gridTemplateColumns: `repeat(${cols.value}, minmax(0, 1fr))`,
    gap: `${gridGap}px`,
}))

const metrics = reactive({ cell: 0, gap: gridGap })

const cellKey = (row, col) => `${row}:${col}`

const clueMap = computed(() => {
    const map = {}

    for (const clue of board?.clues || []) {
        map[cellKey(clue.row, clue.col)] = clue.number
    }

    return map
})

const walls = computed(() => board?.walls || [])

const wallSet = computed(() => new Set(walls.value.map(
    (wall) => `${wall.row}:${wall.col}:${wall.direction}`,
)))

const cells = computed(() => {
    const list = []

    for (let row = 0; row < rows.value; row++) {
        for (let col = 0; col < cols.value; col++) {
            const key = cellKey(row, col)

            list.push({
                row,
                col,
                clue: clueMap.value[key] ?? null,
            })
        }
    }

    return list
})

const startKey = computed(() => {
    const startClue = (board?.clues || []).find((clue) => clue.number === 1)

    return startClue ? cellKey(startClue.row, startClue.col) : null
})

const totalCells = computed(() => rows.value * cols.value)

const pathKeys = ref([])
const dragging = ref(false)
const activePointerId = ref(null)
const complete = ref(false)

let nextClueNumber = 2

const pathSet = computed(() => new Set(pathKeys.value))
const tipKey = computed(() => pathKeys.value[pathKeys.value.length - 1] ?? null)

const cellFromPoint = (clientX, clientY) => {
    const el = boardEl.value

    if (!el || !metrics.cell) return null

    const rect = el.getBoundingClientRect()
    const step = metrics.cell + metrics.gap

    const col = Math.floor((clientX - rect.left) / step)
    const row = Math.floor((clientY - rect.top) / step)

    if (row < 0 || row >= rows.value || col < 0 || col >= cols.value) return null

    return { row, col }
}

const blocksMove = (fromRow, fromCol, toRow, toCol) => {
    const deltaRow = toRow - fromRow
    const deltaCol = toCol - fromCol

    let wallKey = null

    if (deltaRow === 0 && deltaCol === 1) {
        wallKey = `${fromRow}:${fromCol}:right`
    } else if (deltaRow === 0 && deltaCol === -1) {
        wallKey = `${toRow}:${toCol}:right`
    } else if (deltaRow === 1 && deltaCol === 0) {
        wallKey = `${fromRow}:${fromCol}:down`
    } else if (deltaRow === -1 && deltaCol === 0) {
        wallKey = `${toRow}:${toCol}:down`
    }

    return wallKey !== null && wallSet.value.has(wallKey)
}

const canMove = (toRow, toCol) => {
    const [fromRow, fromCol] = tipKey.value.split(':').map(Number)

    if (toRow < 0 || toRow >= rows.value || toCol < 0 || toCol >= cols.value) return false

    if (pathSet.value.has(cellKey(toRow, toCol))) return false

    if (Math.abs(toRow - fromRow) + Math.abs(toCol - fromCol) !== 1) return false

    if (blocksMove(fromRow, fromCol, toRow, toCol)) return false

    const clue = clueMap.value[cellKey(toRow, toCol)]

    return clue === null || clue === undefined || clue === nextClueNumber
}

const append = (row, col) => {
    pathKeys.value.push(cellKey(row, col))

    const clue = clueMap.value[cellKey(row, col)]

    if (clue === nextClueNumber) {
        nextClueNumber += 1
    }

    if (pathKeys.value.length === totalCells.value) {
        complete.value = true
        dragging.value = false
        activePointerId.value = null
        emit('completed')
    }
}

const extendTo = (target) => {
    let guard = totalCells.value

    while (guard > 0) {
        const [tipRow, tipCol] = tipKey.value.split(':').map(Number)

        if (tipRow === target.row && tipCol === target.col) break

        const deltaCol = target.col - tipCol
        const deltaRow = target.row - tipRow

        let nextRow = tipRow
        let nextCol = tipCol

        if (deltaCol !== 0) {
            nextCol = tipCol + Math.sign(deltaCol)
        } else if (deltaRow !== 0) {
            nextRow = tipRow + Math.sign(deltaRow)
        } else {
            break
        }

        if (!canMove(nextRow, nextCol)) break

        append(nextRow, nextCol)
        guard -= 1
    }
}

const onPointerDown = (event) => {
    if (disabled || complete.value || dragging.value) return

    const cell = cellFromPoint(event.clientX, event.clientY)

    if (!cell || cellKey(cell.row, cell.col) !== startKey.value) return

    dragging.value = true
    activePointerId.value = event.pointerId
    pathKeys.value = [startKey.value]
    nextClueNumber = 2

    boardEl.value?.setPointerCapture?.(event.pointerId)
    event.preventDefault()
}

const onPointerMove = (event) => {
    if (!dragging.value || event.pointerId !== activePointerId.value) return

    const target = cellFromPoint(event.clientX, event.clientY)

    if (target) {
        extendTo(target)
    }
}

const onPointerEnd = (event) => {
    if (!dragging.value || event.pointerId !== activePointerId.value) return

    dragging.value = false
    activePointerId.value = null
}

const resetPath = () => {
    if (disabled || complete.value) return

    pathKeys.value = startKey.value ? [startKey.value] : []
    nextClueNumber = 2
}

const boardEl = ref(null)

const computeMetrics = () => {
    if (!boardEl.value || cols.value <= 0) return

    metrics.cell = Math.max(0, (boardEl.value.clientWidth - gridGap * (cols.value - 1)) / cols.value)
}

const onResize = () => {
    computeMetrics()
}

onMounted(() => {
    computeMetrics()
    window.addEventListener('resize', onResize)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', onResize)
})

const cellClass = (cell) => {
    const key = cellKey(cell.row, cell.col)
    const base = 'relative flex aspect-square items-center justify-center rounded-[4px] text-sm font-semibold transition-colors sm:text-base'

    if (cell.clue !== null) {
        return `${base} bg-[var(--daily-play-accent)] text-white`
    }

    if (key === tipKey.value) {
        return `${base} bg-[var(--daily-play-accent-soft)] text-[var(--daily-play-accent-active)] ring-2 ring-inset ring-[var(--daily-play-accent)]`
    }

    if (key === startKey.value) {
        return `${base} bg-[var(--daily-play-accent-soft)] text-[var(--daily-play-accent-active)]`
    }

    if (pathSet.value.has(key)) {
        return `${base} bg-[var(--daily-play-accent-soft)] text-[var(--daily-play-accent-active)]`
    }

    return `${base} bg-[var(--daily-play-surface)] text-[var(--daily-play-text-muted)]`
}

const wallStyle = (wall) => {
    const step = metrics.cell + metrics.gap

    if (wall.direction === 'right') {
        return {
            left: `${(wall.col + 1) * step - metrics.gap / 2}px`,
            top: `${wall.row * step + 1}px`,
            height: `${metrics.cell - 2}px`,
            width: '4px',
            transform: 'translateX(-50%)',
        }
    }

    return {
        top: `${(wall.row + 1) * step - metrics.gap / 2}px`,
        left: `${wall.col * step + 1}px`,
        width: `${metrics.cell - 2}px`,
        height: '4px',
        transform: 'translateY(-50%)',
    }
}
</script>

<template>
    <div class="flex w-full flex-col items-center gap-4">
        <div class="w-full max-w-[26rem]">
            <div
                ref="boardEl"
                class="relative grid touch-none select-none overflow-hidden rounded-lg border border-[var(--daily-play-border)] bg-[var(--daily-play-border)]"
                :style="gridStyle"
                @pointerdown="onPointerDown"
                @pointermove="onPointerMove"
                @pointerup="onPointerEnd"
                @pointercancel="onPointerEnd"
            >
                <div
                    v-for="cell in cells"
                    :key="cellKey(cell.row, cell.col)"
                    :class="cellClass(cell)"
                    :aria-label="
                        cell.clue !== null
                            ? `Clue ${cell.clue}`
                            : `Cell row ${cell.row + 1}, column ${cell.col + 1}`
                    "
                >
                    {{ cell.clue ?? '' }}
                </div>

                <div
                    v-for="(wall, index) in walls"
                    :key="`wall-${index}`"
                    class="absolute z-10 rounded-full bg-[var(--daily-play-text-muted)]"
                    :style="wallStyle(wall)"
                />
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                :disabled="disabled || complete || pathKeys.length <= 1"
                class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--daily-play-border)] px-3 py-1.5 text-sm font-medium text-[var(--daily-play-text-muted)] transition hover:text-[var(--daily-play-accent)] disabled:opacity-50"
                @click="resetPath"
            >
                <FontAwesomeIcon icon="rotate-left" class="text-xs" />
                Reset
            </button>
        </div>

        <p
            v-if="!complete"
            class="text-center text-sm text-[var(--daily-play-text-muted)]"
        >
            Start at <span class="font-semibold text-[var(--daily-play-accent-active)]">1</span>
            and drag through every cell without lifting, without revisiting a cell, and without crossing a wall.
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