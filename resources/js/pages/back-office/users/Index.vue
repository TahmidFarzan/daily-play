<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/pagination/Pagination.vue'
import InfiniteScrollApiSelect from '@/components/common/multi-select/InfiniteScrollApiSelect.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash,
    faFilter,
    faInfo,
    faPlus,
    faPen,
    faEye,
    faEyeSlash,
    faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useApiClient'

import {
    canUpdateUser,
    canDeleteUser,
    canActiveInactiveUser
} from '@/composables/useUserPermissions'

FontAwesomeLibrary.add(
    faTrash,
    faFilter,
    faInfo,
    faPlus,
    faPen,
    faEye,
    faEyeSlash,
    faSpinner
)

defineOptions({
    layout: Layout
})

const authUser = inject('authUser')

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const statusRow = ref(null)
const statusAction = ref(null)
const showStatusModal = ref(false)

const activeProcessing = ref(null)
const inactiveProcessing = ref(null)

const { users } = defineProps({
    users: Object
})

const paginationOnly = computed(() => {
    if (!users) return {}

    const { data, ...rest } = users

    return rest
})

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    user_permission_id: null,
    date: '',
    search: '',
    is_active: null
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())

    intertiaJsRoute.get(
        route('back-office.users.index'),
        cleanParams,
        {
            replace: true,
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                filterForm.processing = false
            }
        }
    )
}

const confirmDelete = (user) => {
    deletingRow.value = user
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    deletingRow.value = null
    showDeleteModal.value = false
}

const confirmStatus = (user, action) => {
    statusRow.value = user
    statusAction.value = action
    showStatusModal.value = true
}

const closeStatusModal = () => {
    statusRow.value = null
    statusAction.value = null
    showStatusModal.value = false
}

const canUpdate = (user) => canUpdateUser(authUser?.value, user)
const canDelete = (user) => canDeleteUser(authUser?.value, user)
const canActiveInactive = (user) => canActiveInactiveUser(authUser?.value, user)

const handleDelete = (user) => {
    if (!user || deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(
        route('back-office.users.delete', { slug: user.slug }),
        {
            onFinish: () => {
                deleteProcessing.value = false
                closeDeleteModal()
            }
        }
    )
}

const executeStatusAction = () => {
    if (!statusRow.value) return

    const slug = statusRow.value.slug
    const isInactive = statusAction.value === 'inactive'

    if (isInactive) {
        inactiveProcessing.value = slug
    } else {
        activeProcessing.value = slug
    }

    intertiaJsRoute.patch(
        route(isInactive ? 'back-office.users.inactive' : 'back-office.users.active', { slug }),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                closeStatusModal()
            },

            onFinish: () => {
                activeProcessing.value = null
                inactiveProcessing.value = null
            },

            onError: () => {
                activeProcessing.value = null
                inactiveProcessing.value = null
            }
        }
    )
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.per_page = urlParams.get('per_page') || ''
    filterForm.created_by_id = urlParams.get('created_by_id') || ''
    filterForm.user_permission_id = urlParams.get('user_permission_id') || ''
    filterForm.date = urlParams.get('date') || ''
    filterForm.search = urlParams.get('search') || ''

    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Users', active: true }
            ]
        })
    )
})
</script>

