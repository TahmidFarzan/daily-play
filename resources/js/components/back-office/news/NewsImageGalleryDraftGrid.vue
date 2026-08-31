<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import axios from 'axios'

import NewsImageGalleryDraftImageCard from './NewsImageGalleryDraftImageCard.vue'

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
    faPlus,
    faSpinner,
    faXmark,
    faGripVertical,
    faFloppyDisk
)

const { t } = useTranslate()

const {
    form,
    fieldName,
    initialImages,
} = defineProps({
    form: {
        type: Object,
        required: true,
    },

    fieldName: {
        type: String,
        default: 'gallery_image_ids',
    },

    initialImages: {
        type: Array,
        default: () => [],
    },
})

const selectedImages = ref([...initialImages])

const showCreateModal = ref(false)
const imagePreviewUrl = ref(null)
const imageInputRef = ref(null)

const createProcessing = ref(false)
const createErrors = ref({})

const isSequenceMode = ref(false)
const sequenceImages = ref([])
const sequenceError = ref(null)

const draggedIndex = ref(null)
const dragOverIndex = ref(null)

const createForm = ref({
    image: null,
    caption: '',
    alt: '',
})

const displayImages = computed(() => {
    return isSequenceMode.value ? sequenceImages.value : selectedImages.value
})

const fieldError = computed(() => {
    return form?.errors?.[fieldName] || null
})

ensureFormField()
syncFormImageIds()

function ensureFormField() {
    if (!Array.isArray(form[fieldName])) {
        form[fieldName] = []
    }
}

function syncFormImageIds(imageList = selectedImages.value) {
    ensureFormField()

    form[fieldName] = imageList
        .map((image) => image?.id)
        .filter(Boolean)

    if (typeof form.clearErrors === 'function') {
        form.clearErrors(fieldName)
    }
}

function clearImagePreview() {
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value)
        imagePreviewUrl.value = null
    }
}

function resetCreateForm() {
    createForm.value = {
        image: null,
        caption: '',
        alt: '',
    }

    createErrors.value = {}
    clearImagePreview()

    if (imageInputRef.value) {
        imageInputRef.value.value = ''
    }
}

function openCreateModal() {
    resetCreateForm()
    showCreateModal.value = true
}

function closeCreateModal(force = false) {
    if (createProcessing.value && !force) return

    showCreateModal.value = false
    resetCreateForm()
}

function handleImageChange(event) {
    const file = event.target.files?.[0] || null

    createForm.value.image = file
    createErrors.value.media = null
    createErrors.value.image = null

    clearImagePreview()

    if (file) {
        imagePreviewUrl.value = URL.createObjectURL(file)
    }
}

function validateCreateForm() {
    createErrors.value = {}

    if (!createForm.value.image) {
        createErrors.value.media = t("common.messages.imageIsRequired")
        return false
    }

    return true
}

function normalizeErrors(error) {
    const responseErrors = error?.response?.data?.errors || {}
    const normalizedErrors = {}

    Object.keys(responseErrors).forEach((key) => {
        normalizedErrors[key] = Array.isArray(responseErrors[key])
            ? responseErrors[key][0]
            : responseErrors[key]
    })

    if (!Object.keys(normalizedErrors).length) {
        normalizedErrors.general =
            error?.response?.data?.message || t("common.messages.invalidData")
    }

    return normalizedErrors
}

async function saveImage() {
    if (createProcessing.value) return
    if (!validateCreateForm()) return

    createProcessing.value = true
    createErrors.value = {}

    const payload = new FormData()

    payload.append('media', createForm.value.image)
    payload.append('caption', createForm.value.caption || '')
    payload.append('alt', createForm.value.alt || '')

    try {
        const response = await axios.post(
            route('back-office.medias.quick-save'),
            payload,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }
        )

        if (response?.data?.status === 'error') {
            createErrors.value.general = response?.data?.message || t("admin.components.news.newsImageGalleryDraftGrid.messages.imageSaveFailed")
            return
        }

        const image = response?.data?.media

        if (!image?.id) {
            createErrors.value.general = t("admin.components.news.newsImageGalleryDraftGrid.messages.invalidImageResponse")
            return
        }

        selectedImages.value.push(image)

        if (isSequenceMode.value) {
            sequenceImages.value.push(image)
            syncFormImageIds(sequenceImages.value)
        } else {
            syncFormImageIds(selectedImages.value)
        }

        closeCreateModal(true)
    } catch (error) {
        createErrors.value = normalizeErrors(error)
    } finally {
        createProcessing.value = false
    }
}

function handleImageUpdated(updatedImage) {
    selectedImages.value = selectedImages.value.map((image) => {
        return image.id === updatedImage.id ? updatedImage : image
    })

    sequenceImages.value = sequenceImages.value.map((image) => {
        return image.id === updatedImage.id ? updatedImage : image
    })

    syncFormImageIds(isSequenceMode.value ? sequenceImages.value : selectedImages.value)
}

