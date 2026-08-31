<script setup>
import layout from '@/pages/layouts/PublicLayout.vue'

import { Head, useForm } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSpinner)

defineOptions({ layout })

const resetRequestForm = useForm({
    email: '',
})

function validateForm() {
    resetRequestForm.clearErrors()

    let valid = true

    if (!resetRequestForm.email || resetRequestForm.email.trim() === '') {
        resetRequestForm.setError('email', 'Email is required.')
        valid = false
    } else if (resetRequestForm.email.length > 200) {
        resetRequestForm.setError('email', 'Email must not exceed 200 characters.')
        valid = false
    }

    return valid
}

function handleForgotPassword() {
    if (resetRequestForm.processing) return
    if (!validateForm()) return

    resetRequestForm.post(route('forgot-password.submit'), {
        preserveScroll: true,

        onSuccess: () => {
            resetRequestForm.reset()
            resetRequestForm.clearErrors()
        },

        onError: (errors) => {
            resetRequestForm.clearErrors()
            resetRequestForm.setError(errors)
        }
    })
}
</script>

<template>
    <Head title="Forgot Password" />

    <div class="auth-entry min-h-screen flex items-center justify-center px-4">
        <div class="auth-card w-full max-w-md bg-white shadow rounded-2xl p-6 border border-gray-200">
            <div class="text-center mb-6">
                <img :src="'/uploads/icons/auth/forgot-password.png'" alt="Forgot Password"
                    class="mx-auto mb-3 w-16 h-16 object-contain" />

                <h2 class="text-xl font-semibold text-blue-600">
                    Forgot Password
                </h2>

                <p class="text-sm text-gray-500">
                    Enter your email address and we'll send you a password reset link.
                </p>
            </div>

            <form @submit.prevent="handleForgotPassword">
                <div class="mb-4">
                    <input id="email" v-model="resetRequestForm.email" type="email"
                        placeholder="Enter your email address" autofocus
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="resetRequestForm.errors.email ? 'border-red-500' : 'border-gray-300'" />

                    <p v-if="resetRequestForm.errors.email" class="text-sm text-red-500 mt-1">
                        {{ resetRequestForm.errors.email }}
                    </p>
                </div>

                <button type="submit" :disabled="resetRequestForm.processing"
                    class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <FontAwesomeIcon v-if="resetRequestForm.processing" icon="spinner" spin />

                    {{
                        resetRequestForm.processing
                            ? 'Sending...'
                            : 'Send Reset Link'
                    }}
                </button>
            </form>

            <div class="mt-4 text-center text-sm">
                Already have an account?

                <a :href="route('login')" class="text-blue-600 hover:underline ml-1">
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.auth-entry {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 56%, #dbeafe 140%);
}

.auth-card {
    border-color: var(--news-border);
    box-shadow: var(--news-shadow);
}

.auth-card input {
    min-height: 2.75rem;
    border-radius: var(--news-radius-sm);
}
</style>
