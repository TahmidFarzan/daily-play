<script setup>
import {
    computed, getCurrentInstance, nextTick, onBeforeUnmount, onMounted, ref, watch,
} from 'vue'

import { useLightboxRegistry } from '@/composables/useLightboxRegistry'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faMagnifyingGlassPlus,
    faMagnifyingGlassMinus,
    faXmark,
    faChevronLeft,
    faChevronRight,
    faUpRightFromSquare,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(
    faMagnifyingGlassPlus,
    faMagnifyingGlassMinus,
    faXmark,
    faChevronLeft,
    faChevronRight,
    faUpRightFromSquare,
)

const {
    image,
    lightboxName = 'Image gallery',
    isImageGalleryItem = false,
    showImageGaleryCounter = false,
} = defineProps({
    image: {
        type: Object,
        required: true,
    },

    lightboxName: {
        type: String,
        default: 'Image gallery',
    },

    isImageGalleryItem: {
        type: Boolean,
        default: false,
    },

    showImageGaleryCounter: {
        type: Boolean,
        default: false,
    },
})

const { getGroup, registerToLightbox, unregisterFromLightbox } = useLightboxRegistry()

const uid = getCurrentInstance()?.uid || Math.random().toString(36).slice(2)

const isOpen = ref(false)
const activeIndex = ref(0)
const isZoomed = ref(false)
const imageWrapper = ref(null)
const imageOrientation = ref(null)

const normalizedImage = computed(() => {
    return {
        uid,
        id: image?.id || image?.uuid,
        src: image?.preview_url || image?.original_url || '',
        thumb: image?.preview_url || image?.original_url || '',
        alt: image?.custom_properties?.alt || image?.name || 'Gallery image',
        caption: image?.custom_properties?.caption || image?.name || '',
    }
})

const groupImages = computed(() => {
    return getGroup(lightboxName).filter((item) => item?.src)
})

const ownIndex = computed(() => {
    const foundIndex = groupImages.value.findIndex((item) => item.uid === uid)

    return foundIndex >= 0 ? foundIndex : 0
})

const activeImage = computed(() => {
    return groupImages.value?.[activeIndex.value] || normalizedImage.value || null
})

const hasMultipleImages = computed(() => {
    return groupImages.value.length > 1
})

const imageCounter = computed(() => {
    return `${activeIndex.value + 1} / ${groupImages.value.length}`
})

const itemCounter = computed(() => {
    return `${ownIndex.value + 1} / ${groupImages.value.length}`
})

const shouldShowItemCounter = computed(() => {
    return isImageGalleryItem && showImageGaleryCounter && groupImages.value.length > 0
})

const hasNextImage = computed(() => {
    return ownIndex.value + 1 < groupImages.value.length
})

const isVerticalImage = computed(() => {
    return imageOrientation.value === 'vertical'
})

const figureClass = computed(() => {
    return [
        'm-auto w-full max-w-3xl space-y-3 pb-8',
        hasNextImage.value ? 'border-b border-gray-200' : '',
    ]
})

const imageButtonClass = computed(() => {
    return isVerticalImage.value
        ? 'mx-auto block w-full max-w-2xl'
        : 'mx-auto block w-full max-w-3xl'
})

const previewImageClass = computed(() => {
    return isVerticalImage.value
        ? 'mx-auto h-auto max-h-[680px] w-auto max-w-full rounded-2xl object-contain transition duration-300 group-hover:scale-[1.015]'
        : 'mx-auto h-auto max-h-[580px] w-full max-w-full rounded-2xl object-contain transition duration-300 group-hover:scale-[1.015]'
})

const registerImage = () => {
    registerToLightbox(lightboxName, normalizedImage.value)
}

const setImageOrientation = (event) => {
    const loadedImage = event.target

    imageOrientation.value =
        loadedImage.naturalHeight > loadedImage.naturalWidth ? 'vertical' : 'horizontal'
}

