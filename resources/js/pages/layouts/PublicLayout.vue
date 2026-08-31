<script setup>
import AuthTopbarDropdownMenu from '@/components/common/layout/public-layout/AuthTopbarDropdownMenu.vue'
import FlashMessageToaster from '@/components/common/layout/FlashMessageToaster.vue'

import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import {
    faArrowRightToBracket,
    faGamepad,
} from '@fortawesome/free-solid-svg-icons'

library.add(
    faArrowRightToBracket,
    faGamepad
)

const page = usePage()

const appName = import.meta.env.VITE_APP_NAME
const appLogo = import.meta.env.VITE_APP_LOGO
const appEnv = import.meta.env.VITE_APP_ENV

const showLoginUrl = appEnv !== 'production'

const authUser = computed(() => {
    return page.props.auth?.user ?? null
})

const flashMessage = computed(() => {
    return page.props.flashMessage
})

const year = new Date().getFullYear()

const homeUrl = route('home')

</script>

<template>
    <div class="guest-layout flex min-h-screen flex-col">
        <header class="dp-header">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
                <a :href="homeUrl" class="dp-logo-link group flex items-center gap-3">
                    <img
                        v-if="appLogo"
                        :src="appLogo"
                        :alt="appName"
                        class="dp-logo h-8 w-auto sm:h-9"
                    />
                    <span class="dp-brand-text hidden text-base font-semibold sm:inline">{{ appName }}</span>
                </a>

                <div class="flex items-center gap-2">
                    <a
                        v-if="!authUser && showLoginUrl"
                        :href="route('login')"
                        class="dp-nav-btn"
                    >
                        <FontAwesomeIcon icon="arrow-right-to-bracket" class="text-sm" />
                        <span class="hidden sm:inline">Login</span>
                    </a>

                    <div v-if="authUser">
                        <AuthTopbarDropdownMenu :auth-user="authUser" />
                    </div>
                </div>
            </div>
        </header>

        <main class="dp-main mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6">
            <slot />
        </main>

        <footer class="dp-footer">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 text-sm sm:flex-row sm:px-6">
                <span class="dp-footer-text">
                    &copy; {{ year }} {{ appName }}. All rights reserved.
                </span>

                <span class="dp-footer-text">
                    Developed by
                    <a
                        href="https://www.linkedin.com/in/sk-md-tahmid-farzan/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="dp-footer-link"
                    >
                        Sk. Md. Tahmid Farzan
                    </a>
                </span>
            </div>
        </footer>

        <FlashMessageToaster :flash-message="flashMessage" />
    </div>
</template>

<style scoped>
.guest-layout {
    font-family: var(--daily-play-font);
    background: var(--daily-play-background);
    color: var(--daily-play-text);
    text-rendering: optimizeLegibility;
    -webkit-font-smoothing: antialiased;
}

.guest-layout ::selection {
    background: var(--daily-play-accent-soft);
    color: var(--daily-play-accent-active);
}

.guest-layout :deep(a:focus-visible),
.guest-layout :deep(button:focus-visible) {
    outline: 0;
    box-shadow: var(--daily-play-focus-ring);
}

.dp-header {
    position: sticky;
    top: 0;
    z-index: 50;
    background: var(--daily-play-header-bg);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 4px 20px rgb(15 23 42 / 20%);
}

.dp-logo-link {
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    padding-right: 0.75rem;
    transition: opacity var(--daily-play-transition);
}

.dp-logo-link:hover {
    opacity: 0.9;
}

.dp-brand-text {
    color: var(--daily-play-text-inverse);
    letter-spacing: -0.01em;
}

.dp-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4375rem 0.875rem;
    border-radius: var(--daily-play-radius-sm);
    font-size: 0.8125rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.06);
    transition: all var(--daily-play-transition);
    white-space: nowrap;
}

.dp-nav-btn:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.2);
}

.dp-nav-btn:focus-visible {
    outline: 0;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.6);
}

.dp-nav-btn-accent {
    background: var(--daily-play-accent);
    border-color: transparent;
    color: #fff;
}

.dp-nav-btn-accent:hover {
    background: var(--daily-play-accent-hover);
    border-color: transparent;
}

.dp-main {
    flex: 1;
}

.dp-footer {
    border-top: 1px solid var(--daily-play-border);
    background: var(--daily-play-surface);
}

.dp-footer-text {
    color: var(--daily-play-text-muted);
}

.dp-footer-link {
    color: var(--daily-play-accent);
    font-weight: 500;
    transition: color var(--daily-play-transition);
}

.dp-footer-link:hover {
    color: var(--daily-play-accent-hover);
}

@media (max-width: 640px) {
    .dp-main {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
}
</style>
