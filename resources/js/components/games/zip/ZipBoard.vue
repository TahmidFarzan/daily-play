<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCircleCheck } from '@fortawesome/free-solid-svg-icons'

import { useZipPlay } from '@/composables/useZipPlay'
import { segmentColorAt } from '@/composables/progressColors'

FontAwesomeLibrary.add(faCircleCheck)

const emit = defineEmits(['completed', 'backtrack-count'])

const { board, disabled } = defineProps({
    board: { type: Object, default: null },
    disabled: { type: Boolean, default: false },
})

const boardSource = computed(() => board)

const {
    rows,
    cols,
    cellKey,
    clueMap,
    walls,
    startCell,
    goalKey,
    path,
    currentCell,
    complete,
    backtrackCount,
    canStartDragAt,
    startDrag,
    traceToward,
} = useZipPlay(boardSource)

const isDragging = ref(false)
let activePointerId = null

const gridGap = 4

const gridStyle = computed(() => ({
    gridTemplateColumns: `repeat(${cols.value}, minmax(0, 1fr))`,
    gap: `${gridGap}px`,
}))

const metrics = reactive({ cell: 0, gap: gridGap })

const cells = computed(() => {
    const list = []

    for (let row = 0; row < rows.value; row++) {
        for (let col = 0; col < cols.value; col++) {
            list.push({
                row,
                col,
                clue: clueMap.value[cellKey(row, col)] ?? null,
            })
        }
    }

    return list
})

const boardEl = ref(null)

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

watch(backtrackCount, (count) => {
    emit('backtrack-count', count)
})

watch(complete, (done) => {
    if (done) {
        emit('completed', {
            path: path.value.map((cell) => ({ ...cell })),
            backtrackCount: backtrackCount.value,
        })
    }
})

const finishDrag = (event) => {
    if (!isDragging.value || event.pointerId !== activePointerId) return

    isDragging.value = false
    activePointerId = null

    if (boardEl.value?.hasPointerCapture?.(event.pointerId)) {
        boardEl.value.releasePointerCapture(event.pointerId)
    }
}

const onPointerDown = (event) => {
    if (disabled || isDragging.value) return

    const cell = cellFromPoint(event.clientX, event.clientY)

    if (!cell) return

    if (!startDrag(cell)) return

    isDragging.value = true
    activePointerId = event.pointerId

    if (boardEl.value?.setPointerCapture) {
        boardEl.value.setPointerCapture(event.pointerId)
    }

    event.preventDefault()
}

const onPointerMove = (event) => {
    if (!isDragging.value || event.pointerId !== activePointerId) return

    const target = cellFromPoint(event.clientX, event.clientY)

    if (target) {
        traceToward(target)
    }
}

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

const segments = computed(() => path.value.slice(0, -1).map((cell, index) => {
    const next = path.value[index + 1]

    return {
        from: pointFor(cell.row, cell.col),
        to: pointFor(next.row, next.col),
        color: segmentColorAt(index),
    }
}))

const glowColor = computed(() => (segments.value.length > 0
    ? segments.value[segments.value.length - 1].color
    : 'var(--daily-play-accent)'))

const nodeRadius = computed(() => metrics.cell * 0.13)
const headRadius = computed(() => metrics.cell * 0.2)

const markerRadius = computed(() => Math.max(0, metrics.cell * 0.32))
const markerFontSize = computed(() => Math.max(11, Math.round(metrics.cell * 0.3)))

const markers = computed(() => cells.value
    .filter((cell) => cell.clue !== null)
    .map((cell) => {
        const index = path.value.findIndex(
            (pathCell) => pathCell.row === cell.row && pathCell.col === cell.col,
        )

        return {
            number: cell.clue,
            cx: pointFor(cell.row, cell.col).x,
            cy: pointFor(cell.row, cell.col).y,
            radius: markerRadius.value,
            font: markerFontSize.value,
            completed: index !== -1,
            color: segmentColorAt(Math.max(0, index)),
        }
    }))

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
                @pointerup="finishDrag"
                @pointercancel="finishDrag"
                @pointerleave="finishDrag"
                @lostpointercapture="finishDrag"
                @contextmenu.prevent="() => {}"
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
                />

                <svg
                    class="pointer-events-none absolute inset-0 z-10 h-full w-full overflow-hidden rounded-lg"
                    aria-hidden="true"
                    :viewBox="svgViewBox"
                >
                    <g
                        v-if="path.length > 1"
                        :class="{ 'path-complete': complete }"
                        :style="{ '--path-glow-color': glowColor }"
                    >
                        <line
                            v-for="(segment, index) in segments"
                            :key="`segment-${index}`"
                            :x1="segment.from.x"
                            :y1="segment.from.y"
                            :x2="segment.to.x"
                            :y2="segment.to.y"
                            :stroke="segment.color"
                            stroke-width="5"
                            stroke-linecap="round"
                            vector-effect="non-scaling-stroke"
                        />
                    </g>

                    <circle
                        v-for="(cell, index) in path"
                        :key="`node-${index}`"
                        :cx="pointFor(cell.row, cell.col).x"
                        :cy="pointFor(cell.row, cell.col).y"
                        :r="nodeRadius"
                        fill="var(--daily-play-accent-active)"
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

                    <g
                        v-for="marker in markers"
                        :key="`marker-${marker.number}`"
                        :transform="`translate(${marker.cx} ${marker.cy})`"
                    >
                        <g
                            class="marker-marker"
                            :class="{ 'marker-pop': marker.completed }"
                            :style="{ '--marker-color': marker.color }"
                        >
                            <circle
                                :r="marker.radius"
                                :fill="marker.completed ? marker.color : 'var(--daily-play-surface)'"
                                :stroke="marker.completed ? marker.color : 'var(--daily-play-accent-active)'"
                                stroke-width="2"
                                vector-effect="non-scaling-stroke"
                            />
                            <text
                                text-anchor="middle"
                                dominant-baseline="central"
                                :font-size="marker.font"
                                :fill="marker.completed ? '#FFFFFF' : 'var(--daily-play-accent-active)'"
                            >
                                {{ marker.number }}
                            </text>
                        </g>
                    </g>
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
            and press to trace the route in order. Drag back over your line to backtrack.
            You can release and continue from the endpoint at any time.
        </p>

        <p
            v-else
            class="inline-flex items-center gap-1.5 font-medium text-[var(--daily-play-accent-active)]"
        >
            <FontAwesomeIcon icon="circle-check" />
            Challange complete!
        </p>
    </div>
</template>

<style scoped>
.path-complete {
    animation: path-complete-pulse 0.7s ease-out;
    filter: drop-shadow(0 0 6px var(--path-glow-color));
}

@keyframes path-complete-pulse {
    0% {
        filter: drop-shadow(0 0 0 var(--path-glow-color));
    }

    50% {
        filter: drop-shadow(0 0 10px var(--path-glow-color));
    }

    100% {
        filter: drop-shadow(0 0 6px var(--path-glow-color));
    }
}

.marker-marker {
    transform-box: fill-box;
    transform-origin: 50% 50%;
}

.marker-pop {
    animation: marker-pop 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes marker-pop {
    0% {
        transform: scale(0.82);
        filter: drop-shadow(0 0 0 var(--marker-color));
    }

    60% {
        transform: scale(1.12);
        filter: drop-shadow(0 0 6px var(--marker-color));
    }

    100% {
        transform: scale(1);
        filter: drop-shadow(0 0 3px var(--marker-color));
    }
}

@media (prefers-reduced-motion: reduce) {
    .marker-pop {
        animation: none;
    }
}
</style>