const openLightbox = async () => {
    activeIndex.value = ownIndex.value
    isOpen.value = true
    isZoomed.value = false

    await nextTick()

    imageWrapper.value?.focus()
}

const openLightboxAt = async (index) => {
    activeIndex.value = index
    isOpen.value = true
    isZoomed.value = false

    await nextTick()

    imageWrapper.value?.focus()
}

const closeLightbox = () => {
    isOpen.value = false
    isZoomed.value = false
}

const showPrevious = () => {
    if (!groupImages.value.length) {
        return
    }

    activeIndex.value =
        activeIndex.value === 0
            ? groupImages.value.length - 1
            : activeIndex.value - 1

    isZoomed.value = false
}

const showNext = () => {
    if (!groupImages.value.length) {
        return
    }

    activeIndex.value =
        activeIndex.value === groupImages.value.length - 1
            ? 0
            : activeIndex.value + 1

    isZoomed.value = false
}

const toggleZoom = () => {
    isZoomed.value = !isZoomed.value
}

const handleKeydown = (event) => {
    if (!isOpen.value) {
        return
    }

    if (event.key === 'Escape') {
        closeLightbox()
    }

    if (event.key === 'ArrowLeft') {
        showPrevious()
    }

    if (event.key === 'ArrowRight') {
        showNext()
    }
}

watch(
    normalizedImage,
    () => {
        registerImage()
    },
    { deep: true },
)

watch(isOpen, (value) => {
    if (typeof document === 'undefined') {
        return
    }

    if (value) {
        document.body.classList.add('overflow-hidden')
        window.addEventListener('keydown', handleKeydown)
    } else {
        document.body.classList.remove('overflow-hidden')
        window.removeEventListener('keydown', handleKeydown)
    }
})

onMounted(() => {
    registerImage()
})

onBeforeUnmount(() => {
    unregisterFromLightbox(lightboxName, uid)
    window.removeEventListener('keydown', handleKeydown)

    if (typeof document !== 'undefined') {
        document.body.classList.remove('overflow-hidden')
    }
})
</script>

