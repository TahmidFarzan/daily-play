<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

import { onMounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: Layout })

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(new CustomEvent('set-breadcrumb', {
        detail: [{ text: 'Dashboard', active: true }]
    }))
})
</script>

<template>
    <Head title="Dashboard" />

    <div class="grid grid-cols-1 gap-4">
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            Dashboard
        </div>
    </div>
</template>
