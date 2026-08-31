<script setup>
import { ref, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { router } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash,
    faGripVertical,
    faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faGripVertical, faSpinner)

const { t } = useTranslate()

const {
    title,
    items,
    news,
    fieldName
} = defineProps({
    title: String,
    items: { type: Array, default: () => [] },
    news: Object,
    fieldName: String,
})

const emit = defineEmits([
    'update:items',
    'sequence-change'
])

const draggableItems = computed({
    get() {
        return items
    },
    set(value) {
        emit('update:items', value)
        emit('sequence-change', fieldName, value)
    }
})

const showDeleteModal = ref(false)
const selectedItem = ref(null)
const deleteProcessing = ref(false)

function openDeleteModal(item) {
    selectedItem.value = item
    showDeleteModal.value = true
}

function closeDeleteModal() {
    if (deleteProcessing.value) return

    selectedItem.value = null
    showDeleteModal.value = false
}

function handleDelete() {
    if (!selectedItem.value || deleteProcessing.value) return

    const deletingItem = selectedItem.value

    deleteProcessing.value = true

    router.delete(
        route('back-office.news.news-placements.delete', {
            slug: news?.slug,
            newsPlacementSlug: deletingItem?.slug
        }),
        {
            preserveScroll: true,
            preserveState: true,

            onSuccess: () => {
                const updatedItems = items.filter(
                    item => item.id !== deletingItem?.id
                )

                emit('update:items', updatedItems)
                emit('sequence-change', fieldName, updatedItems)

                showDeleteModal.value = false
                selectedItem.value = null
            },

            onFinish: () => {
                deleteProcessing.value = false
            }
        }
    )
}

function isCurrentNewsItem(item) {
    return item?.news_id === news?.id ||
        item?.news?.id === news?.id ||
        item?.news?.slug === news?.slug ||
        item?.slug === news?.slug
}
</script>

<template>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4 news-placement">

        <h3 class="text-base font-semibold border-b pb-2">
            {{ title }}
        </h3>

        <div v-if="!draggableItems.length"
            class="border border-dashed border-gray-300 rounded-xl p-6 text-center text-sm text-gray-500">
            {{ t("common.labels.noRecordsFound") }}
        </div>

        <VueDraggable v-else v-model="draggableItems" handle=".drag-handle" ghost-class="drag-ghost"
            chosen-class="drag-chosen" class="space-y-3">
            <div v-for="(item, index) in draggableItems" :key="item.id"
                class="border rounded-xl p-4 transition-all duration-200" :class="[
                    isCurrentNewsItem(item)
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 bg-white hover:border-gray-300'
                ]">
                <div class="flex items-start justify-between gap-4">

                    <div class="flex items-start gap-3 min-w-0">

                        <button type="button" class="drag-handle text-gray-400 hover:text-gray-600 cursor-move mt-1">
                            <FontAwesomeIcon icon="fa-solid fa-grip-vertical" />
                        </button>

                        <span
                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0"
                            :class="[
                                isCurrentNewsItem(item)
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700'
                            ]">
                            {{ index + 1 }}
                        </span>

                        <div class="min-w-0 space-y-1">

                            <p class="font-semibold text-sm text-gray-800">
                                {{ item?.news?.title ?? 'Untitled News' }}
                            </p>

                            <p v-if="isCurrentNewsItem(item)"
                                class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">
                                {{ t("admin.components.news.newsPlacementSortableList.labels.createNews") }}
                            </p>

                        </div>

                    </div>

                    <button type="button"
                        class="w-9 h-9 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition shrink-0"
                        @click="openDeleteModal(item)">
                        <FontAwesomeIcon icon="fa-solid fa-trash" />
                    </button>

                </div>
            </div>
        </VueDraggable>

    </div>

    <Teleport to="body">
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center px-4">
                <Transition enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4">
                    <div class="bg-white rounded-2xl w-full max-w-md p-6 space-y-5 shadow-xl">

                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ t("common.modals.deleteConfirmation") }}
                            </h3>

                            <p class="text-sm text-gray-500">
                                {{ t("common.modals.areYouSureYouWantToDeleteThis") }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button"
                                class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50"
                                :disabled="deleteProcessing" @click="closeDeleteModal">
                                {{ t("common.actions.cancel") }}
                            </button>

                            <button type="button"
                                class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm hover:bg-red-700 disabled:opacity-70"
                                :disabled="deleteProcessing" @click="handleDelete">
                                <span v-if="deleteProcessing" class="inline-flex items-center gap-2">
                                    <FontAwesomeIcon icon="fa-solid fa-spinner" spin />
                                    {{ t("common.actions.deleting") }}
                                </span>

                                <span v-else>
                                    {{ t("common.actions.delete") }}
                                </span>
                            </button>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.drag-ghost {
    opacity: 0.5;
}

.drag-chosen {
    box-shadow: 0 0 0 2px rgb(191 219 254);
}
</style>
