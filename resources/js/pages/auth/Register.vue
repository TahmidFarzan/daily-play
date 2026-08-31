<script setup>
import layout from '@/pages/layouts/PublicLayout.vue'

import { Head, useForm, usePage } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faEye, faEyeSlash, faSpinner, faUserPlus } from '@fortawesome/free-solid-svg-icons'

import {
    showPassword,
    showConfirmPassword,
    togglePasswordVisibility,
    toggleConfirmPasswordVisibility,
} from '@/composables/usePassword'

FontAwesomeLibrary.add(faEye, faEyeSlash, faSpinner, faUserPlus)

defineOptions({ layout })

const page = usePage()
const appLogo = import.meta.env.VITE_APP_LOGO
const appName = import.meta.env.VITE_APP_NAME

const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

function validateForm() {
    registerForm.clearErrors()

    let valid = true

    if (!registerForm.name || registerForm.name.trim() === '') {
        registerForm.setError('name', 'Name is required.')
        valid = false
    } else if (registerForm.name.length > 200) {
        registerForm.setError('name', 'Name must not exceed 200 characters.')
        valid = false
    }

    if (!registerForm.email || registerForm.email.trim() === '') {
        registerForm.setError('email', 'Email is required.')
        valid = false
    } else if (registerForm.email.length > 200) {
        registerForm.setError('email', 'Email must not exceed 200 characters.')
        valid = false
    }

    if (!registerForm.password || registerForm.password.trim() === '') {
        registerForm.setError('password', 'Password is required.')
        valid = false
    }

    if (!registerForm.password_confirmation || registerForm.password_confirmation.trim() === '') {
        registerForm.setError('password_confirmation', 'Password confirmation is required.')
        valid = false
    } else if (registerForm.password !== registerForm.password_confirmation) {
        registerForm.setError('password_confirmation', 'Password confirmation does not match.')
        valid = false
    }

    return valid
}

function handleRegister() {
    if (registerForm.processing) return
    if (!validateForm()) return

    registerForm.post(route('register.submit'), {
        preserveScroll: true,

        onSuccess: () => {
            registerForm.reset()
            registerForm.clearErrors()
        },

        onError: (errors) => {
            registerForm.clearErrors()
            registerForm.setError(errors)
        }
    })
}
</script>

<template>
    <Head title="Register" />

    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-card-header">
                <img
                    v-if="appLogo"
                    :src="appLogo"
                    :alt="appName"
                    class="auth-logo"
                />

                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join Daily Play and start competing</p>
            </div>

            <form @submit.prevent="handleRegister" class="auth-form">
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>

                    <input
                        id="name"
                        v-model="registerForm.name"
                        type="text"
                        placeholder="Your full name"
                        autofocus
                        class="form-input"
                        :class="{ 'form-input-error': registerForm.errors.name }"
                    />

                    <p v-if="registerForm.errors.name" class="form-error">
                        {{ registerForm.errors.name }}
                    </p>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>

                    <input
                        id="email"
                        v-model="registerForm.email"
                        type="email"
                        placeholder="you@example.com"
                        class="form-input"
                        :class="{ 'form-input-error': registerForm.errors.email }"
                    />

                    <p v-if="registerForm.errors.email" class="form-error">
                        {{ registerForm.errors.email }}
                    </p>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>

                    <div class="form-input-wrapper">
                        <input
                            id="password"
                            v-model="registerForm.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Create a password"
                            class="form-input form-input-password"
                            :class="{ 'form-input-error': registerForm.errors.password }"
                        />

                        <button
                            type="button"
                            @click="togglePasswordVisibility"
                            class="form-input-toggle"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        >
                            <FontAwesomeIcon :icon="showPassword ? 'eye-slash' : 'eye'" />
                        </button>
                    </div>

                    <p v-if="registerForm.errors.password" class="form-error">
                        {{ registerForm.errors.password }}
                    </p>
                </div>

                <div class="form-group">
                    <label for="passwordConfirmation" class="form-label">Confirm Password</label>

                    <div class="form-input-wrapper">
                        <input
                            id="passwordConfirmation"
                            v-model="registerForm.password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            placeholder="Confirm your password"
                            class="form-input form-input-password"
                            :class="{ 'form-input-error': registerForm.errors.password_confirmation }"
                        />

                        <button
                            type="button"
                            @click="toggleConfirmPasswordVisibility"
                            class="form-input-toggle"
                            :aria-label="showConfirmPassword ? 'Hide confirm password' : 'Show confirm password'"
                        >
                            <FontAwesomeIcon :icon="showConfirmPassword ? 'eye-slash' : 'eye'" />
                        </button>
                    </div>

                    <p v-if="registerForm.errors.password_confirmation" class="form-error">
                        {{ registerForm.errors.password_confirmation }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="registerForm.processing"
                    class="form-submit"
                >
                    <FontAwesomeIcon v-if="registerForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="user-plus" />
                    <span>{{ registerForm.processing ? 'Creating account...' : 'Create Account' }}</span>
                </button>
            </form>

            <div class="auth-footer-text">
                Already have an account?
                <a :href="route('login')" class="auth-link">
                    Sign in
                </a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.auth-page {
    min-height: calc(100vh - 8rem);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 1rem;
    background: linear-gradient(160deg, #F8FAFC 0%, #FFFFFF 40%, var(--daily-play-accent-soft) 100%);
}

.auth-card {
    width: 100%;
    max-width: 28rem;
    background: var(--daily-play-surface);
    border: 1px solid var(--daily-play-border);
    border-radius: var(--daily-play-radius);
    box-shadow: var(--daily-play-shadow);
    padding: 2rem;
    animation: dp-fade-in 0.3s ease-out;
}

@keyframes dp-fade-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.auth-card-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-logo {
    height: 2.5rem;
    width: auto;
    margin: 0 auto 1rem;
    object-fit: contain;
}

.auth-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--daily-play-text);
    letter-spacing: -0.02em;
    line-height: 1.3;
}

