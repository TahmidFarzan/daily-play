<script setup>
import layout from '@/pages/layouts/PublicLayout.vue'

import { ref } from 'vue'
import { Head, router as inertiaJsRoute, usePage } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library } from '@fortawesome/fontawesome-svg-core'
import { faSpinner, faEnvelopeCircleCheck, faPaperPlane } from '@fortawesome/free-solid-svg-icons'

library.add(faSpinner, faEnvelopeCircleCheck, faPaperPlane)

defineOptions({ layout })

const page = usePage()
const appLogo = import.meta.env.VITE_APP_LOGO
const appName = import.meta.env.VITE_APP_NAME

const resending = ref(false)

function handleResendVerification() {
    if (resending.value) return

    resending.value = true

    inertiaJsRoute.post(route('email-verify.resend'), {}, {
        onFinish: () => {
            resending.value = false
        }
    })
}
</script>

<template>
    <Head title="Email Verification" />

    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-card-header">
                <div class="verify-icon-wrapper">
                    <FontAwesomeIcon icon="envelope-circle-check" class="verify-icon" />
                </div>

                <h1 class="auth-title">Verify Your Email</h1>
                <p class="auth-subtitle">
                    We've sent a verification link to your email address.
                    Please check your inbox and click the link to verify your account.
                </p>
            </div>

            <form @submit.prevent="handleResendVerification">
                <button
                    type="submit"
                    :disabled="resending"
                    class="form-submit"
                >
                    <FontAwesomeIcon v-if="resending" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="paper-plane" />
                    <span>{{ resending ? 'Sending...' : 'Resend Verification Email' }}</span>
                </button>
            </form>

            <div class="auth-footer-text">
                Already verified?
                <a :href="route('login')" class="auth-link">
                    Back to Sign In
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
    margin-bottom: 1.75rem;
}

.verify-icon-wrapper {
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    background: var(--daily-play-accent-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
}

.verify-icon {
    font-size: 1.5rem;
    color: var(--daily-play-accent);
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
    margin-top: 0.5rem;
    line-height: 1.6;
    max-width: 22rem;
    margin-left: auto;
    margin-right: auto;
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
