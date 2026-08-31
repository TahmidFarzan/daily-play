<script setup>
import NewsImageGalleryImageCard from './NewsImageGalleryImageCard.vue'

import { ref, computed, onBeforeUnmount } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faPlus,
    faSpinner,
    faXmark,
    faGripVertical,
    faFloppyDisk,
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(
    faPlus, faSpinner, faXmark, faGripVertical, faFloppyDisk
)

const { t } = useTranslate()


const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

const showCreateModal = ref(false)
const imagePreviewUrl = ref(null)
const imageInputRef = ref(null)

const isSequenceMode = ref(false)
const sequenceImages = ref([])
const draggedIndex = ref(null)
const dragOverIndex = ref(null)

const galleryImages = computed(() => {
    if (!news?.gallery_images) {
        return []
    }

    const images = Array.isArray(news.gallery_images)
        ? news.gallery_images
        : Object.values(news.gallery_images)

    return images
        .filter(Boolean)
        .sort((firstImage, secondImage) => {
            return Number(firstImage?.order_column || 0) - Number(secondImage?.order_column || 0)
        })
})

const displayGalleryImages = computed(() => {
    return isSequenceMode.value ? sequenceImages.value : galleryImages.value
})

const saveForm = useForm({
    image: null,
    caption: '',
    alt: '',
    order_column: '',
})

const sequenceForm = useForm({
    sequence: [],
})

function openModal() {
    showCreateModal.value = true
}

function clearImagePreview() {
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value)
        imagePreviewUrl.value = null
    }
}

function resetForm() {
    saveForm.reset()
    saveForm.clearErrors()
    clearImagePreview()

    if (imageInputRef.value) {
        imageInputRef.value.value = ''
    }
}

function closeModal(force = false) {
    if (saveForm.processing && !force) return

    showCreateModal.value = false
    resetForm()
}

function handleImageChange(event) {
    const file = event.target.files?.[0] || null

    saveForm.image = file
    saveForm.clearErrors('image')

    clearImagePreview()

    if (file) {
        imagePreviewUrl.value = URL.createObjectURL(file)
    }
}

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (!saveForm.image) {
        saveForm.setError('image', t("common.messages.imageIsRequired"))
        valid = false
    }

    return valid
}

function refreshGalleryImages() {
    router.reload({
        only: ['news'],
        preserveScroll: true,
        preserveState: true,
    })
}

