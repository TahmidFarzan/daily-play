<script setup>
import Layout from "@/pages/layouts/AuthLayout.vue";
import MediaRenderer from "@/components/common/media/MediaRenderer.vue";
import RecentActivities from "@/components/back-office/activity-log/RecentModelActivityLogs.vue";

import { ref, onMounted, nextTick, inject } from "vue";
import { Head, router as inertiaJsRouter } from "@inertiajs/vue3";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import { faTrash, faSpinner } from "@fortawesome/free-solid-svg-icons";

import { formatDateTime } from "@/composables/useDateTime";
import { titleFormat } from "@/composables/useStringFormat";
import { extractModelName } from "@/composables/useUtil";

FontAwesomeLibrary.add(faTrash, faSpinner);

defineOptions({ layout: Layout });

const authUser = inject("authUser");

const showDeleteModal = ref(false);
const deleteProcessing = ref(false);

const { media } = defineProps({
    media: {
        type: Object,
        default: null,
    },
});

const handleDelete = () => {
    if (deleteProcessing.value) return;

    deleteProcessing.value = true;

    inertiaJsRouter.delete(
        route("back-office.medias.delete", { slug: media?.slug }),
        {
            onFinish: () => (deleteProcessing.value = false),
        },
    );
};

onMounted(async () => {
    await nextTick();

    window.dispatchEvent(
        new CustomEvent("set-breadcrumb", {
            detail: [
                { text: "Media", href: route("back-office.medias.index") },
                { text: `${media?.name} Details`, active: true },
            ],
        }),
    );
});
</script>