<template>
    <figure class="light-box-container" :class="figureClass">
        <div v-if="shouldShowItemCounter" class="flex items-center gap-2 text-sm leading-6 text-gray-600">
            <span class="rounded border border-gray-300 px-2 py-0.5 font-semibold text-gray-900">
                {{ itemCounter }}
            </span>
        </div>

        <button type="button" :class="[
            imageButtonClass,
            'group relative overflow-hidden rounded-2xl bg-transparent text-left transition duration-300 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2',
        ]" @click="openLightbox">
            <img :src="normalizedImage.thumb" :alt="normalizedImage.alt" :class="previewImageClass" loading="lazy"
                @load="setImageOrientation" />

            <div
                class="absolute inset-0 flex items-center justify-center rounded-2xl bg-black/0 opacity-0 transition duration-300 group-hover:bg-black/35 group-hover:opacity-100">
                <div
                    class="flex h-14 w-14 scale-90 items-center justify-center rounded-full bg-white/90 text-xl text-gray-900 shadow-lg transition duration-300 group-hover:scale-100">
                    <FontAwesomeIcon :icon="faMagnifyingGlassPlus" />
                </div>
            </div>
        </button>

        <figcaption v-if="normalizedImage.caption" class="text-sm leading-6 text-gray-500">
            {{ normalizedImage.caption }}
        </figcaption>

        <Teleport to="body">
            <Transition name="lightbox-fade">
                <div v-if="isOpen" class="fixed inset-0 z-[9999] flex bg-black/95 text-white" role="dialog"
                    aria-modal="true">
                    <button type="button" class="absolute inset-0 cursor-default" aria-label="Close overlay"
                        @click="closeLightbox" />

                    <div class="lightbox-panel relative z-10 flex h-full w-full flex-col">
                        <div class="flex items-center justify-between gap-3 bg-black/80 px-3 py-2 sm:px-5">
                            <div class="min-w-0">
                                <p class="text-sm text-white/70">
                                    {{ imageCounter }}
                                </p>

                                <p class="truncate text-sm font-medium">
                                    {{ activeImage?.caption }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                    class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-medium transition hover:bg-white/20"
                                    @click.stop="toggleZoom">
                                    <FontAwesomeIcon
                                        :icon="isZoomed ? faMagnifyingGlassMinus : faMagnifyingGlassPlus" />

                                    <span class="hidden sm:inline">
                                        {{ isZoomed ? 'Zoom Out' : 'Zoom In' }}
                                    </span>
                                </button>

                                <a v-if="activeImage?.src" :href="activeImage.src" target="_blank"
                                    rel="noopener noreferrer"
                                    class="hidden items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-medium transition hover:bg-white/20 sm:inline-flex"
                                    @click.stop>
                                    <FontAwesomeIcon :icon="faUpRightFromSquare" />

                                    <span>Original</span>
                                </a>

                                <button type="button"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-lg transition hover:bg-white/20"
                                    aria-label="Close" @click.stop="closeLightbox">
                                    <FontAwesomeIcon :icon="faXmark" />
                                </button>
                            </div>
                        </div>

                        <div ref="imageWrapper" tabindex="0"
                            class="relative flex h-full w-full flex-1 items-center justify-center overflow-auto bg-black p-0 outline-none">
                            <button v-if="hasMultipleImages" type="button"
                                class="fixed left-3 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-lg text-white transition hover:bg-black/80 sm:left-6"
                                aria-label="Previous image" @click.stop="showPrevious">
                                <FontAwesomeIcon :icon="faChevronLeft" />
                            </button>

                            <Transition name="image-slide" mode="out-in">
                                <img v-if="activeImage" :key="activeImage.src" :src="activeImage.src"
                                    :alt="activeImage.alt"
                                    class="select-none object-contain transition-transform duration-300" :class="isZoomed
                                        ? 'h-auto w-auto max-h-none max-w-none cursor-zoom-out'
                                        : 'h-full w-full cursor-zoom-in'
                                        " @click.stop="toggleZoom" />
                            </Transition>

                            <button v-if="hasMultipleImages" type="button"
                                class="fixed right-3 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-lg text-white transition hover:bg-black/80 sm:right-6"
                                aria-label="Next image" @click.stop="showNext">
                                <FontAwesomeIcon :icon="faChevronRight" />
                            </button>
                        </div>

                        <div v-if="hasMultipleImages"
                            class="flex gap-2 overflow-x-auto border-t border-white/10 px-4 py-3 sm:px-6">
                            <button v-for="(thumbImage, thumbIndex) in groupImages"
                                :key="`thumb-${thumbImage?.id || thumbImage?.src || thumbIndex}`" type="button"
                                class="h-16 w-20 shrink-0 overflow-hidden rounded-lg border transition sm:w-24" :class="activeIndex === thumbIndex
                                    ? 'border-white opacity-100'
                                    : 'border-transparent opacity-60 hover:opacity-100'
                                    " @click.stop="openLightboxAt(thumbIndex)">
                                <img :src="thumbImage.thumb" :alt="thumbImage.alt" class="h-full w-full object-contain"
                                    loading="lazy" />
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </figure>
</template>

<style scoped>
.lightbox-fade-enter-active,
.lightbox-fade-leave-active {
    transition: opacity 220ms ease;
}

.lightbox-fade-enter-from,
.lightbox-fade-leave-to {
    opacity: 0;
}

.lightbox-fade-enter-active .lightbox-panel,
.lightbox-fade-leave-active .lightbox-panel {
    transition:
        transform 220ms ease,
        opacity 220ms ease;
}

.lightbox-fade-enter-from .lightbox-panel,
.lightbox-fade-leave-to .lightbox-panel {
    transform: scale(0.96);
    opacity: 0;
}

.image-slide-enter-active,
.image-slide-leave-active {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.image-slide-enter-from,
.image-slide-leave-to {
    opacity: 0;
    transform: scale(0.98);
}
</style>
