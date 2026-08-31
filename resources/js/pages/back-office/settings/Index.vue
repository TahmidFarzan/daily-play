<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

import { computed, nextTick, onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faBroom,
    faCalendarDays,
    faCheck,
    faClock,
    faCopy,
    faFileCode,
    faGlobe,
    faPenToSquare,
    faPlay,
    faRotateRight,
    faShieldHalved,
    faStop,
    faTrashCan,
    faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(
    faBroom,
    faCalendarDays,
    faCheck,
    faClock,
    faCopy,
    faFileCode,
    faGlobe,
    faPenToSquare,
    faPlay,
    faRotateRight,
    faShieldHalved,
    faStop,
    faTrashCan,
    faTriangleExclamation,
)

defineOptions({ layout: Layout })

const activeTab = ref(null)
const copiedKey = ref(null)

const tabs = computed(() => [
    {
        key: 'queue',
        label: 'Queue',
        icon: faClock,
    },
    {
        key: 'schedule',
        label: 'Schedule',
        icon: faCalendarDays,
    },
    {
        key: 'robots_txt',
        label: 'Robots.txt',
        icon: faGlobe,
    },
    {
        key: 'ads_txt',
        label: 'Ads.txt',
        icon: faFileCode,
    },
])

const activeTabKey = computed(() => {
    return activeTab.value ?? tabs.value[0]?.key ?? null
})

const queueActions = computed(() => [
    {
        key: 'queue_start',
        title: 'Start Queue',
        url: route('back-office.settings.queue.start'),
        icon: faPlay,
    },
    {
        key: 'queue_restart',
        title: 'Restart Queue',
        url: route('back-office.settings.queue.restart'),
        icon: faRotateRight,
    },
    {
        key: 'queue_clear',
        title: 'Clear Queue',
        url: route('back-office.settings.queue.clear'),
        icon: faBroom,
    },
    {
        key: 'queue_flush',
        title: 'Flush Queue',
        url: route('back-office.settings.queue.flush'),
        icon: faTrashCan,
    },
    {
        key: 'queue_monitor_stale',
        title: 'Monitor Stale',
        url: route('back-office.settings.queue.monitor.stale'),
        icon: faTriangleExclamation,
    },
    {
        key: 'queue_monitor_purge',
        title: 'Monitor Purge',
        url: route('back-office.settings.queue.monitor.purge'),
        icon: faShieldHalved,
    },
])

const scheduleActions = computed(() => [
    {
        key: 'schedule_start',
        title: 'Start Schedule',
        url: route('back-office.settings.schedule.start'),
        icon: faPlay,
    },
    {
        key: 'schedule_stop',
        title: 'Stop Schedule',
        url: route('back-office.settings.schedule.stop'),
        icon: faStop,
    },
])

const robotsTxtAction = computed(() => ({
    key: 'robots_txt_edit',
    title: 'Edit Robots.txt',
    url: route('back-office.settings.robots-txt.edit'),
    icon: faPenToSquare,
}))

const adsTxtAction = computed(() => ({
    key: 'ads_txt_edit',
    title: 'Edit Ads.txt',
    url: route('back-office.settings.ads-txt.edit'),
    icon: faPenToSquare,
}))

const copyToClipboard = async (item) => {
    if (!item?.copyable || !item?.text) return

    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(item.text)
        } else {
            const textarea = document.createElement('textarea')

            textarea.value = item.text
            textarea.setAttribute('readonly', '')
            textarea.style.position = 'absolute'
            textarea.style.left = '-9999px'

            document.body.appendChild(textarea)
            textarea.select()
            document.execCommand('copy')
            document.body.removeChild(textarea)
        }

        copiedKey.value = item.key

        setTimeout(() => {
            if (copiedKey.value === item.key) {
                copiedKey.value = null
            }
        }, 1500)
    } catch (error) {
        console.error('Copy failed:', error)
    }
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: 'Settings',
                    active: true,
                },
            ],
        }),
    )
})
</script>

<template>

    <Head title="Settings" />

    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-950">
                Settings
            </h2>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-4">
                <nav class="-mb-px flex flex-wrap gap-1">
                    <button v-for="tab in tabs" :key="tab.key" type="button"
                        class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition"
                        :class="activeTabKey === tab.key
                            ? 'border-red-600 text-red-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            " @click="activeTab = tab.key">
                        <FontAwesomeIcon :icon="tab.icon" class="text-xs" />
                        <span>{{ tab.label }}</span>
                    </button>
                </nav>
            </div>

            <div class="p-4 sm:p-6">
                <div v-if="activeTabKey === 'queue'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            Queue Actions
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Manage the queue worker processes.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <a v-for="item in queueActions" :key="item.key" :href="item.url"
                            class="group flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 transition hover:border-red-200 hover:bg-red-50">
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600 transition group-hover:bg-white">
                                    <FontAwesomeIcon :icon="item.icon" class="text-sm" />
                                </span>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-950">
                                        {{ item.title }}
                                    </p>

                                    <p class="mt-1 truncate font-mono text-xs text-gray-500">
                                        {{ item.url }}
                                    </p>
                                </div>
                            </div>

                            <span class="text-xs font-semibold text-red-600">
                                Run
                            </span>
                        </a>
                    </div>
                </div>

                <div v-else-if="activeTabKey === 'schedule'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            Schedule Actions
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Manage the scheduled task runner.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <a v-for="item in scheduleActions" :key="item.key" :href="item.url"
                            class="group flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 transition hover:border-red-200 hover:bg-red-50">
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600 transition group-hover:bg-white">
                                    <FontAwesomeIcon :icon="item.icon" class="text-sm" />
                                </span>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-950">
                                        {{ item.title }}
                                    </p>

                                    <p class="mt-1 truncate font-mono text-xs text-gray-500">
                                        {{ item.url }}
                                    </p>
                                </div>
                            </div>

                            <span class="text-xs font-semibold text-red-600">
                                Run
                            </span>
                        </a>
                    </div>
                </div>

                <div v-else-if="activeTabKey === 'robots_txt'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            Robots.txt
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Edit the robots.txt file to control search engine crawling.
                        </p>
                    </div>

                    <a :href="robotsTxtAction.url"
                        class="group inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                        <FontAwesomeIcon :icon="robotsTxtAction.icon" class="text-xs" />

                        <span>
                            Edit
                        </span>
                    </a>
                </div>

                <div v-else-if="activeTabKey === 'ads_txt'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            Ads.txt
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Edit the ads.txt file to manage ad inventory authorization.
                        </p>
                    </div>

                    <a :href="adsTxtAction.url"
                        class="group inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                        <FontAwesomeIcon :icon="adsTxtAction.icon" class="text-xs" />

                        <span>
                            Edit
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
