<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faTrash,
    faPen,
    faEye,
    faEyeSlash,
    faSpinner,
    faCircleCheck
} from '@fortawesome/free-solid-svg-icons'

import { formatDate, formatDateTime } from '@/composables/useDateTime'

import { canUpdateUser, canDeleteUser, canActiveInactiveUser } from '@/composables/useUserPermissions'

FontAwesomeLibrary.add(
    faTrash,
    faPen,
    faEye,
    faEyeSlash,
    faSpinner,
    faCircleCheck
)

defineOptions({
    layout: Layout
})

const authUser = inject('authUser')

const showDeleteModal = ref(false)
const showStatusModal = ref(false)
const statusAction = ref(null)
const deleteProcessing = ref(false)
const activeProcessing = ref(false)
const inactiveProcessing = ref(false)

const { user } = defineProps({
    user: Object
})

const pageTitle = computed(() => `${user?.name} Details`)
const groupedPermissions = computed(() => {
    const permissions = user?.user_permissions || []

    return permissions.reduce((groups, permission) => {
        const module = permission.module

        if (!groups[module]) {
            groups[module] = []
        }

        groups[module].push(permission)

        return groups
    }, {})
})

const canUpdate = (user) => canUpdateUser(authUser?.value, user)
const canDelete = (user) => canDeleteUser(authUser?.value, user)
const canActiveInactive = (user) => canActiveInactiveUser(authUser?.value, user)

const closeDeleteModal = () => { showDeleteModal.value = false }

const openStatusModal = (action) => {
    statusAction.value = action
    showStatusModal.value = true
}

const closeStatusModal = () => {
    showStatusModal.value = false
    statusAction.value = null
}

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.users.delete', { slug: user?.slug }), {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false
            closeDeleteModal()
        }
    })
}

const executeStatusAction = () => {
    if (activeProcessing.value || inactiveProcessing.value) return

    const isInactive = statusAction.value === 'inactive'

    if (isInactive) {
        inactiveProcessing.value = true
    } else {
        activeProcessing.value = true
    }

    intertiaJsRoute.patch(
        route(isInactive ? 'back-office.users.inactive' : 'back-office.users.active', { slug: user?.slug }), {},
        {
            preserveScroll: true,

            onSuccess: () => {
                closeStatusModal()
            },

            onFinish: () => {
                activeProcessing.value = false
                inactiveProcessing.value = false
            },

            onError: () => {
                activeProcessing.value = false
                inactiveProcessing.value = false
            }
        }
    )
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: 'Users',
                    href: route('back-office.users.index')
                },
                {
                    text: pageTitle.value,
                    active: true
                }
            ]
        })
    )
})
</script>