.auth-subtitle {
    font-size: 0.875rem;
    color: var(--daily-play-text-muted);
    margin-top: 0.375rem;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--daily-play-text);
}

.form-input {
    width: 100%;
    height: 2.75rem;
    padding: 0 0.875rem;
    font-size: 0.875rem;
    color: var(--daily-play-text);
    background: var(--daily-play-surface);
    border: 1px solid var(--daily-play-border);
    border-radius: var(--daily-play-radius-sm);
    outline: none;
    transition: border-color var(--daily-play-transition), box-shadow var(--daily-play-transition);
}

.form-input::placeholder {
    color: var(--daily-play-text-muted);
    opacity: 0.6;
}

.form-input:focus {
    border-color: var(--daily-play-border-focus);
    box-shadow: var(--daily-play-focus-ring);
}

.form-input-error {
    border-color: var(--daily-play-danger);
}

.form-input-error:focus {
    border-color: var(--daily-play-danger);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.form-input-wrapper {
    position: relative;
}

.form-input-password {
    padding-right: 2.75rem;
}

.form-input-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--daily-play-text-muted);
    transition: color var(--daily-play-transition);
    padding: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-input-toggle:hover {
    color: var(--daily-play-text);
}

.form-error {
    font-size: 0.8125rem;
    color: var(--daily-play-danger);
    line-height: 1.4;
}

.form-submit {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 2.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    background: var(--daily-play-accent);
    border: none;
    border-radius: var(--daily-play-radius-sm);
    cursor: pointer;
    transition: background var(--daily-play-transition), box-shadow var(--daily-play-transition);
    margin-top: 0.25rem;
}

.form-submit:hover:not(:disabled) {
    background: var(--daily-play-accent-hover);
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
}

.form-submit:active:not(:disabled) {
    background: var(--daily-play-accent-active);
}

.form-submit:focus-visible {
    outline: 0;
    box-shadow: var(--daily-play-focus-ring);
}

.form-submit:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.auth-footer-text {
    text-align: center;
    font-size: 0.8125rem;
    color: var(--daily-play-text-muted);
    margin-top: 1.5rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--daily-play-border);
}

.auth-link {
    font-weight: 500;
    color: var(--daily-play-accent);
    transition: color var(--daily-play-transition);
}

.auth-link:hover {
    color: var(--daily-play-accent-hover);
}

@media (max-width: 480px) {
    .auth-card {
        padding: 1.5rem;
    }

    .auth-title {
        font-size: 1.25rem;
    }
}
</style>
