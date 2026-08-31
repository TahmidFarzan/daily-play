<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from "@/pages/layouts/PublicLayout.vue"

defineOptions({
    layout: Layout
})
</script>

<template>
    <Head title="Home" />

    <div class="grid grid-cols-1 gap-4">
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h1 class="text-xl font-semibold text-gray-900">Home</h1>
            <p class="mt-2 text-gray-600">Welcome to your application.</p>
        </div>
    </div>
</template>

<style scoped>

</style>
