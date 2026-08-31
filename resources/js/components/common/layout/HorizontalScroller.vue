<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount, watch } from "vue"

const {
    loading = false,
    threshold = 80,
    watchKey = '',
    trackClass = 'absolute left-0 right-0 bottom-0 h-3 cursor-pointer flex items-center',
    railClass = 'relative h-[2px] w-full rounded-full bg-white/10',
    thumbClass = 'absolute top-1/2 -translate-y-1/2 h-[2px] cursor-grab rounded-full bg-white/40 transition-colors hover:bg-white/70 active:cursor-grabbing',
} = defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
    threshold: {
        type: Number,
        default: 80,
    },
    watchKey: {
        type: [String, Number, Boolean],
        default: '',
    },
    trackClass: {
        type: String,
        default: 'absolute left-0 right-0 bottom-0 h-3 cursor-pointer flex items-center',
    },
    railClass: {
        type: String,
        default: 'relative h-[2px] w-full rounded-full bg-white/10',
    },
    thumbClass: {
        type: String,
        default: 'absolute top-1/2 -translate-y-1/2 h-[2px] cursor-grab rounded-full bg-white/40 transition-colors hover:bg-white/70 active:cursor-grabbing',
    },
})

const emit = defineEmits(['reach-end'])

const scrollRef = ref(null)
const trackRef = ref(null)
const thumbWidth = ref(0)
const thumbLeft = ref(0)
const dragging = ref(false)
const dragStartX = ref(0)
const dragStartLeft = ref(0)

let resizeObserver = null

const updateScrollbar = () => {
    const el = scrollRef.value

    if (!el) return

    const clientWidth = el.clientWidth
    const scrollWidth = el.scrollWidth
    const scrollLeft = el.scrollLeft

    if (scrollWidth <= clientWidth) {
        thumbWidth.value = 0
        thumbLeft.value = 0
        return
    }

    const trackWidth = trackRef.value?.clientWidth || clientWidth
    const newThumbWidth = Math.max((clientWidth / scrollWidth) * trackWidth, 32)
    const maxThumbLeft = trackWidth - newThumbWidth
    const maxScrollLeft = scrollWidth - clientWidth

    thumbWidth.value = newThumbWidth
    thumbLeft.value = (scrollLeft / maxScrollLeft) * maxThumbLeft
}

const checkReachEnd = () => {
    const el = scrollRef.value

    if (!el || loading) return

    const almostEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - threshold

    if (almostEnd) {
        emit('reach-end')
    }
}

const handleScroll = () => {
    updateScrollbar()
    checkReachEnd()
}

const handleWheel = (event) => {
    const el = scrollRef.value

    if (!el) return

    const rawDelta = Math.abs(event.deltaX) > Math.abs(event.deltaY)
        ? event.deltaX
        : event.deltaY

    if (!rawDelta) return

    const delta = event.deltaMode === 1
        ? rawDelta * 16
        : rawDelta

    event.preventDefault()
    event.stopPropagation()

    el.scrollLeft += delta

    updateScrollbar()
    checkReachEnd()
}

const scrollByThumbPosition = (left) => {
    const el = scrollRef.value
    const track = trackRef.value

    if (!el || !track || !thumbWidth.value) return

    const maxThumbLeft = track.clientWidth - thumbWidth.value
    const maxScrollLeft = el.scrollWidth - el.clientWidth

    if (maxThumbLeft <= 0 || maxScrollLeft <= 0) return

    const safeLeft = Math.max(0, Math.min(left, maxThumbLeft))

    el.scrollLeft = (safeLeft / maxThumbLeft) * maxScrollLeft

    updateScrollbar()
    checkReachEnd()
}

const handleTrackPointerDown = (event) => {
    const track = trackRef.value

    if (!track || !thumbWidth.value) return

    event.preventDefault()
    event.stopPropagation()

    const rect = track.getBoundingClientRect()
    const clickX = event.clientX - rect.left
    const targetLeft = clickX - thumbWidth.value / 2

    scrollByThumbPosition(targetLeft)
}

const handleThumbPointerDown = (event) => {
    event.preventDefault()
    event.stopPropagation()

    dragging.value = true
    dragStartX.value = event.clientX
    dragStartLeft.value = thumbLeft.value

    event.currentTarget.setPointerCapture(event.pointerId)
}

const handleThumbPointerMove = (event) => {
    if (!dragging.value) return

    event.preventDefault()
    event.stopPropagation()

    const diff = event.clientX - dragStartX.value

    scrollByThumbPosition(dragStartLeft.value + diff)
}

const handleThumbPointerUp = (event) => {
    dragging.value = false

    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
        event.currentTarget.releasePointerCapture(event.pointerId)
    }
}

watch(
    () => watchKey,
    async () => {
        await nextTick()
        updateScrollbar()
    }
)

onMounted(async () => {
    await nextTick()
    updateScrollbar()

    resizeObserver = new ResizeObserver(() => {
        updateScrollbar()
    })

    if (scrollRef.value) {
        resizeObserver.observe(scrollRef.value)
    }

    window.addEventListener('resize', updateScrollbar)
})

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect()
    }

    window.removeEventListener('resize', updateScrollbar)
})
</script>

<template>
    <div class="relative min-w-0" @wheel.prevent.stop="handleWheel">
        <div ref="scrollRef" @scroll.passive="handleScroll" @wheel.prevent.stop="handleWheel"
            class="overflow-x-auto overflow-y-visible pb-3 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <slot />
        </div>

        <div v-if="thumbWidth" ref="trackRef" :class="trackClass" @pointerdown="handleTrackPointerDown"
            @wheel.prevent.stop="handleWheel">
            <div :class="railClass">
                <div :class="thumbClass" @pointerdown="handleThumbPointerDown" @pointermove="handleThumbPointerMove"
                    @pointerup="handleThumbPointerUp" @pointercancel="handleThumbPointerUp"
                    @wheel.prevent.stop="handleWheel" :style="{
                        width: `${thumbWidth}px`,
                        transform: `translateX(${thumbLeft}px) translateY(-50%)`
                    }"></div>
            </div>
        </div>
    </div>
</template>
