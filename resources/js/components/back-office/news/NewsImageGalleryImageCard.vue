<script setup>
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'

import { ref } from 'vue'
import { router as inertiaJsRoute, useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faSpinner, faXmark } from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(
    faTrash, faPen, faEye, faSpinner, faXmark
)

const { t } = useTranslate()

const { news, galleryImage, sequenceMode } = defineProps({
    news: {
        type: Object,
        required: true,
    },
    galleryImage: {
        type: Object,
        required: true,
    },
    sequenceMode: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['refresh-gallery-images'])

const selectedGalleryImage = ref(null)

const showUpdateModal = ref(false)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const updateForm = useForm({
    caption: galleryImage?.custom_properties?.caption || '',
    alt: galleryImage?.custom_properties?.alt || '',
    order_column: galleryImage?.order_column || '',
})

const openUpdateModal = () => {
    updateForm.caption = galleryImage?.custom_properties?.caption || ''
    updateForm.alt = galleryImage?.custom_properties?.alt || ''
    updateForm.order_column = galleryImage?.order_column || ''
    updateForm.clearErrors()

    showUpdateModal.value = true
}

const closeUpdateModal = (force = false) => {
    if (updateForm.processing && !force) return

    showUpdateModal.value = false
}

const updateGalleryImage = () => {
    if (updateForm.processing) return

    updateForm.patch(
        route('back-office.news.gallery-images.update', {
            slug: news?.slug,
            mediaSlug: galleryImage?.slug,
        }),
        {
            preserveScroll: true,
            preserveState: true,

            onSuccess: () => {
                closeUpdateModal(true)
                emit('refresh-gallery-images')
            },
        }
    )
}

const openDeleteModal = () => {
    showDeleteModal.value = true
}

const closeDeleteModal = (force = false) => {
    if (deleteProcessing.value && !force) return

    showDeleteModal.value = false
}

const deleteGalleryImage = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    inertiaJsRoute.patch(
        route('back-office.news.gallery-images.delete', {
            slug: news?.slug,
            mediaSlug: galleryImage?.slug,
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,

            onSuccess: () => {
                closeDeleteModal(true)
                emit('refresh-gallery-images')
            },

            onFinish: () => {
                deleteProcessing.value = false
            },
        }
    )
}
</script>

<template>
    <div class="relative overflow-hidden rounded-lg border border-gray-100">
        <button type="button" class="block w-full" :class="sequenceMode ? 'cursor-move' : 'cursor-pointer'"
            @click="!sequenceMode && (selectedGalleryImage = galleryImage)">
            <MediaRenderer v-if="galleryImage" :media="galleryImage" :mediaClass="'w-full h-40 object-cover'" />
        </button>

        <div class="absolute right-2 top-2 flex items-center gap-2">
            <button v-if="!sequenceMode" type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-gray-700 shadow hover:bg-gray-100"
                @click.stop="selectedGalleryImage = galleryImage">
                <FontAwesomeIcon :icon="faEye" />
            </button>

            <button v-if="!sequenceMode" type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-500 text-white shadow hover:bg-yellow-600 disabled:opacity-60"
                :disabled="updateForm.processing" @click.stop="openUpdateModal">
                <FontAwesomeIcon :icon="faPen" />
            </button>

            <button v-if="!sequenceMode" type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full bg-red-600 text-white shadow hover:bg-red-700 disabled:opacity-60"
                :disabled="deleteProcessing" @click.stop="openDeleteModal">
                <FontAwesomeIcon :icon="faTrash" />
            </button>
        </div>
    </div>

    <Teleport to="body">
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="selectedGalleryImage" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                @click.self="selectedGalleryImage = null">
                <Transition enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4">

                    <div class="relative w-full max-w-5xl">
                        <button type="button"
                            class="absolute -top-10 right-0 flex h-8 w-8 items-center justify-center rounded-full text-white hover:bg-white/10"
                            @click="selectedGalleryImage = null">
                            <FontAwesomeIcon :icon="faXmark" class="text-2xl" />
                        </button>

                        <img :src="selectedGalleryImage.original_url || selectedGalleryImage.preview_url"
                            :alt="selectedGalleryImage.custom_properties?.alt || selectedGalleryImage.name"
                            class="max-h-[85vh] w-full rounded-lg object-contain">

                        <div v-if="selectedGalleryImage.custom_properties?.caption"
                            class="mt-3 text-center text-sm text-white">
                            {{ selectedGalleryImage.custom_properties.caption }}
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showUpdateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
                @click.self="closeUpdateModal">
                <Transition enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4">
                    <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-lg">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">
                                {{ t("admin.components.news.newsImageGalleryImageCard.labels.updateGalleryImage") }}
                            </h3>

                            <button type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                @click="closeUpdateModal">
                                <FontAwesomeIcon :icon="faXmark" />
                            </button>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    {{ t("common.labels.caption") }}
                                </label>

                                <input v-model="updateForm.caption" type="text"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">

                                <p v-if="updateForm.errors.caption" class="mt-1 text-xs text-red-600">
                                    {{ updateForm.errors.caption }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    {{ t("common.labels.altText") }}
                                </label>

                                <input v-model="updateForm.alt" type="text"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">

                                <p v-if="updateForm.errors.alt" class="mt-1 text-xs text-red-600">
                                    {{ updateForm.errors.alt }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">
                                    {{ t("common.labels.orderColumn") }}
                                </label>

                                <input v-model="updateForm.order_column" type="number" min="1"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">

                                <p v-if="updateForm.errors.order_column" class="mt-1 text-xs text-red-600">
                                    {{ updateForm.errors.order_column }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                                :disabled="updateForm.processing" @click="closeUpdateModal">
                                {{ t("common.actions.cancel") }}
                            </button>

                            <button type="button"
                                class="rounded-md bg-yellow-500 px-4 py-2 text-sm text-white hover:bg-yellow-600 disabled:opacity-60"
                                :disabled="updateForm.processing" @click="updateGalleryImage">
                                <FontAwesomeIcon v-if="updateForm.processing" :icon="faSpinner"
                                    class="mr-1 animate-spin" />
                                {{ t("common.actions.update") }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
                @click.self="closeDeleteModal">
                <Transition enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4">
                    <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-lg">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">
                                Delete Gallery Image?
                            </h3>

                            <button type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                @click="closeDeleteModal">
                                <FontAwesomeIcon :icon="faXmark" />
                            </button>
                        </div>

                        <p class="mt-2 text-sm text-gray-600">
                            Are you sure you want to delete this gallery image?
                        </p>

                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                                :disabled="deleteProcessing" @click="closeDeleteModal">
                                Cancel
                            </button>

                            <button type="button"
                                class="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-60"
                                :disabled="deleteProcessing" @click="deleteGalleryImage">
                                <FontAwesomeIcon v-if="deleteProcessing" :icon="faSpinner" class="mr-1 animate-spin" />
                                Delete
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
