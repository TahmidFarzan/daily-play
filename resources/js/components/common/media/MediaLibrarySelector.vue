<script setup>
import MediaRenderer from './MediaRenderer.vue'
import { ref, watch, computed } from 'vue'
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'

const { disableOpenMediaButton, mediaType, galleryTitle, fetchUrl, multiple, hideDefaultOpenButton, cacheKey, cacheTtl } = defineProps({
    disableOpenMediaButton: {
        type: Boolean,
        default: false,
    },
    mediaType: {
        type: String,
        default: 'image',
    },
    galleryTitle: {
        type: String,
        default: 'Media',
    },
    fetchUrl: {
        type: String,
        required: true,
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    hideDefaultOpenButton: {
        type: Boolean,
        default: false,
    },
    cacheKey: {
        type: String,
        default: null,
    },
    cacheTtl: {
        type: Number,
        default: null,
    },
})

const emit = defineEmits(['media-selected'])

const showModal = ref(false)
const mediaList = ref([])
const selectedMediaList = ref([])
const search = ref('')
const page = ref(1)
const lastPage = ref(null)
const perPage = 10
const loading = ref(false)

let loadedPages = new Set()

const hasSelection = computed(() => selectedMediaList.value.length > 0)
const resolvedCacheKey = computed(() => cacheKey || apiCacheKey.DEFAULT)
const resolvedCacheTtl = computed(() => cacheTtl ?? apiCacheTTL.DEFAULT)

const openModal = () => {
    showModal.value = true
}

const resetSelection = () => {
    selectedMediaList.value = []
}

const handleSearch = () => {
    page.value = 1
    lastPage.value = null
    loadedPages = new Set()
    loadMedia(true)
}

const loadMore = () => {
    if (lastPage.value !== null && page.value < lastPage.value) {
        page.value++
        loadMedia()
    }
}

const isSelected = (id) => {
    return selectedMediaList.value.some((media) => media.id === id)
}

const toggleMedia = (media) => {
    if (multiple) {
        const index = selectedMediaList.value.findIndex((item) => item.id === media.id)

        if (index >= 0) {
            selectedMediaList.value.splice(index, 1)
        } else {
            selectedMediaList.value.push(media)
        }
    } else {
        selectedMediaList.value = [media]
    }
}

const confirmSelection = () => {
    if (!hasSelection.value) return

    emit(
        'media-selected',
        multiple ? selectedMediaList.value : selectedMediaList.value[0]
    )

    showModal.value = false
}

const loadMedia = async (clear = false) => {
    if (loading.value || loadedPages.has(page.value)) return

    loading.value = true

    try {
        const params = {
            page: page.value,
            per_page: perPage,
            search: search.value,
            media_type: mediaType,
        }
        const data = await fetchFromApi(
            fetchUrl,
            params,
            {
                key: `${resolvedCacheKey.value}:${fetchUrl}`,
                ttl: resolvedCacheTtl.value,
            }
        )

        if (clear) {
            mediaList.value = []
        }

        const items = data.items || data.data || []

        mediaList.value.push(...items)
        loadedPages.add(page.value)

        lastPage.value = data.last_page || data.meta?.last_page || null
    } catch (error) {
        console.error('Media load failed:', error)
    } finally {
        loading.value = false
    }
}

watch(showModal, (value) => {
    if (value) {
        page.value = 1
        lastPage.value = null
        loadedPages = new Set()
        loadMedia(true)
    }
})

defineExpose({
    openModal,
    resetSelection,
})
</script>

<template>
    <div class="media-library-select">
        <button v-if="!disableOpenMediaButton" type="button"
            class="px-3 py-1 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-400 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mb-2"
            @click="openModal" :hidden="hideDefaultOpenButton">
            Open Media Library
        </button>

        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModal = false">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">

                <div class="fixed inset-0 z-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showModal = false">
                </div>

                <div
                    class="relative z-10 inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ galleryTitle }} Gallery
                            </h3>

                            <button type="button" @click="showModal = false"
                                class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4 overflow-y-auto" style="max-height: 70vh;">
                        <div class="mb-4">
                            <input v-model="search" type="text"
                                class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Search..." @input="handleSearch" />
                        </div>

                        <div v-if="loading && mediaList.length === 0" class="py-10 text-center text-gray-500">
                            Loading...
                        </div>

                        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-if="!loading && mediaList.length === 0"
                                class="col-span-full text-center text-gray-500 py-8">
                                No media found
                            </div>

                            <div v-for="media in mediaList" :key="media.id" @click="toggleMedia(media)" :class="[
                                'cursor-pointer transition-all duration-200 hover:scale-102',
                                isSelected(media.id)
                                    ? 'ring-2 ring-blue-500 rounded-lg'
                                    : 'hover:shadow-md'
                            ]">
                                <MediaRenderer :media="media" />
                            </div>
                        </div>

                        <div class="mt-4 text-center" v-if="lastPage !== null && page < lastPage">
                            <button type="button" @click="loadMore" :disabled="loading"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                                <svg v-if="loading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>

                                {{ loading ? 'Loading...' : 'Load More' }}
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-2">
                        <button type="button" @click="showModal = false"
                            class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Close
                        </button>

                        <button type="button" @click="confirmSelection" :disabled="!hasSelection"
                            class="px-3 py-1 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            Select
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hover\:scale-102:hover {
    transform: scale(1.02);
}
</style>
