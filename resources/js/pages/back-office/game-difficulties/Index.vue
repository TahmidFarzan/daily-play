<script setup>
import Layout from "@/pages/layouts/AuthLayout.vue";
import ModelPagination from "@/components/common/pagination/Pagination.vue";
import InfiniteScrollApiSelect from "@/components/common/multi-select/InfiniteScrollApiSelect.vue";

import { ref, computed, onMounted, nextTick } from "vue";
import { Head, useForm, router as inertiaJsRoute } from "@inertiajs/vue3";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import { faFilter, faInfo, faSpinner } from "@fortawesome/free-solid-svg-icons";

import { formatDateTime } from "@/composables/useDateTime";
import { itemListFilterParameters } from "@/composables/useDataTable";

FontAwesomeLibrary.add(faFilter, faInfo, faSpinner);

defineOptions({ layout: Layout });

const { gameDifficulties } = defineProps({
    gameDifficulties: Object,
});

const paginationOnly = computed(() => {
    if (!gameDifficulties) return {};

    const { data, ...rest } = gameDifficulties;

    return rest;
});

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    date: null,
    search: null,
});

const applyFilter = () => {
    if (filterForm.processing) return;

    const cleanParams = itemListFilterParameters(filterForm.data());

    inertiaJsRoute.get(
        route("back-office.game-difficulties.index"),
        cleanParams,
        {
            replace: true,
            preserveScroll: true,
            preserveState: true,
        },
    );
};

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search);

    filterForm.per_page = urlParams.get("per_page") || null;
    filterForm.created_by_id = urlParams.get("created_by_id") || null;
    filterForm.date = urlParams.get("date") || null;
    filterForm.search = urlParams.get("search") || null;

    await nextTick();

    window.dispatchEvent(
        new CustomEvent("set-breadcrumb", {
            detail: [{ text: "Game Difficulties", active: true }],
        }),
    );
});
</script>

<template>
    <Head title="Game Difficulties" />

    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Game Difficulties</h2>
        </div>

        <form
            @submit.prevent="applyFilter"
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4"
        >
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <InfiniteScrollApiSelect
                    :form="filterForm"
                    fieldName="per_page"
                    :selectedItem="filterForm.per_page || null"
                    :apiUrl="route('search.per-pages')"
                    :multiple="false"
                    selectedLabelKey="name"
                    selectedValueKey="id"
                    apiLabelKey="name"
                    apiValueKey="id"
                    placeholder="Per Page"
                />

                <InfiniteScrollApiSelect
                    :form="filterForm"
                    fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id || null"
                    :apiUrl="route('search.users')"
                    :multiple="false"
                    selectedLabelKey="name"
                    selectedValueKey="id"
                    apiLabelKey="name"
                    apiValueKey="id"
                    placeholder="Created By"
                />

                <input
                    v-model="filterForm.date"
                    type="date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />

                <input
                    v-model="filterForm.search"
                    type="search"
                    placeholder="Search by name or brief..."
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-50"
                >
                    <FontAwesomeIcon
                        v-if="filterForm.processing"
                        icon="spinner"
                        spin
                    />
                    <FontAwesomeIcon icon="filter" />

                    {{ filterForm.processing ? "Applying..." : "Apply Filter" }}
                </button>
            </div>
        </form>

        <div
            class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Created At</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr
                            v-for="(item, index) in gameDifficulties?.data"
                            :key="item.id"
                            class="hover:bg-gray-50 transition"
                        >
                            <td class="px-4 py-3">{{ index + 1 }}</td>

                            <td class="px-4 py-3 font-medium">
                                {{ item.name || "N/A" }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{
                                    item.created_at
                                        ? formatDateTime(item.created_at)
                                        : "N/A"
                                }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <a
                                    :href="
                                        route(
                                            'back-office.game-difficulties.details',
                                            { slug: item.slug },
                                        )
                                    "
                                    class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                    title="View Details"
                                >
                                    <FontAwesomeIcon icon="info" />
                                </a>
                            </td>
                        </tr>

                        <tr v-if="!gameDifficulties?.data?.length">
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-gray-500"
                            >
                                No game difficulties found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ModelPagination :pagination="paginationOnly" />
    </div>
</template>
