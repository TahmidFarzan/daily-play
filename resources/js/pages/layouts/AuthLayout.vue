<script setup>
import OffCanvasMenu from '@/components/common/layout/auth-layout/OffCanvasMenu.vue'
import Breadcrumbs from '@/components/common/layout/auth-layout/Breadcrumbs.vue'
import AuthTopbarDropdownMenu from '@/components/common/layout/auth-layout/AuthTopbarDropdownMenu.vue'
import FlashMessageToaster from '@/components/common/layout/FlashMessageToaster.vue'

import { usePage } from '@inertiajs/vue3'
import { computed, provide } from 'vue'

const appName = import.meta.env.VITE_APP_NAME
const appLogo = import.meta.env.VITE_APP_LOGO

const page = usePage()

const authUser = computed(() => page.props.auth?.user ?? null)
const flashMessage = computed(() => page.props.flashMessage ?? null)

provide('authUser', authUser)
</script>

<template>
    <div class="auth-layout flex flex-col min-h-screen">
        <header class="fixed top-0 left-0 w-full bg-white shadow-sm z-50">
            <div class="px-4 py-2 flex items-center justify-between">
                <a :href="route('home')" class="flex items-center gap-2 min-w-0">
                    <img v-if="appLogo" :src="appLogo" :alt="appName" class="h-8 flex-shrink-0">

                    <span class="font-semibold truncate">
                        {{ appName }}
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    <!-- OFFCANVAS (trigger) -->
                    <OffCanvasMenu mode="trigger" :auth-user="authUser" />

                    <!-- USER MENU -->
                    <AuthTopbarDropdownMenu :auth-user="authUser" />
                </div>
            </div>
        </header>

        <main class="main flex-1 flex pt-16">
            <OffCanvasMenu mode="sidebar" :auth-user="authUser" />

            <div class="flex-1 p-4 min-w-0">
                <Breadcrumbs />

                <div v-if="authUser && !authUser.email_verified_at"
                    class="mb-4 p-3 bg-yellow-100 border border-yellow-300 text-yellow-800 rounded">
                    Please verify your email address.
                </div>

                <slot />
            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 py-3 text-gray-500 text-sm">
            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-2">
                <span class="text-center md:text-left w-full md:w-auto">
                    &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
                </span>

                <span class="text-center md:text-right w-full md:w-auto">
                    Developed by
                    <a href="https://www.linkedin.com/in/sk-md-tahmid-farzan/" target="_blank" rel="noopener noreferrer"
                        class="text-blue-600 hover:underline font-medium">
                        Seikh Md Tahmid Farzan
                    </a>
                </span>
            </div>
        </footer>

        <FlashMessageToaster :flash-message="flashMessage" />
    </div>
</template>

<style scoped>
.auth-layout {
    background: var(--news-soft);
    color: var(--news-ink);
    font-family: var(--font-en);
}

.auth-layout :deep(a:focus-visible),
.auth-layout :deep(button:focus-visible),
.auth-layout :deep(input:focus-visible),
.auth-layout :deep(select:focus-visible),
.auth-layout :deep(textarea:focus-visible) {
    outline: 0;
    box-shadow: var(--news-focus-ring);
}

.auth-layout header {
    border-bottom: var(--news-border-default);
    box-shadow: var(--news-shadow-soft);
}

.auth-layout main > div:last-child {
    background: var(--news-soft);
}

.auth-layout footer {
    color: var(--news-muted);
}
</style>
