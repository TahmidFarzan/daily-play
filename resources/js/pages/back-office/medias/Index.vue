<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/pagination/Pagination.vue'
import InfiniteScrollApiSelect from '@/components/common/multi-select/InfiniteScrollApiSelect.vue'

import { ref, computed, onMounted, nextTick } from 'vue'
import { Head, useForm, router as inertiaJsRouter } from '@inertiajs/vue3'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTrash, faFilter, faInfo, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useApiClient'

library.add(faTrash, faFilter, faInfo, faSpinner)

defineOptions({ layout: Layout })

const { medias } = defineProps({
    medias: {
        type: Object,
        default: null,
    },
})

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    subject_type: null,
    date: null,
    search: null,
})

const paginationOnly = computed(() => {
    if (!medias) return {}

    const { data, ...rest } = medias

    return rest
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())

    inertiaJsRouter.get(route('back-office.medias.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false,
    })
}

const confirmDelete = (media) => {
    deletingRow.value = media
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    if (deleteProcessing.value) return

    showDeleteModal.value = false
    deletingRow.value = null
}

const handleDelete = (media) => {
    if (!media || deleteProcessing.value) return

    deleteProcessing.value = true

    inertiaJsRouter.delete(route('back-office.medias.delete', { slug: media?.slug }), {
        onFinish: () => {
            deleteProcessing.value = false
            showDeleteModal.value = false
            deletingRow.value = null
        },
    })
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.created_by_id = urlParams.get('created_by_id') || null
    filterForm.date = urlParams.get('date') || null
    filterForm.search = urlParams.get('search') || null
    filterForm.per_page = urlParams.get('per_page') || null

    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Media', active: true },
            ],
        })
    )
})
</script>

<template>
    <Head title="Media" />

    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                Media
            </h2>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <InfiniteScrollApiSelect :form="filterForm" fieldName="per_page"
                    :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                    selectedLabelKey="name" selectedValueKey="id" apiLabelKey="name" apiValueKey="id"
                    placeholder="Per Page" />

                <InfiniteScrollApiSelect :form="filterForm" fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                    selectedLabelKey="name" selectedValueKey="id" apiLabelKey="name"
                    apiValueKey="id" placeholder="Created By" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search" placeholder="Search by name..."
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="filter" />

                    {{ filterForm.processing ? 'Applying...' : 'Apply Filter' }}
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                #
                            </th>

                            <th class="px-4 py-3 text-left">
                                Name
                            </th>

                            <th class="px-4 py-3 text-left">
                                Collection Name
                            </th>

                            <th class="px-4 py-3 text-left">
                                Created At
                            </th>

                            <th class="px-4 py-3 text-right">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in medias?.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                {{ index + 1 }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ item.name ?? 'N/A' }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item.collection_name ?? 'N/A' }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ item.created_at ? formatDateTime(item.created_at) : 'N/A' }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a :href="route('back-office.medias.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                        title="View Details">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <button @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border"
                                        title="Delete">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!medias?.data?.length">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                No records found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ModelPagination :pagination="paginationOnly" />

        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div v-if="showDeleteModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-red-600">
                                Delete Media
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action cannot be undone.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="closeDeleteModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />

                                    {{ deleteProcessing ? 'Deleting...' : 'Delete' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