function handleImageDeleted(deletedImage) {
    selectedImages.value = selectedImages.value.filter((image) => {
        return image.id !== deletedImage.id
    })

    sequenceImages.value = sequenceImages.value.filter((image) => {
        return image.id !== deletedImage.id
    })

    syncFormImageIds(isSequenceMode.value ? sequenceImages.value : selectedImages.value)
}

function enableSequenceMode() {
    sequenceImages.value = selectedImages.value.map((image) => ({ ...image }))

    sequenceError.value = null
    draggedIndex.value = null
    dragOverIndex.value = null
    isSequenceMode.value = true

    syncFormImageIds(sequenceImages.value)
}

function cancelSequenceMode() {
    sequenceImages.value = []
    sequenceError.value = null
    draggedIndex.value = null
    dragOverIndex.value = null
    isSequenceMode.value = false

    syncFormImageIds(selectedImages.value)
}

function saveSequence() {
    if (!sequenceImages.value.length) {
        sequenceError.value = t("common.messages.sequenceIsRequired")
        return
    }

    selectedImages.value = sequenceImages.value.map((image) => ({ ...image }))

    sequenceImages.value = []
    sequenceError.value = null
    draggedIndex.value = null
    dragOverIndex.value = null
    isSequenceMode.value = false

    syncFormImageIds(selectedImages.value)
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

    syncFormImageIds(sequenceImages.value)
}

function handleDragEnd() {
    draggedIndex.value = null
    dragOverIndex.value = null
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
                        :disabled="createProcessing" @click="openCreateModal">
                        <FontAwesomeIcon icon="plus" />
                        {{ t("common.actions.addImage") }}
                    </button>

                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-white shadow hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="createProcessing || !selectedImages.length" @click="enableSequenceMode">
                        <FontAwesomeIcon icon="grip-vertical" />
                        {{ t("common.actions.dragAndSequence") }}
                    </button>
                </template>

                <template v-else>
                    <button type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                        @click="cancelSequenceMode">
                        {{ t("common.actions.cancel") }}
                    </button>

                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-green-700"
                        @click="saveSequence">
                        <FontAwesomeIcon icon="floppy-disk" />
                        {{ t("common.actions.save") }}
                    </button>
                </template>
            </div>
        </div>

        <div v-if="fieldError" class="mb-3 rounded-lg p-3 text-sm text-red-600">
            {{ fieldError }}
        </div>

        <div v-if="sequenceError" class="mb-3 rounded-lg p-3 text-sm text-red-600">
            {{ sequenceError }}
        </div>

        <div v-if="!displayImages.length"
            class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
            {{ t("common.labels.noRecordsFound") }}
        </div>

        <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            <div v-for="(image, index) in displayImages" :key="image.id" :class="[
                isSequenceMode ? 'cursor-move' : '',
                dragOverIndex === index ? 'rounded-xl ring-2 ring-blue-500' : '',
            ]" :draggable="isSequenceMode" @dragstart="handleDragStart(index)"
                @dragenter.prevent="handleDragEnter(index)" @dragover.prevent @drop.prevent="handleDrop(index)"
                @dragend="handleDragEnd">
                <NewsImageGalleryDraftImageCard :image="image" :index="index" :sequence-mode="isSequenceMode"
                    @image-updated="handleImageUpdated" @image-deleted="handleImageDeleted" />
            </div>
        </div>

    </div>

    <Teleport to="body">
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                @click.self="closeCreateModal">
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
                                @click="closeCreateModal">
                                <FontAwesomeIcon icon="xmark" />
                            </button>
                        </div>

                        <form @submit.prevent="saveImage">
                            <div v-if="createErrors.general" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-600">
                                {{ createErrors.general }}
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        {{ t("common.labels.image") }} <span class="text-red-500">*</span>
                                    </label>

                                    <input ref="imageInputRef" type="file" accept="image/*"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200"
                                        @change="handleImageChange">

                                    <div v-if="createErrors.media" class="mt-1 text-sm text-red-600">
                                        {{ createErrors.media }}
                                    </div>

                                    <div v-if="imagePreviewUrl" class="mt-3">
                                        <div class="mb-1 text-sm text-gray-500">
                                            {{ t("common.labels.preview") }}
                                        </div>

                                        <img :src="imagePreviewUrl" alt="Selected image preview"
                                            class="h-50 rounded-lg border border-gray-300 object-cover">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        {{ t("common.labels.caption") }}
                                    </label>

                                    <input v-model="createForm.caption" type="text"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="Enter caption">

                                    <div v-if="createErrors.caption" class="mt-1 text-sm text-red-600">
                                        {{ createErrors.caption }}
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        {{ t("common.labels.altText") }}
                                    </label>

                                    <input v-model="createForm.alt" type="text"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="Enter alt text">

                                    <div v-if="createErrors.alt" class="mt-1 text-sm text-red-600">
                                        {{ createErrors.alt }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="createProcessing" @click="closeCreateModal">
                                    {{ t("common.actions.cancel") }}
                                </button>

                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="createProcessing">
                                    <FontAwesomeIcon v-if="createProcessing" icon="spinner" class="animate-spin" />

                                    <span>
                                        {{ createProcessing ? t("common.actions.saving") : t("common.actions.save") }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