<template>
    <Head title="Users" />

    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                Users
            </h2>

            <a :href="route('back-office.users.create')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                <FontAwesomeIcon icon="plus" />
                Create
            </a>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <InfiniteScrollApiSelect :form="filterForm" fieldName="per_page"
                    :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                    placeholder="Per Page" />

                <InfiniteScrollApiSelect :form="filterForm" fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                    placeholder="Created By" />

                <InfiniteScrollApiSelect :form="filterForm" fieldName="user_permission_id"
                    :selectedItem="filterForm.user_permission_id" :apiUrl="route('search.user-permissions')"
                    :multiple="false" placeholder="User Permission" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search" placeholder="Search by name or email..."
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <div class="flex items-center gap-4 text-sm">
                    <label class="flex items-center gap-1">
                        <input type="radio" v-model="filterForm.is_active" :value="null" />
                        All
                    </label>

                    <label class="flex items-center gap-1">
                        <input type="radio" v-model="filterForm.is_active" :value="true" />
                        Active
                    </label>

                    <label class="flex items-center gap-1">
                        <input type="radio" v-model="filterForm.is_active" :value="false" />
                        Inactive
                    </label>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon icon="filter" />
                    {{ filterForm.processing ? 'Applying...' : 'Apply Filter' }}
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Created At</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in users?.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">{{ index + 1 }}</td>

                            <td class="px-4 py-3 font-medium">
                                {{ item.name || 'N/A' }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ item.created_at ? formatDateTime(item.created_at) : 'N/A' }}
                            </td>

                            <td class="px-4 py-3">
                                <span :class="item.is_active ? 'text-green-600' : 'text-red-500'"
                                    class="text-xs font-medium">
                                    {{ item.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a :href="route('back-office.users.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                        title="View Details">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a v-if="canUpdate(item)" :href="route('back-office.users.edit', { slug: item.slug })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border"
                                        title="Edit">
                                        <FontAwesomeIcon icon="pen" />
                                    </a>

                                    <button v-if="item.is_active && canActiveInactive(item)" type="button"
                                        @click="confirmStatus(item, 'inactive')"
                                        :disabled="inactiveProcessing === item.slug"
                                        class="p-2 rounded-md text-gray-600 hover:bg-gray-100 border disabled:opacity-60 disabled:cursor-not-allowed"
                                        title="Deactivate">
                                        <FontAwesomeIcon v-if="inactiveProcessing === item.slug" icon="spinner" spin />
                                        <FontAwesomeIcon v-else icon="eye-slash" />
                                    </button>

                                    <button v-if="!item.is_active && canActiveInactive(item)" type="button"
                                        @click="confirmStatus(item, 'active')"
                                        :disabled="activeProcessing === item.slug"
                                        class="p-2 rounded-md text-green-600 hover:bg-green-50 border disabled:opacity-60 disabled:cursor-not-allowed"
                                        title="Activate">
                                        <FontAwesomeIcon v-if="activeProcessing === item.slug" icon="spinner" spin />
                                        <FontAwesomeIcon v-else icon="eye" />
                                    </button>

                                    <button v-if="!item.is_active && canDelete(item)" type="button" @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border"
                                        title="Delete">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!users?.data?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No users found
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
                                Delete User
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action cannot be undone.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeDeleteModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button type="button" @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    {{ deleteProcessing ? 'Deleting...' : 'Delete' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showStatusModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                        <h3 class="text-lg font-semibold">
                            {{ statusAction === 'active' ? 'Activate User' : 'Deactivate User' }}
                        </h3>

                        <p class="font-medium">
                            {{ statusRow?.name }}
                        </p>

                        <p class="text-sm text-gray-500">
                            {{
                                statusAction === 'active'
                                    ? 'Are you sure you want to activate this user?'
                                    : 'Are you sure you want to deactivate this user?'
                            }}
                        </p>

                        <div class="flex justify-end gap-2">
                            <button @click="closeStatusModal" class="px-4 py-2 bg-gray-100 rounded">
                                Cancel
                            </button>

                            <button type="button" @click="executeStatusAction" :disabled="activeProcessing || inactiveProcessing"
                                :class="[
                                    statusAction === 'inactive'
                                        ? 'bg-yellow-500 hover:bg-yellow-600'
                                        : 'bg-green-500 hover:bg-green-600',
                                    'px-4 py-2 text-white rounded-md text-sm flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed'
                                ]">
                                <FontAwesomeIcon v-if="activeProcessing || inactiveProcessing" icon="spinner" spin />

                                {{
                                    activeProcessing || inactiveProcessing
                                        ? (statusAction === 'inactive' ? 'Deactivating...' : 'Activating...')
                                        : (statusAction === 'inactive' ? 'Deactivate' : 'Activate')
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