function handleSave() {
    if (saveForm.processing) return

    if (!validateForm()) return

    saveForm.post(
        route('back-office.news.gallery-images.save', {
            slug: news?.slug,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            forceFormData: true,

            onSuccess: () => {
                closeModal(true)
                refreshGalleryImages()
            },

            onError: (errors) => {
                saveForm.clearErrors()
                saveForm.setError(errors)
            },
        }
    )
}

function enableSequenceMode() {
    if (saveForm.processing || sequenceForm.processing) return

    sequenceImages.value = galleryImages.value.map((galleryImage) => ({ ...galleryImage }))
    sequenceForm.sequence = []
    sequenceForm.clearErrors()
    draggedIndex.value = null
    dragOverIndex.value = null
    isSequenceMode.value = true
}

function cancelSequenceMode() {
    if (sequenceForm.processing) return

    sequenceImages.value = []
    sequenceForm.sequence = []
    sequenceForm.clearErrors()
    draggedIndex.value = null
    dragOverIndex.value = null
    isSequenceMode.value = false
}

function handleDragStart(index) {
    if (!isSequenceMode.value) return

    draggedIndex.value = index
}

function handleDragEnter(index) {
    if (!isSequenceMode.value) return

    dragOverIndex.value = index
}

function handleDrop(dropIndex) {
    if (!isSequenceMode.value) return

    if (draggedIndex.value === null || draggedIndex.value === dropIndex) {
        draggedIndex.value = null
        dragOverIndex.value = null
        return
    }

    const images = [...sequenceImages.value]
    const draggedImage = images.splice(draggedIndex.value, 1)[0]

    images.splice(dropIndex, 0, draggedImage)

    sequenceImages.value = images
    draggedIndex.value = null
    dragOverIndex.value = null
}

function handleDragEnd() {
    draggedIndex.value = null
    dragOverIndex.value = null
}

function saveSequence() {
    if (sequenceForm.processing) return

    sequenceForm.clearErrors()
    sequenceForm.sequence = sequenceImages.value.map((galleryImage) => galleryImage.id)

    if (!sequenceForm.sequence.length) {
        sequenceForm.setError('sequence', t("common.messages.sequenceIsRequired"))
        return
    }

    sequenceForm.patch(
        route('back-office.news.gallery-images.update-sequence', {
            slug: news?.slug,
        }),
        {
            preserveScroll: true,
            preserveState: true,

            onSuccess: () => {
                isSequenceMode.value = false
                sequenceImages.value = []
                sequenceForm.sequence = []
                refreshGalleryImages()
            },

            onError: (errors) => {
                sequenceForm.clearErrors()
                sequenceForm.setError(errors)
            },
        }
    )
}

onBeforeUnmount(() => {
    clearImagePreview()
})
</script>

<template>
    <div>
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="text-gray-500">
                {{ t("common.labels.galleryImages") }}
            </div>

            <div class="flex items-center gap-2">
                <template v-if="!isSequenceMode">
                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="openModal">
                        <FontAwesomeIcon icon="plus" />
                        {{ t("common.actions.addImage") }}
                    </button>

                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-white shadow hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="saveForm.processing || sequenceForm.processing" @click="enableSequenceMode">
                        <FontAwesomeIcon icon="grip-vertical" />
                        {{ t("common.actions.dragAndSequence") }}
                    </button>
                </template>

                <template v-else>
                    <button type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="sequenceForm.processing" @click="cancelSequenceMode">
                        {{ t("common.actions.cancel") }}
                    </button>

                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="sequenceForm.processing" @click="saveSequence">
                        <FontAwesomeIcon v-if="sequenceForm.processing" icon="spinner" class="animate-spin" />

                        <FontAwesomeIcon v-else icon="floppy-disk" />

                        <span>
                            {{ sequenceForm.processing ? t("common.actions.saving") : t("common.actions.save") }}
                        </span>
                    </button>
                </template>
            </div>
        </div>

        <div v-if="sequenceForm.errors.sequence" class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-600">
            {{ sequenceForm.errors.sequence }}
        </div>

        <div v-if="!displayGalleryImages.length"
            class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
            {{ t("common.labels.noRecordsFound") }}
        </div>

        <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            <div v-for="(galleryImage, index) in displayGalleryImages" :key="galleryImage.id" class="relative" :class="[
                isSequenceMode ? 'cursor-move' : '',
                dragOverIndex === index ? 'rounded-lg ring-2 ring-blue-500' : '',
            ]" :draggable="isSequenceMode" @dragstart="handleDragStart(index)"
                @dragenter.prevent="handleDragEnter(index)" @dragover.prevent @drop.prevent="handleDrop(index)"
                @dragend="handleDragEnd">
                <div v-if="isSequenceMode"
                    class="absolute left-2 top-2 z-10 flex h-8 min-w-8 items-center justify-center rounded-full bg-black/70 px-2 text-xs font-semibold text-white">
                    {{ index + 1 }}
                </div>

                <NewsImageGalleryImageCard :news="news" :gallery-image="galleryImage" :sequence-mode="isSequenceMode"
                    @refresh-gallery-images="refreshGalleryImages" />
            </div>
        </div>

        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                    @click.self="closeModal">
                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                            <div class="mb-4 flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-800">
                                    {{ t("common.actions.addImage") }}
                                </h2>

                                <button type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                    @click="closeModal">
                                    <FontAwesomeIcon icon="xmark" />
                                </button>
                            </div>

                            <form @submit.prevent="handleSave">
                                <div class="space-y-4">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">
                                            {{ t("common.labels.image") }} <span class="text-red-500">*</span>
                                        </label>

                                        <input ref="imageInputRef" type="file" accept="image/*"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200"
                                            @change="handleImageChange">

                                        <div v-if="saveForm.errors.image" class="mt-1 text-sm text-red-600">
                                            {{ saveForm.errors.image }}
                                        </div>

                                        <div v-if="imagePreviewUrl" class="mt-3">
                                            <div class="mb-1 text-sm text-gray-500">
                                                {{ t("common.labels.preview") }}
                                            </div>

                                            <img :src="imagePreviewUrl" alt="Selected image preview"
                                                class="h-28 w-28 rounded-lg border object-cover">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">
                                            {{ t("common.labels.orderColumn") }}
                                        </label>

                                        <input v-model="saveForm.order_column" type="number"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Enter order column" min="0" step="1">

                                        <div v-if="saveForm.errors.order_column" class="mt-1 text-sm text-red-600">
                                            {{ saveForm.errors.order_column }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">
                                            {{ t("common.labels.caption") }}
                                        </label>

                                        <input v-model="saveForm.caption" type="text"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Enter caption">

                                        <div v-if="saveForm.errors.caption" class="mt-1 text-sm text-red-600">
                                            {{ saveForm.errors.caption }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">
                                            {{ t("common.labels.altText") }}
                                        </label>

                                        <input v-model="saveForm.alt" type="text"
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Enter alt text">

                                        <div v-if="saveForm.errors.alt" class="mt-1 text-sm text-red-600">
                                            {{ saveForm.errors.alt }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button type="button"
                                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="saveForm.processing" @click="closeModal">
                                        {{ t("common.actions.cancel") }}
                                    </button>

                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="saveForm.processing">
                                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner"
                                            class="animate-spin" />

                                        <span>
                                            {{ saveForm.processing ? t("common.actions.saving") : t("common.actions.save") }}
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
