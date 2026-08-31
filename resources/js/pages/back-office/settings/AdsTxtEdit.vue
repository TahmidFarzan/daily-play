<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

import { computed, nextTick, onMounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faArrowLeft,
    faFileCode,
    faFloppyDisk,
    faRotateLeft,
    faWandMagicSparkles,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(
    faArrowLeft,
    faFileCode,
    faFloppyDisk,
    faRotateLeft,
    faWandMagicSparkles,
)

defineOptions({ layout: Layout })

const { adsTxt } = defineProps({
    adsTxt: {
        type: String,
        default: '',
    },
})

const defaultAdsTxt = computed(() => {
    return [
        'google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0',
    ].join('\n')
})

const form = useForm({
    ads_txt: adsTxt || '',
})

const submit = () => {
    form.post(route('back-office.settings.ads-txt.save'), {
        preserveScroll: true,
    })
}

const useExample = () => {
    form.ads_txt = defaultAdsTxt.value
}

const resetForm = () => {
    form.ads_txt = adsTxt || ''
    form.clearErrors()
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: 'Settings',
                    href: route('back-office.settings.index'),
                },
                {
                    text: 'Edit ads.txt',
                    active: true,
                },
            ],
        }),
    )
})
</script>

<template>
    <Head title="Edit ads.txt" />

    <div class="w-full space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-950">
                    Edit ads.txt
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Manage your ads.txt file content. This file is used to declare authorized digital sellers.
                </p>
            </div>

            <Link :href="route('back-office.settings.index')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                <FontAwesomeIcon :icon="faArrowLeft" class="text-xs" />

                <span>
                    Back
                </span>
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <div class="space-y-4 lg:col-span-8">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 p-4">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                    <FontAwesomeIcon :icon="faFileCode" class="text-sm" />
                                </span>

                                <div>
                                    <h3 class="text-sm font-semibold text-gray-950">
                                        ads.txt Content
                                    </h3>

                                    <p class="text-xs text-gray-500">
                                        Edit the content of your ads.txt file.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            <textarea v-model="form.ads_txt" rows="18"
                                class="min-h-[420px] w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 font-mono text-sm text-gray-800 outline-none transition focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-100"
                                placeholder="Paste your ads.txt content here..." />

                            <p v-if="form.errors.ads_txt" class="mt-2 text-sm text-red-600">
                                {{ form.errors.ads_txt }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing">
                            <FontAwesomeIcon :icon="faFloppyDisk" class="text-xs" />

                            <span>
                                {{
                                    form.processing
                                        ? 'Saving...'
                                        : 'Save'
                                }}
                            </span>
                        </button>

                        <button type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            @click="useExample">
                            <FontAwesomeIcon :icon="faWandMagicSparkles" class="text-xs" />

                            <span>
                                Use Example
                            </span>
                        </button>

                        <button type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            @click="resetForm">
                            <FontAwesomeIcon :icon="faRotateLeft" class="text-xs" />

                            <span>
                                Reset
                            </span>
                        </button>
                    </div>
                </div>

                <div class="space-y-4 lg:col-span-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-950">
                            What is ads.txt?
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-500">
                            ads.txt is an IAB initiative that helps publishers declare who is authorized to sell their digital inventory. This file should be placed in the root of your domain.
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-950">
                            Example Format
                        </h3>

                        <pre
                            class="mt-3 overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs leading-6 text-white">{{ defaultAdsTxt }}</pre>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