<template>
    <Head :title="pageTitle" />

    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                User Details
            </h2>

            <div class="flex gap-2">
                <a v-if="canUpdate(user)" :href="route('back-office.users.edit', { slug: user?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    Edit
                </a>

                <button v-if="user?.is_active && canActiveInactive(user)" type="button"
                    @click="openStatusModal('inactive')" :disabled="inactiveProcessing"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 disabled:opacity-60">
                    <FontAwesomeIcon v-if="inactiveProcessing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="eye-slash" />
                    {{ inactiveProcessing ? 'Inactivating...' : 'Inactive' }}
                </button>

                <button v-if="!user?.is_active && canActiveInactive(user)" type="button"
                    @click="openStatusModal('active')" :disabled="activeProcessing"
                    class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded-md flex items-center gap-2 disabled:opacity-60">
                    <FontAwesomeIcon v-if="activeProcessing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="eye" />
                    {{ activeProcessing ? 'Activating...' : 'Active' }}
                </button>

                <button v-if="!user?.is_active && canDelete(user)" type="button" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    Delete
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                Basic Information
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Name</span>
                        <span class="font-medium">{{ user?.name || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Email</span>
                        <span class="font-medium">
                            {{ user?.email || 'N/A' }}
                            <FontAwesomeIcon v-if="user?.email_verified_at" icon="circle-check" class="text-green-500" />
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Super Admin</span>
                        <span class="font-medium">
                            {{ user?.is_super_admin ? 'Yes' : 'No' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Mobile</span>
                        <span class="font-medium">{{ user?.mobile || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span :class="user?.is_active ? 'text-green-600' : 'text-red-500'" class="font-medium">
                            {{ user?.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 flex items-center justify-center">
                    <img :src="user?.profile_image?.preview_url || '/uploads/icons/auth/user.png'"
                        alt="User profile image"
                        class="w-40 h-40 object-cover rounded-xl border" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Birth Date</span>
                        <span class="font-medium">
                            {{ user?.birth_date ? formatDate(user?.birth_date) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Gender</span>
                        <span class="font-medium">{{ user?.gender?.name || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Age</span>
                        <span class="font-medium">{{ user?.age || 'N/A' }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Religion</span>
                        <span class="font-medium">{{ user?.religion?.name || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Marital Status</span>
                        <span class="font-medium">{{ user?.marital_status?.name || 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-lg p-4 text-sm space-y-2">
                <span class="text-gray-500">User Permissions</span>

                <div class="mt-3">
                    <div v-if="Object.keys(groupedPermissions).length" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div v-for="(permissions, module) in groupedPermissions" :key="module"
                            class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-50 px-4 py-3 font-semibold text-gray-700">
                                {{ module }}
                            </div>

                            <div class="p-4 flex flex-wrap gap-2">
                                <span v-for="permission in permissions" :key="permission.id"
                                    class="px-3 py-1 rounded-full text-xs bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ permission.access }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <span v-else class="font-medium">N/A</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                System Information
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created At</span>
                        <span class="font-medium">
                            {{ user?.created_at ? formatDateTime(user.created_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Created By</span>
                        <span class="font-medium">
                            {{ user?.created_by?.name || 'N/A' }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated At</span>
                        <span class="font-medium">
                            {{ user?.updated_at ? formatDateTime(user.updated_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated By</span>
                        <span class="font-medium">
                            {{ user?.latest_activity_log?.causer?.name || 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                Activity Logs
            </h3>

            <RecentActivities :model-slug="'user'" :model="user" />
        </div>

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
                                {{ user?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action cannot be undone.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeDeleteModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button type="button" @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    {{ deleteProcessing ? 'Deleting...' : 'Delete' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <Transition enter-active-class="transition duration-200" leave-active-class="transition duration-150">
                <div v-if="showStatusModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                        <h3 class="text-lg font-semibold" :class="statusAction === 'inactive'
                            ? 'text-yellow-600'
                            : 'text-sky-600'
                        ">
                            {{ statusAction === 'inactive' ? 'Inactive' : 'Active' }}
                        </h3>

                        <p>{{ user?.name }}</p>

                        <p class="text-sm text-gray-500">
                            {{
                                statusAction === 'inactive'
                                    ? 'Are you sure you want to inactive this user?'
                                    : 'Are you sure you want to activate this user?'
                            }}
                        </p>

                        <div class="flex justify-end gap-2">
                            <button @click="closeStatusModal" class="px-4 py-2 bg-gray-100 rounded-md">
                                Cancel
                            </button>

                            <button @click="executeStatusAction" :disabled="activeProcessing || inactiveProcessing"
                                :class="[
                                    statusAction === 'inactive'
                                        ? 'bg-yellow-500 hover:bg-yellow-600'
                                        : 'bg-sky-500 hover:bg-sky-600',
                                    'px-4 py-2 text-white rounded-md flex items-center gap-2'
                                ]">
                                <FontAwesomeIcon v-if="activeProcessing || inactiveProcessing" icon="spinner" spin />

                                {{
                                    statusAction === 'inactive'
                                        ? (inactiveProcessing ? 'Inactivating...' : 'Inactive')
                                        : (activeProcessing ? 'Activating...' : 'Active')
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
