<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCircleCheck } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faCircleCheck)

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

const totalCells = computed(() => rows.value * cols.value)

const startCell = computed(() => {
    const startClue = (board?.clues || []).find((clue) => clue.number === 1)

    return startClue ? { row: startClue.row, col: startClue.col } : null
})

const goalKey = computed(() => {
    let highest = null

    for (const clue of board?.clues || []) {
        if (!highest || clue.number > highest.number) highest = clue
    }

    return highest ? cellKey(highest.row, highest.col) : null
})

const path = ref([])
const currentCell = ref(null)
const isDragging = ref(false)
const complete = ref(false)
const backtrackCount = ref(0)

let activePointerId = null
let backtrackingInGesture = false

const boardEl = ref(null)

const pathIndex = (row, col) => path.value.findIndex((cell) => cell.row === row && cell.col === col)

const nextClue = computed(() => {
    let highest = 0

    for (const cell of path.value) {
        const clue = clueMap.value[cellKey(cell.row, cell.col)]

        if (clue) highest = Math.max(highest, clue)
    }

    return highest + 1
})

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

const isAdjacent = (fromRow, fromCol, toRow, toCol) =>
    Math.abs(toRow - fromRow) + Math.abs(toCol - fromCol) === 1

const finish = () => {
    complete.value = true
    isDragging.value = false
    activePointerId = null

    emit('completed', {
        path: path.value.map((cell) => ({ ...cell })),
        backtrackCount: backtrackCount.value,
    })
}

const moveTo = (row, col) => {
    if (complete.value || !currentCell.value) return false

    if (currentCell.value.row === row && currentCell.value.col === col) return false

    const index = pathIndex(row, col)

    if (index !== -1) {
        path.value.splice(index + 1)
        currentCell.value = { row, col }

        if (!backtrackingInGesture) {
            backtrackCount.value += 1
            backtrackingInGesture = true
        }

        return true
    }

    if (!isAdjacent(currentCell.value.row, currentCell.value.col, row, col)) return false

    if (blocksMove(currentCell.value.row, currentCell.value.col, row, col)) return false

    const clue = clueMap.value[cellKey(row, col)]

    if (clue && clue !== nextClue.value) return false

    path.value.push({ row, col })
    currentCell.value = { row, col }
    backtrackingInGesture = false

    if (path.value.length === totalCells.value) {
        finish()
    }

    return true
}

const stepToward = (target) => {
    const head = currentCell.value

    if (!head || (head.row === target.row && head.col === target.col)) return null

    const deltaRow = target.row - head.row
    const deltaCol = target.col - head.col

    const horizontal = deltaCol !== 0 ? { row: head.row, col: head.col + Math.sign(deltaCol) } : null
    const vertical = deltaRow !== 0 ? { row: head.row + Math.sign(deltaRow), col: head.col } : null
    const candidates = [horizontal, vertical].filter(Boolean)

    const inPath = candidates.find((cell) => pathIndex(cell.row, cell.col) !== -1)

    if (inPath) return inPath

    if (Math.abs(deltaRow) > Math.abs(deltaCol)) return vertical ?? horizontal

    return horizontal ?? vertical
}

const traceToward = (target) => {
    let guard = totalCells.value

    while (guard > 0) {
        if (complete.value) return

        const step = stepToward(target)

        if (!step) return

        if (!moveTo(step.row, step.col)) return

        guard -= 1
    }
}

const onPointerDown = (event) => {
    if (disabled || complete.value || isDragging.value) return

    const cell = cellFromPoint(event.clientX, event.clientY)

    if (!cell || !startCell.value) return

    if (cell.row !== startCell.value.row || cell.col !== startCell.value.col) return

    isDragging.value = true
    activePointerId = event.pointerId
    backtrackingInGesture = false
    path.value = [{ row: startCell.value.row, col: startCell.value.col }]
    currentCell.value = { row: startCell.value.row, col: startCell.value.col }

    boardEl.value?.setPointerCapture?.(event.pointerId)
    event.preventDefault()
}

const onPointerMove = (event) => {
    if (!isDragging.value || event.pointerId !== activePointerId) return

    const target = cellFromPoint(event.clientX, event.clientY)

    if (target) {
        traceToward(target)
    }
}

const onPointerEnd = (event) => {
    if (!isDragging.value || event.pointerId !== activePointerId) return

    isDragging.value = false
    activePointerId = null
}

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

