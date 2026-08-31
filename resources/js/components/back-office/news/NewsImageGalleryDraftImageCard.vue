<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faEye,
    faPenToSquare,
    faTrash,
    faSpinner,
    faXmark,
} from '@fortawesome/free-solid-svg-icons'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(
    faEye,
    faPenToSquare,
    faTrash,
    faSpinner,
    faXmark
)

const { t } = useTranslate()

const {
    image,
    index,
    sequenceMode,
} = defineProps({
    image: {
        type: Object,
        required: true,
    },

    index: {
        type: Number,
        default: 0,
    },

    sequenceMode: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits([
    'image-updated',
    'image-deleted',
])

const showViewModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

const editProcessing = ref(false)
const deleteProcessing = ref(false)

const editErrors = ref({})
const deleteErrors = ref({})

const editForm = ref({
    caption: '',
    alt: '',
})

const imageUrl = computed(() => {
    return image?.preview_url || image?.original_url || ''
})

const fullImageUrl = computed(() => {
    return image?.original_url || image?.preview_url || ''
})

const caption = computed(() => {
    return image?.caption || image?.custom_properties?.caption || ''
})

const altText = computed(() => {
    return image?.alt || image?.custom_properties?.alt || caption.value || null
})

const imageSlug = computed(() => {
    return image?.slug || null
})

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

function openViewModal() {
    showViewModal.value = true
}

function closeViewModal() {
    showViewModal.value = false
}

function openEditModal() {
    editForm.value = {
        caption: caption.value,
        alt: image?.alt || image?.custom_properties?.alt || null,
    }

    editErrors.value = {}
    showEditModal.value = true
}

function closeEditModal(force = false) {
    if (editProcessing.value && !force) return

    showEditModal.value = false

    editForm.value = {
        caption: '',
        alt: '',
    }

    editErrors.value = {}
}

async function updateImage() {
    if (editProcessing.value) return

    if (!imageSlug.value) {
        editErrors.value.general = t("admin.components.news.newsImageGalleryDraftImageCard.messages.missingSlug")
        return
    }

    editProcessing.value = true
    editErrors.value = {}

    try {
        const response = await axios.patch(
            route('back-office.medias.quick-update', {
                slug: imageSlug.value,
            }),
            {
                caption: editForm.value.caption || '',
                alt: editForm.value.alt || '',
            }
        )

        if (response?.data?.status === 'error') {
            editErrors.value.general = response?.data?.message || t("admin.components.news.newsImageGalleryDraftImageCard.messages.failToUpdateImage")
            return
        }

        const updatedImage = response?.data?.media

        if (!updatedImage?.id) {
            editErrors.value.general = t("admin.components.news.newsImageGalleryDraftImageCard.messages.invalidResponseAfterImageUpdate")
            return
        }

        emit('image-updated', {
            ...image,
            ...updatedImage,
        })

        closeEditModal(true)
    } catch (error) {
        editErrors.value = normalizeErrors(error)
    } finally {
        editProcessing.value = false
    }
}

function openDeleteModal() {
    deleteErrors.value = {}
    showDeleteModal.value = true
}

function closeDeleteModal(force = false) {
    if (deleteProcessing.value && !force) return

    showDeleteModal.value = false
    deleteErrors.value = {}
}

async function deleteImage() {
    if (deleteProcessing.value) return

    if (!imageSlug.value) {
        deleteErrors.value.general = t("admin.components.news.newsImageGalleryDraftImageCard.messages.missingSlug")
        return
    }

    deleteProcessing.value = true
    deleteErrors.value = {}

    try {
        const response = await axios.delete(
            route('back-office.medias.delete', {
                slug: imageSlug.value,
            })
        )

        if (response?.data?.status === 'error') {
            deleteErrors.value.general = response?.data?.message || t("admin.components.news.newsImageGalleryDraftImageCard.messages.failToDeleteImage")
            return
        }

        emit('image-deleted', image)

        closeDeleteModal(true)
    } catch (error) {
        deleteErrors.value = normalizeErrors(error)
    } finally {
        deleteProcessing.value = false
    }
}
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-white shadow-sm border border-gray-300">
        <div class="relative aspect-square bg-gray-100">
            <img :src="imageUrl" :alt="altText" class="h-full w-full object-contain">

            <div v-if="sequenceMode"
                class="absolute left-2 top-2 z-10 flex h-8 min-w-8 items-center justify-center rounded-full bg-black/70 px-2 text-xs font-semibold text-white">
                {{ index + 1 }}
            </div>

            <div class="absolute right-2 top-2 z-10 flex items-center gap-1">
                <button type="button" title="View"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-black/70 text-xs text-white hover:bg-black"
                    @mousedown.stop @click.stop="openViewModal">
                    <FontAwesomeIcon icon="eye" />
                </button>

                <button type="button" title="Edit"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/90 text-xs text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="editProcessing || deleteProcessing" @mousedown.stop @click.stop="openEditModal">
                    <FontAwesomeIcon icon="pen-to-square" />
                </button>

                <button type="button" title="Delete"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-600/90 text-xs text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="editProcessing || deleteProcessing" @mousedown.stop @click.stop="openDeleteModal">
                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" class="animate-spin" />

                    <FontAwesomeIcon v-else icon="trash" />
                </button>
            </div>
        </div>

        <div v-if="caption" class="p-3 text-sm text-gray-700">
            <span class="font-medium">{{ t("admin.components.news.newsImageGalleryDraftImageCard.labels.captionWithColon") }}</span>
            {{ caption }}
        </div>

    </div>


    <Teleport to="body">
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 px-4"
                @click.self="closeViewModal">
                <Transition enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4">
                    <div class="relative w-full max-w-5xl">
                        <button type="button"
                            class="absolute -right-2 -top-10 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-gray-700 hover:bg-gray-100"
                            @click="closeViewModal">
                            <FontAwesomeIcon icon="xmark" />
                        </button>

                        <img :src="fullImageUrl" :alt="altText"
                            class="mx-auto max-h-[80vh] max-w-full rounded-xl bg-white object-contain">

                        <div v-if="caption" class="mt-3 rounded-lg bg-white p-3 text-sm text-gray-700">
                            <span class="font-medium">{{ t("admin.components.news.newsImageGalleryDraftImageCard.labels.captionWithColon") }}</span>
                            {{ caption}}
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                @click.self="closeEditModal">
                <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ t("admin.components.news.newsImageGalleryDraftImageCard.labels.editImage") }}
                        </h2>

                        <button type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                            @click="closeEditModal">
                            <FontAwesomeIcon icon="xmark" />
                        </button>
                    </div>

                    <form @submit.prevent="updateImage">
                        <div v-if="editErrors.general" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-600">
                            {{ editErrors.general }}
                        </div>

                        <div class="space-y-4">
                            <div>
                                <div class="mb-1 text-sm text-gray-500">
                                    {{ t("common.labels.preview") }}
                                </div>

                                <img :src="imageUrl" :alt="altText"
                                    class="h-50 rounded-lg border object-cover border border-gray-300">
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    {{ t("common.labels.caption") }}
                                </label>

                                <input v-model="editForm.caption" type="text"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="Enter caption">

                                <div v-if="editErrors.caption" class="mt-1 text-sm text-red-600">
                                    {{ editErrors.caption }}
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    {{ t("common.labels.altText") }}
                                </label>

                                <input v-model="editForm.alt" type="text"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="Enter alt text">

                                <div v-if="editErrors.alt" class="mt-1 text-sm text-red-600">
                                    {{ editErrors.alt }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="editProcessing" @click="closeEditModal">
                                {{ t("common.actions.cancel") }}
                            </button>

                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="editProcessing">
                                <FontAwesomeIcon v-if="editProcessing" icon="spinner" class="animate-spin" />

                                <span>
                                    {{ editProcessing ? t("admin.components.news.newsImageGalleryDraftImageCard.actions.updating") : t("common.actions.update") }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                @click.self="closeDeleteModal">
                <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ t("common.modals.deleteConfirmation") }}
                        </h2>

                        <button type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                            @click="closeDeleteModal">
                            <FontAwesomeIcon icon="xmark" />
                        </button>
                    </div>

                    <div v-if="deleteErrors.general" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-600">
                        {{ deleteErrors.general }}
                    </div>

                    <div class="mb-4 flex gap-3">
                        <img :src="imageUrl" :alt="altText" class="h-50 rounded-lg border object-cover">

                        <div>
                            <p class="text-sm font-medium text-gray-800">
                                {{ t("common.modals.areYouSureYouWantToDeleteThis") }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="deleteProcessing" @click="closeDeleteModal">
                            {{ t("common.actions.cancel") }}
                        </button>

                        <button type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="deleteProcessing" @click="deleteImage">
                            <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" class="animate-spin" />

                            <FontAwesomeIcon v-else icon="trash" />

                            <span>
                                {{ deleteProcessing ? t("common.actions.deleting") : t("common.actions.delete") }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
