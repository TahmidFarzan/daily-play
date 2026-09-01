<script setup>
import Layout from "@/pages/layouts/AuthLayout.vue";
import RecentActivities from "@/components/back-office/activity-log/RecentModelActivityLogs.vue";

import { computed, onMounted, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";

import { formatDateTime } from "@/composables/useDateTime";

defineOptions({ layout: Layout });

const { game } = defineProps({
    game: Object,
});

const pageTitle = computed(() => `Game - ${game?.name || "Details"}`);

onMounted(async () => {
    await nextTick();

    window.dispatchEvent(
        new CustomEvent("set-breadcrumb", {
            detail: [
                {
                    text: "Game",
                    href: route("back-office.games.index"),
                },
                {
                    text: game?.name || "Game Details",
                    active: true,
                },
            ],
        }),
    );
});
</script>

<template>
    <Head :title="pageTitle" />

    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Game Details</h2>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4" >
            <h3 class="text-base font-semibold border-b pb-2">
                Basic Information
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Name</span>
                        <span class="font-medium">{{ game?.name || "N/A" }}</span>
                    </div>

                    <div>
                        <span class="text-gray-500">Brief</span>
                        <p>{{ game?.brief || "N/A" }}</p>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <img :src="game?.logo?.preview_url || '/uploads/images/logo/game.png'"
                        :alt="game?.logo?.name"
                        class="w-40 h-40 object-cover rounded-xl border" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-1 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="text-gray-500 mb-1">How to play</div>
                    <div class="text-gray-700">
                        {{ game?.how_to_play || "N/A" }}
                    </div>
                </div>
            </div>
        </div>

        <div
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4"
        >
            <h3 class="text-base font-semibold border-b pb-2">
                System Information
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created By</span>
                        <span class="font-medium">
                            {{ game?.created_by?.name || "N/A" }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Created At</span>
                        <span class="font-medium">
                            {{
                                game?.created_at
                                    ? formatDateTime(game.created_at)
                                    : "N/A"
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4"
        >
            <h3 class="text-base font-semibold border-b pb-2">Activity Logs</h3>

            <RecentActivities :model-slug="'game'" :model="game" />
        </div>
    </div>
</template>
