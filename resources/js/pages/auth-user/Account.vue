<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'

import { onMounted, nextTick, ref } from 'vue'
import { Head, useForm, router as inertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'

import {
    showPassword,
    showConfirmPassword,
    showCurrentPassword,
    togglePasswordVisibility,
    toggleConfirmPasswordVisibility,
    toggleCurrentPasswordVisibility
} from '@/composables/usePassword'

FontAwesomeLibrary.add(faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { user } = defineProps({
    user: Object,
})

const activeTab = ref('info')

const accountUpdateForm = useForm({
    name: user?.name || '',
    email: user?.email || '',
    password: '',
    current_password: '',
    password_confirmation: '',
    change_password: false
})

function validateForm() {
    accountUpdateForm.clearErrors()

    let valid = true

    if (!accountUpdateForm.name || accountUpdateForm.name.trim() === '') {
        accountUpdateForm.setError('name', 'Name is required.')
        valid = false
    }

    if (!accountUpdateForm.email || accountUpdateForm.email.trim() === '') {
        accountUpdateForm.setError('email', 'Email is required.')
        valid = false
    }

    if (accountUpdateForm.change_password) {
        if (!accountUpdateForm.current_password || accountUpdateForm.current_password.trim() === '') {
            accountUpdateForm.setError('current_password', 'Current password is required.')
            valid = false
        }

        if (!accountUpdateForm.password || accountUpdateForm.password.trim() === '') {
            accountUpdateForm.setError('password', 'Password is required.')
            valid = false
        }

        if (!accountUpdateForm.password_confirmation || accountUpdateForm.password_confirmation.trim() === '') {
            accountUpdateForm.setError('password_confirmation', 'Password confirmation is required.')
            valid = false
        } else if (accountUpdateForm.password !== accountUpdateForm.password_confirmation) {
            accountUpdateForm.setError('password_confirmation', 'Password confirmation does not match.')
            valid = false
        }
    }

    return valid
}

function handleAccountUpdate() {
    if (accountUpdateForm.processing) return
    if (!validateForm()) return

    inertiaJsRoute.post(route('auth-user.account.update'), {
        ...accountUpdateForm.data(),
        _method: 'patch'
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Account', active: true },
            ],
        })
    )
})
</script>