const step = computed(() => metrics.cell + metrics.gap)

const svgWidth = computed(() => cols.value * metrics.cell + (cols.value - 1) * metrics.gap + 2)
const svgHeight = computed(() => rows.value * metrics.cell + (rows.value - 1) * metrics.gap + 2)
const svgViewBox = computed(() => `0 0 ${svgWidth.value} ${svgHeight.value}`)

const pointFor = (row, col) => {
    const point = {
        x: 1 + col * step.value + metrics.cell / 2,
        y: 1 + row * step.value + metrics.cell / 2,
    }

    return point
}

const linePoints = computed(() => path.value.map(
    (cell) => {
        const point = pointFor(cell.row, cell.col)

        return `${point.x},${point.y}`
    },
).join(' '))

const nodeRadius = computed(() => metrics.cell * 0.13)
const headRadius = computed(() => metrics.cell * 0.2)

const cellClass = (cell) => {
    const base = 'relative flex aspect-square items-center justify-center rounded-[4px] bg-[var(--daily-play-surface)] text-sm font-semibold transition-colors sm:text-base'

    if (cell.clue !== null) {
        return `${base} text-[var(--daily-play-accent-active)]`
    }

    return `${base} text-[var(--daily-play-text-muted)]`
}

const cellEmphasis = (cell) => {
    if (cellKey(cell.row, cell.col) === goalKey.value) {
        return 'bg-[var(--daily-play-accent-soft)] inset-ring-2 inset-ring-[var(--daily-play-accent)]'
    }

    if (startCell.value && cellKey(cell.row, cell.col) === cellKey(startCell.value.row, startCell.value.col)) {
        return 'bg-[var(--daily-play-accent-soft)]'
    }

    return ''
}

const wallStyle = (wall) => {
    const stepX = metrics.cell + metrics.gap

    if (wall.direction === 'right') {
        return {
            left: `${(wall.col + 1) * stepX - metrics.gap / 2}px`,
            top: `${wall.row * stepX + 1}px`,
            height: `${metrics.cell - 2}px`,
            width: '4px',
            transform: 'translateX(-50%)',
        }
    }

    return {
        top: `${(wall.row + 1) * stepX - metrics.gap / 2}px`,
        left: `${wall.col * stepX + 1}px`,
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
                role="grid"
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
                    role="gridcell"
                    :class="[cellClass(cell), cellEmphasis(cell)]"
                    :aria-label="
                        cell.clue !== null
                            ? `Clue ${cell.clue}`
                            : `Cell row ${cell.row + 1}, column ${cell.col + 1}`
                    "
                >
                    {{ cell.clue ?? '' }}
                </div>

                <svg
                    class="pointer-events-none absolute inset-0 z-10 h-full w-full"
                    aria-hidden="true"
                    :viewBox="svgViewBox"
                >
                    <polyline
                        v-if="path.length > 1"
                        :points="linePoints"
                        fill="none"
                        style="stroke: var(--daily-play-accent); stroke-width: 5; stroke-linecap: round; stroke-linejoin: round;"
                        vector-effect="non-scaling-stroke"
                    />

                    <circle
                        v-for="(cell, index) in path"
                        :key="`node-${index}`"
                        :cx="pointFor(cell.row, cell.col).x"
                        :cy="pointFor(cell.row, cell.col).y"
                        :r="nodeRadius"
                        fill="var(--daily-play-accent)"
                    />

                    <circle
                        v-if="currentCell"
                        :cx="pointFor(currentCell.row, currentCell.col).x"
                        :cy="pointFor(currentCell.row, currentCell.col).y"
                        :r="headRadius"
                        fill="var(--daily-play-surface)"
                        stroke="var(--daily-play-accent-active)"
                        stroke-width="3"
                        vector-effect="non-scaling-stroke"
                    />
                </svg>

                <div
                    v-for="(wall, index) in walls"
                    :key="`wall-${index}`"
                    class="absolute z-20 rounded-full bg-[var(--daily-play-text-muted)]"
                    :style="wallStyle(wall)"
                />
            </div>
        </div>

        <p
            v-if="!complete"
            class="text-center text-sm text-[var(--daily-play-text-muted)]"
        >
            Start at <span class="font-semibold text-[var(--daily-play-accent-active)]">1</span>
            and press to trace the route in order.
            Drag back over your line to backtrack.
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