<template>
    <Head :title="`${media?.name} Details`" />

    <div class="w-full space-y-6">
        <div
            class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6"
        >
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">
                Basic Information
            </h3>

            <div class="grid md:grid-cols-2 gap-4">
                <div
                    class="border border-gray-200 rounded-xl p-4 space-y-2 text-sm"
                >
                    <div>
                        <span class="font-medium text-gray-600">Name:</span>
                        {{ media?.name || "N/A" }}
                    </div>

                    <div>
                        <span class="font-medium text-gray-600"
                            >Collection Name:</span
                        >
                        {{ media?.collection_name || "N/A" }}
                    </div>

                    <div>
                        <span class="font-medium text-gray-600"
                            >Model Type:</span
                        >
                        {{ extractModelName(media?.model_type) || "N/A" }}
                    </div>
                </div>

                <div
                    class="border border-gray-200 rounded-xl p-4 space-y-2 text-sm"
                >
                    <div>
                        <span class="font-medium text-gray-600"
                            >MIME Type:</span
                        >
                        {{ media?.mime_type || "N/A" }}
                    </div>

                    <div>
                        <span class="font-medium text-gray-600">Disk:</span>
                        {{ media?.disk || "N/A" }}
                    </div>

                    <div>
                        <span class="font-medium text-gray-600">Size:</span>
                        {{ media?.size || "N/A" }}
                    </div>
                </div>

                <div
                    class="border border-gray-200 rounded-xl p-4 space-y-2 text-sm"
                >
                    <div class="text-sm font-semibold text-gray-700 mb-2">
                        Custom Properties:
                    </div>

                    <div
                        v-if="
                            media?.custom_properties &&
                            Object.keys(media.custom_properties).length
                        "
                        class="space-y-2"
                    >
                        <div
                            v-for="(value, key) in media.custom_properties"
                            :key="key"
                            class="flex justify-between items-start border-b border-gray-100 pb-1"
                        >
                            <span class="font-medium text-gray-600">
                                {{ titleFormat(key) }}
                            </span>

                            <span class="text-gray-800 text-right">
                                {{ value ? titleFormat(value) : "N/A" }}
                            </span>
                        </div>
                    </div>

                    <p v-else class="text-sm text-gray-500">
                        No custom properties
                    </p>
                </div>

                <div
                    class="border border-gray-200 rounded-xl p-4 space-y-2 text-sm"
                >
                    <div class="text-sm font-semibold text-gray-700 mb-2">
                        Generated Conversions:
                    </div>

                    <div
                        v-if="
                            media?.generated_conversions &&
                            Object.keys(media.generated_conversions).length
                        "
                        class="space-y-2"
                    >
                        <div
                            v-for="(value, key) in media.generated_conversions"
                            :key="key"
                            class="flex justify-between items-center border-b border-gray-100 pb-1"
                        >
                            <span class="font-medium text-gray-600">
                                {{ titleFormat(key) }}
                            </span>

                            <span
                                :class="
                                    value
                                        ? 'text-green-600 font-medium'
                                        : 'text-red-500 font-medium'
                                "
                            >
                                {{ value ? "Yes" : "No" }}
                            </span>
                        </div>
                    </div>

                    <p v-else class="text-sm text-gray-500">
                        No generated conversions
                    </p>
                </div>
            </div>

            <div class="grid md:grid-cols-1 gap-4 mt-4">
                <div
                    class="border border-gray-200 rounded-xl p-4 space-y-2 text-sm"
                >
                    <div>
                        <span class="font-medium text-gray-600"
                            >Original URL:</span
                        >
                        {{ media?.original_url || "N/A" }}
                    </div>

                    <div>
                        <span class="font-medium text-gray-600"
                            >Preview URL:</span
                        >
                        {{ media?.preview_url || "N/A" }}
                    </div>

                    <div>
                        <span class="font-medium text-gray-600"
                            >Order Column:</span
                        >
                        {{ media?.order_column || "N/A" }}
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-1 gap-4 mt-4">
                <div class="border border-gray-200 rounded-xl p-4 text-sm">
                    <span class="font-medium text-gray-600">Media:</span>
                    <MediaRenderer :media="media" />
                </div>
            </div>
        </div>

        <div
            class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6"
        >
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">
                System Information
            </h3>

            <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-xl p-4 space-y-2">
                    <div>
                        <span class="font-medium text-gray-600"
                            >Created At:</span
                        >
                        {{
                            media?.created_at
                                ? formatDateTime(media.created_at)
                                : "N/A"
                        }}
                    </div>

                    <div>
                        <span class="font-medium text-gray-600"
                            >Created By:</span
                        >
                        {{ media?.created_by?.name || "N/A" }}
                    </div>
                </div>
            </div>
        </div>

        <div
            class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6"
        >
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">
                Activity Logs
            </h3>

            <RecentActivities :model-slug="'media'" :model="media" />
        </div>

        <div
            class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex justify-end gap-2"
        >
            <button
                @click="showDeleteModal = true"
                class="px-4 py-2 border border-red-500 text-red-600 rounded hover:bg-red-50 flex items-center gap-2"
            >
                <FontAwesomeIcon icon="trash" />
                Delete
            </button>
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showDeleteModal"
                    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
                >
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div class="bg-white p-6 rounded-xl shadow-lg w-96">
                            <div class="font-semibold mb-2">
                                Delete Confirmation
                            </div>

                            <p class="mb-2">
                                {{ media?.name }}
                            </p>

                            <p class="text-sm text-gray-600 mb-4">
                                This action cannot be undone.
                            </p>

                            <div class="flex justify-end gap-2">
                                <button
                                    @click="showDeleteModal = false"
                                    class="px-3 py-1 bg-gray-200 rounded"
                                >
                                    Cancel
                                </button>

                                <button
                                    @click="handleDelete"
                                    :disabled="deleteProcessing"
                                    class="px-3 py-1 bg-red-500 text-white rounded flex items-center gap-1"
                                >
                                    <FontAwesomeIcon
                                        v-if="deleteProcessing"
                                        icon="spinner"
                                        spin
                                    />
                                    {{
                                        deleteProcessing
                                            ? "Deleting..."
                                            : "Delete"
                                    }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