<template>
    <Head title="Account" />

    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <div class="border-b border-gray-200 flex gap-4 px-2">
                <button @click="activeTab = 'info'" class="px-4 py-2 text-sm font-medium transition" :class="activeTab === 'info'
                    ? 'text-blue-600 border-b-2 border-blue-500'
                    : 'text-gray-500 hover:text-gray-700'">
                    Info
                </button>

                <button @click="activeTab = 'update'" class="px-4 py-2 text-sm font-medium transition" :class="activeTab === 'update'
                    ? 'text-blue-600 border-b-2 border-blue-500'
                    : 'text-gray-500 hover:text-gray-700'">
                    Update
                </button>

                <button @click="activeTab = 'logs'" class="px-4 py-2 text-sm font-medium transition" :class="activeTab === 'logs'
                    ? 'text-blue-600 border-b-2 border-blue-500'
                    : 'text-gray-500 hover:text-gray-700'">
                    Activity Logs
                </button>
            </div>

            <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150"
                leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1" mode="out-in">

                <div v-if="activeTab === 'info'" class="p-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="w-32 h-32">
                            <MediaRenderer v-if="user?.profile_image" :media="user?.profile_image" />

                            <img v-else :src="'/uploads/icons/auth/user.png'" alt="User profile image"
                                class="object-cover rounded-xl border border-gray-200" />
                        </div>

                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="font-medium text-gray-600">
                                    Name:
                                </span>
                                {{ user?.name }}
                            </div>

                            <div>
                                <span class="font-medium text-gray-600">
                                    Email:
                                </span>
                                {{ user?.email }}
                            </div>

                            <div>
                                <span class="font-medium text-gray-600">
                                    Email Verified:
                                </span>

                                {{
                                    user?.email_verified_at
                                        ? formatDateTime(user.email_verified_at)
                                        : 'Not verified'
                                }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'update'" class="p-6">
                    <form @submit.prevent="handleAccountUpdate" class="space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input v-model="accountUpdateForm.name" class="w-full border rounded px-3 py-2"
                                    :class="accountUpdateForm.errors.name ? 'border-red-500' : 'border-gray-300'" />

                                <p v-if="accountUpdateForm.errors.name" class="text-red-500 text-sm mt-1">
                                    {{ accountUpdateForm.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    Email
                                    <span class="text-red-500">*</span>
                                </label>

                                <input v-model="accountUpdateForm.email" class="w-full border rounded px-3 py-2"
                                    :class="accountUpdateForm.errors.email ? 'border-red-500' : 'border-gray-300'" />

                                <p v-if="accountUpdateForm.errors.email" class="text-red-500 text-sm mt-1">
                                    {{ accountUpdateForm.errors.email }}
                                </p>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="accountUpdateForm.change_password" type="checkbox"
                                class="accent-blue-600" />

                            <span class="text-sm">
                                Change Password
                            </span>
                        </label>

                        <div v-if="accountUpdateForm.change_password" class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    Current Password
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <input v-model="accountUpdateForm.current_password"
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        class="w-full border rounded px-3 py-2 pr-10"
                                        :class="accountUpdateForm.errors.current_password ? 'border-red-500' : 'border-gray-300'" />

                                    <button type="button" @click="toggleCurrentPasswordVisibility"
                                        class="absolute right-2 top-2 text-gray-500"
                                        :aria-label="showCurrentPassword ? 'Hide current password' : 'Show current password'">
                                        <FontAwesomeIcon :icon="showCurrentPassword ? 'eye-slash' : 'eye'" />
                                    </button>
                                </div>

                                <p v-if="accountUpdateForm.errors.current_password" class="text-red-500 text-sm mt-1">
                                    {{ accountUpdateForm.errors.current_password }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    New Password
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <input v-model="accountUpdateForm.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="w-full border rounded px-3 py-2 pr-10"
                                        :class="accountUpdateForm.errors.password ? 'border-red-500' : 'border-gray-300'" />

                                    <button type="button" @click="togglePasswordVisibility"
                                        class="absolute right-2 top-2 text-gray-500"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'">
                                        <FontAwesomeIcon :icon="showPassword ? 'eye-slash' : 'eye'" />
                                    </button>
                                </div>

                                <p v-if="accountUpdateForm.errors.password" class="text-red-500 text-sm mt-1">
                                    {{ accountUpdateForm.errors.password }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    Confirm Password
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <input v-model="accountUpdateForm.password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        class="w-full border rounded px-3 py-2 pr-10"
                                        :class="accountUpdateForm.errors.password_confirmation ? 'border-red-500' : 'border-gray-300'" />

                                    <button type="button" @click="toggleConfirmPasswordVisibility"
                                        class="absolute right-2 top-2 text-gray-500"
                                        :aria-label="showConfirmPassword ? 'Hide confirm password' : 'Show confirm password'">
                                        <FontAwesomeIcon :icon="showConfirmPassword ? 'eye-slash' : 'eye'" />
                                    </button>
                                </div>

                                <p v-if="accountUpdateForm.errors.password_confirmation"
                                    class="text-red-500 text-sm mt-1">
                                    {{ accountUpdateForm.errors.password_confirmation }}
                                </p>
                            </div>
                        </div>

                        <button type="submit" :disabled="accountUpdateForm.processing"
                            class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded flex items-center gap-2">
                            <FontAwesomeIcon v-if="accountUpdateForm.processing" icon="spinner" spin />

                            {{
                                accountUpdateForm.processing
                                    ? 'Updating...'
                                    : 'Update'
                            }}
                        </button>
                    </form>
                </div>

                <div v-else key="logs" class="p-4">
                    <RecentActivities :model-slug="'user'" :model="user" />
                </div>

            </transition>
        </div>
    </div>
</template>
