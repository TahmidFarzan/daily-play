<script setup>
import Layout from "@/pages/layouts/AuthLayout.vue";
import ModelPropertyAttributes from "@/components/back-office/activity-log/ModelPropertyAttributes.vue";

import { ref, onMounted, nextTick, inject } from "vue";
import { Head, router as inertiaJsRoute } from "@inertiajs/vue3";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import { faTrash, faSpinner } from "@fortawesome/free-solid-svg-icons";

import { titleFormat } from "@/composables/useStringFormat";
import { formatDateTime } from "@/composables/useDateTime";

import { canDeleteActivityLog } from "@/composables/useUserPermissions";

FontAwesomeLibrary.add(faTrash, faSpinner);

defineOptions({ layout: Layout });

const authUser = inject("authUser");

const { activityLog } = defineProps({
    activityLog: Object,
});

const deleting = ref(false);
const showDeleteModal = ref(false);

const canDelete = (activityLog) =>
    canDeleteActivityLog(authUser?.value, activityLog);

function handleDelete() {
    if (deleting.value) return;

    deleting.value = true;

    inertiaJsRoute.delete(
        route("back-office.activity-logs.delete", { slug: activityLog?.slug }),
        {
            onFinish: () => {
                deleting.value = false;
                showDeleteModal.value = false;
            },
        },
    );
}

onMounted(async () => {
    await nextTick();

    window.dispatchEvent(
        new CustomEvent("set-breadcrumb", {
            detail: [
                {
                    text: "Activity Logs",
                    href: route("back-office.activity-logs.index"),
                },
                {
                    text: "Activity Log Details",
                    active: true,
                },
            ],
        }),
    );
});
</script>

<template>
    <Head title="Activity Log Details" />

    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Activity Log Details</h2>

            <button
                v-if="canDelete(activityLog)"
                @click="showDeleteModal = true"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition"
            >
                <FontAwesomeIcon icon="trash" />
                Delete
            </button>
        </div>

        <div
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4"
        >
            <h3 class="text-base font-semibold border-b pb-2">
                Basic Information
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500"> Log Name </span>

                        <span class="font-medium">
                            {{ activityLog?.log_name || "N/A" }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500"> Causer </span>

                        <span class="font-medium">
                            {{ activityLog?.causer?.name || "System" }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-1">Description</div>

                    <div class="text-gray-700">
                        {{ activityLog?.description || "N/A" }}
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="Object.keys(activityLog?.properties || {}).length"
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4"
        >
            <h3 class="text-base font-semibold border-b pb-2">Properties</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="(property, propertyIndex) in activityLog?.properties"
                    :key="propertyIndex"
                    class="border border-gray-200 rounded-lg p-4 bg-gray-50 space-y-2"
                >
                    <div class="text-sm font-medium border-b pb-1 text-center">
                        {{ titleFormat(propertyIndex) }}
                    </div>

                    <ModelPropertyAttributes :property="property" />
                </div>
            </div>
        </div>

        <div
            v-if="Object.keys(activityLog?.attribute_changes || {}).length"
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4"
        >
            <h3 class="text-base font-semibold border-b pb-2">
                Attribute Changes
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="(
                        property, propertyIndex
                    ) in activityLog?.attribute_changes"
                    :key="propertyIndex"
                    class="border border-gray-200 rounded-lg p-4 bg-gray-50 space-y-2"
                >
                    <div class="text-sm font-medium border-b pb-1 text-center">
                        {{ titleFormat(propertyIndex) }}
                    </div>

                    <ModelPropertyAttributes
                        :activityLog="activityLog"
                        :property="property"
                    />
                </div>
            </div>
        </div>

        <div
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4"
        >
            <h3 class="text-base font-semibold border-b pb-2">
                System Information
            </h3>

            <div
                class="text-sm border border-gray-200 rounded-lg p-4 flex justify-between"
            >
                <span class="text-gray-500"> Created At </span>

                <span class="font-medium">
                    {{
                        activityLog?.created_at
                            ? formatDateTime(activityLog?.created_at)
                            : "N/A"
                    }}
                </span>
            </div>
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
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
                >
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div
                            v-if="showDeleteModal"
                            class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4"
                        >
                            <h3 class="text-lg font-semibold text-red-600">
                                Delete Activity Log
                            </h3>

                            <p class="text-sm text-gray-500">
                                Are you sure you want to delete this activity
                                log? This action cannot be undone.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button
                                    @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm"
                                >
                                    Cancel
                                </button>

                                <button
                                    @click="handleDelete"
                                    :disabled="deleting"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2"
                                >
                                    <FontAwesomeIcon
                                        v-if="deleting"
                                        icon="spinner"
                                        spin
                                    />

                                    {{ deleting ? "Deleting..." : "Delete" }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
