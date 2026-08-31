<script setup>
import AuthTopbarDropdownMenu from '@/components/common/layout/public-layout/AuthTopbarDropdownMenu.vue'
import FlashMessageToaster from '@/components/common/layout/FlashMessageToaster.vue'

import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import {
    faArrowRightToBracket,
    faMagnifyingGlass,
} from '@fortawesome/free-solid-svg-icons'

library.add(
    faArrowRightToBracket,
    faMagnifyingGlass
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
        <div class="public-topbar text-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2 max-[450px]:gap-2">
                <div class="flex items-center space-x-3 max-[450px]:flex-shrink-0 max-[450px]:space-x-2">
                    <a
                        v-if="!authUser && showLoginUrl"
                        :href="route('login')"
                        class="flex items-center gap-1 text-gray-300 hover:text-white max-[450px]:flex-shrink-0"
                    >
                        <FontAwesomeIcon icon="arrow-right-to-bracket" />
                        <span class="max-[450px]:hidden">Login</span>
                    </a>
                </div>

                <div class="relative flex items-center space-x-3 max-[450px]:flex-1 max-[450px]:min-w-0 max-[450px]:justify-end">
                    <div v-if="authUser" class="max-[450px]:flex-shrink-0">
                        <AuthTopbarDropdownMenu :auth-user="authUser" />
                    </div>
                </div>
            </div>
        </div>

        <div class="public-header text-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4">
                <a :href="homeUrl" class="brand-link flex h-10 flex-shrink-0 items-center pr-4 font-semibold leading-none text-white">
                    <img
                        v-if="appLogo"
                        :src="appLogo"
                        :alt="appName"
                        class="h-10 max-w-40 object-contain"
                    />
                    <b v-else class="hidden sm:inline">{{ appName }}</b>
                </a>

                <div class="flex h-10 flex-shrink-0 items-center gap-2 ml-auto">

                </div>
            </div>
        </div>

        <main class="main public-main mx-auto w-full max-w-7xl px-4 py-6">
            <slot />
        </main>

        <footer class="public-footer mt-2 py-4 text-sm">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 md:flex-row md:gap-4">
                <span class="w-full flex-shrink-0 text-center md:w-auto md:text-left">
                    &copy; {{ year }} {{ appName }}. All rights reserved.
                </span>

                <span class="w-full flex-shrink-0 text-center md:w-auto md:text-right">
                    Developed by
                    <a
                        href="https://www.linkedin.com/in/sk-md-tahmid-farzan/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-blue-600 hover:underline"
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
    font-family: var(--font-en, system-ui, -apple-system, sans-serif);
    background: var(--news-body-gradient, #f5f5f5);
    color: var(--news-ink, #1a1a1a);
    text-rendering: optimizeLegibility;
}

.guest-layout ::selection {
    background: var(--news-selection-bg, #2563eb);
    color: var(--news-selection-color, #ffffff);
}

.guest-layout :deep(a:focus-visible),
.guest-layout :deep(button:focus-visible) {
    outline: 0;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.5);
}

.public-topbar {
    background: var(--news-topbar-gradient, linear-gradient(135deg, #1a1a2e 0%, #16213e 100%));
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 0.875rem;
}

.public-header {
    background: var(--news-header-gradient, linear-gradient(135deg, #1a1a2e 0%, #16213e 100%));
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.brand-link {
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.header-action {
    transition: all 0.2s ease;
}

.header-action:hover {
    transform: translateY(-1px);
    background: rgba(255, 255, 255, 0.1);
}

.public-main {
    flex: 1;
}

.public-footer {
    border-top: 1px solid var(--news-border-default, #e5e7eb);
    background: var(--news-surface, #ffffff);
    color: var(--news-muted, #6b7280);
}

@media (max-width: 640px) {
    .public-main {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
}
</style>
