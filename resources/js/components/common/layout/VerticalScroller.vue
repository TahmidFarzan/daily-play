<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount, watch } from "vue"

const {
    loading = false,
    threshold = 60,
    watchKey = '',
    maxHeightClass = 'max-h-72',
    contentClass = '',
    trackClass = 'absolute right-1 top-2 bottom-2 w-3 cursor-pointer flex justify-center',
    railClass = 'relative h-full w-[2px] rounded-full bg-gray-200/70',
    thumbClass = 'absolute left-1/2 w-[2px] cursor-grab rounded-full bg-gray-400 transition-colors hover:bg-gray-600 active:cursor-grabbing',
} = defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
    threshold: {
        type: Number,
        default: 60,
    },
    watchKey: {
        type: [String, Number, Boolean],
        default: '',
    },
    maxHeightClass: {
        type: String,
        default: 'max-h-72',
    },
    contentClass: {
        type: String,
        default: '',
    },
    trackClass: {
        type: String,
        default: 'absolute right-1 top-2 bottom-2 w-3 cursor-pointer flex justify-center',
    },
    railClass: {
        type: String,
        default: 'relative h-full w-[2px] rounded-full bg-gray-200/70',
    },
    thumbClass: {
        type: String,
        default: 'absolute left-1/2 w-[2px] cursor-grab rounded-full bg-gray-400 transition-colors hover:bg-gray-600 active:cursor-grabbing',
    },
})

const emit = defineEmits(['reach-end'])

const scrollRef = ref(null)
const trackRef = ref(null)
const thumbHeight = ref(0)
const thumbTop = ref(0)
const dragging = ref(false)
const dragStartY = ref(0)
const dragStartTop = ref(0)

let resizeObserver = null

const updateScrollbar = () => {
    const el = scrollRef.value

    if (!el) return

    const clientHeight = el.clientHeight
    const scrollHeight = el.scrollHeight
    const scrollTop = el.scrollTop

    if (scrollHeight <= clientHeight) {
        thumbHeight.value = 0
        thumbTop.value = 0
        return
    }

    const trackHeight = trackRef.value?.clientHeight || clientHeight
    const newThumbHeight = Math.max((clientHeight / scrollHeight) * trackHeight, 28)
    const maxThumbTop = trackHeight - newThumbHeight
    const maxScrollTop = scrollHeight - clientHeight

    thumbHeight.value = newThumbHeight
    thumbTop.value = (scrollTop / maxScrollTop) * maxThumbTop
}

const checkReachEnd = () => {
    const el = scrollRef.value

    if (!el || loading) return

    const almostEnd = el.scrollTop + el.clientHeight >= el.scrollHeight - threshold

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

    const rawDelta = Math.abs(event.deltaY) >= Math.abs(event.deltaX)
        ? event.deltaY
        : event.deltaX

    if (!rawDelta) return

    const delta = event.deltaMode === 1
        ? rawDelta * 16
        : rawDelta

    event.preventDefault()
    event.stopPropagation()

    el.scrollTop += delta

    updateScrollbar()
    checkReachEnd()
}

const scrollByThumbPosition = (top) => {
    const el = scrollRef.value
    const track = trackRef.value

    if (!el || !track || !thumbHeight.value) return

    const maxThumbTop = track.clientHeight - thumbHeight.value
    const maxScrollTop = el.scrollHeight - el.clientHeight

    if (maxThumbTop <= 0 || maxScrollTop <= 0) return

    const safeTop = Math.max(0, Math.min(top, maxThumbTop))

    el.scrollTop = (safeTop / maxThumbTop) * maxScrollTop

    updateScrollbar()
    checkReachEnd()
}

const handleTrackPointerDown = (event) => {
    const track = trackRef.value

    if (!track || !thumbHeight.value) return

    event.preventDefault()
    event.stopPropagation()

    const rect = track.getBoundingClientRect()
    const clickY = event.clientY - rect.top
    const targetTop = clickY - thumbHeight.value / 2

    scrollByThumbPosition(targetTop)
}

const handleThumbPointerDown = (event) => {
    event.preventDefault()
    event.stopPropagation()

    dragging.value = true
    dragStartY.value = event.clientY
    dragStartTop.value = thumbTop.value

    event.currentTarget.setPointerCapture(event.pointerId)
}

const handleThumbPointerMove = (event) => {
    if (!dragging.value) return

    event.preventDefault()
    event.stopPropagation()

    const diff = event.clientY - dragStartY.value

    scrollByThumbPosition(dragStartTop.value + diff)
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
    <div class="relative overflow-visible" @wheel.prevent.stop="handleWheel">
        <div ref="scrollRef" @scroll.passive="handleScroll" @wheel.prevent.stop="handleWheel" :class="[
            maxHeightClass,
            contentClass,
            'overflow-y-auto overflow-x-visible pr-3 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden'
        ]">
            <slot />
        </div>

        <div v-if="thumbHeight" ref="trackRef" :class="trackClass" @pointerdown="handleTrackPointerDown"
            @wheel.prevent.stop="handleWheel">
            <div :class="railClass">
                <div :class="thumbClass" @pointerdown="handleThumbPointerDown" @pointermove="handleThumbPointerMove"
                    @pointerup="handleThumbPointerUp" @pointercancel="handleThumbPointerUp"
                    @wheel.prevent.stop="handleWheel" :style="{
                        height: `${thumbHeight}px`,
                        transform: `translateX(-50%) translateY(${thumbTop}px)`
                    }"></div>
            </div>
        </div>
    </div>
</template>
