<script setup>
import { computed, onMounted } from "vue";
import { Head, useForm, router as inertiaJsRouter } from "@inertiajs/vue3";
import Layout from "@/pages/layouts/PublicLayout.vue";
import MediaRenderer from "@/components/common/media/MediaRenderer.vue";
import ModelPagination from "@/components/common/pagination/Pagination.vue";
import InfiniteScrollApiSelect from "@/components/common/multi-select/InfiniteScrollApiSelect.vue";

import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faArrowLeft,
    faFilter,
    faGamepad,
    faRotateLeft,
    faSpinner,
} from "@fortawesome/free-solid-svg-icons";

import { formatDateTime } from "@/composables/useDateTime";
import { itemListFilterParameters } from "@/composables/useDataTable";

FontAwesomeLibrary.add(
    faArrowLeft,
    faFilter,
    faGamepad,
    faRotateLeft,
    faSpinner,
);

defineOptions({
    layout: Layout,
});

const { game, gamePlays } = defineProps({
    game: { type: Object, default: null },
    gamePlays: { type: Object, default: null },
});

const paginationOnly = computed(() => {
    if (!gamePlays) return {};

    const { data, ...rest } = gamePlays;

    return rest;
});

const filterForm = useForm({
    per_page: null,
    date: null,
    search: null,
});

const applyFilter = () => {
    if (filterForm.processing) return;

    const cleanParams = itemListFilterParameters(filterForm.data());

    inertiaJsRouter.get(
        route("games.details", {
            slug: game?.slug,
        }),
        cleanParams,
        {
            replace: true,
            preserveScroll: true,
            preserveState: true,
            onFinish: () => (filterForm.processing = false),
        },
    );
};

const clearFilter = () => {
    filterForm.search = null;
    filterForm.date = null;
    applyFilter();
};

const serialNumber = (index) => {
    const from = gamePlays?.from || 0;
    return from > 0 ? from + index : index + 1;
};

const formatDate = (value) => (value ? formatDateTime(value, "d-M-Y") : "—");
const formatClock = (value) => (value ? formatDateTime(value, "h:mm a") : "—");

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);

    filterForm.per_page = urlParams.get("per_page") || null;
    filterForm.date = urlParams.get("date") || null;
    filterForm.search = urlParams.get("search") || null;
});
</script>

<template>
    <Head :title="game?.name || 'Game Details'" />

    <section class="w-full space-y-6">
        <a
            :href="route('home')"
            class="inline-flex items-center gap-2 text-sm font-medium text-[var(--daily-play-text-muted)] transition hover:text-[var(--daily-play-accent)]"
        >
            <FontAwesomeIcon icon="arrow-left" class="text-xs" />
            Back to games
        </a>

        <div
            class="grid grid-cols-1  md:grid-cols-12 sm:grid-cols-12 gap-6 overflow-hidden rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] p-6 shadow-sm "
        >
            <div
                class="flex h-full min-h-56 w-full items-center justify-center overflow-hidden rounded-2xl bg-gray-100 sm:col-span-6 md:col-span-6 lg:col-span-3"
            >
                <MediaRenderer
                    v-if="game?.logo"
                    :media="game.logo"
                    media-class="w-full h-full object-contain p-2"
                    :hideCaption="true"
                />

                <FontAwesomeIcon
                    v-else
                    icon="gamepad"
                    class="text-6xl text-[var(--daily-play-text-muted)]"
                />
            </div>

            <div class="flex flex-col gap-4 sm:col-span-6 md:col-span-6 lg:col-span-9">
                <h1
                    class="text-2xl font-bold tracking-tight text-[var(--daily-play-text)] sm:text-3xl"
                >
                    {{ game?.name || "Untitled Game" }}
                </h1>

                <p
                    v-if="game?.brief"
                    class="text-[var(--daily-play-text-muted)]"
                >
                    {{ game.brief }}
                </p>

                <div
                    v-if="game?.how_to_play"
                    class="rounded-xl border border-[var(--daily-play-border)] bg-gray-50 p-4"
                >
                    <h2
                        class="mb-2 text-sm font-semibold uppercase tracking-wide text-[var(--daily-play-text-muted)]"
                    >
                        How to Play
                    </h2>

                    <p
                        class="whitespace-pre-line text-[var(--daily-play-text)]"
                    >
                        {{ game.how_to_play }}
                    </p>
                </div>

                <a
                    v-if="game?.slug"
                    :href="route('games.play', { slug: game.slug })"
                    class="mt-auto inline-flex items-center justify-center gap-2 rounded-xl bg-[var(--daily-play-accent)] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[var(--daily-play-accent-hover)]"
                >
                    <FontAwesomeIcon icon="gamepad" />
                    Play {{ game?.name || "Game" }}
                </a>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] p-6 shadow-sm"
        >
            <div class="mb-4 flex items-center justify-between gap-2">
                <h2
                    class="text-lg font-bold tracking-tight text-[var(--daily-play-text)]"
                >
                    Game Plays
                </h2>
            </div>

            <form @submit.prevent="applyFilter"
                class="mb-5 grid grid-cols-1 gap-3 rounded-xl border border-[var(--daily-play-border)] bg-gray-50 p-4 sm:grid-cols-2 lg:grid-cols-4">

                <InfiniteScrollApiSelect
                    :form="filterForm"
                    fieldName="per_page"
                    :selectedItem="filterForm.per_page || null"
                    :apiUrl="route('search.per-pages')"
                    :multiple="false"
                    :compactDesign="true"
                    selectedLabelKey="name"
                    selectedValueKey="id"
                    apiLabelKey="name"
                    apiValueKey="id"
                    placeholder="Per Page"
                />

                <input
                    v-model="filterForm.date"
                    type="date"
                    class="w-full rounded-lg border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-3 py-2 text-sm text-[var(--daily-play-text)] transition focus:border-[var(--daily-play-accent)] focus:outline-none"/>


                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        :disabled="filterForm.processing"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-[var(--daily-play-accent)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[var(--daily-play-accent-hover)] disabled:opacity-60"
                    >
                        <FontAwesomeIcon
                            :icon="filterForm.processing ? 'spinner' : 'filter'"
                            :spin="filterForm.processing"
                        />
                        {{ filterForm.processing ? "Applying..." : "Filter" }}
                    </button>

                    <button
                        type="button"
                        @click="clearFilter"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-3 py-2 text-sm font-medium text-[var(--daily-play-text-muted)] transition hover:text-[var(--daily-play-text)]"
                        :disabled="filterForm.processing"
                    >
                        <FontAwesomeIcon icon="rotate-left" class="text-xs" />
                        Reset
                    </button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-[var(--daily-play-border)] text-left text-xs font-semibold uppercase tracking-wide text-[var(--daily-play-text-muted)]"
                        >
                            <th class="px-4 py-3">Sl</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Diffculty</th>
                            <th class="px-4 py-3">Start Time</th>
                            <th class="px-4 py-3">End Time</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[var(--daily-play-border)]">
                        <tr
                            v-for="(item, index) in gamePlays?.data"
                            :key="item.id"
                            class="transition hover:bg-gray-50"
                        >
                            <td
                                class="px-4 py-3 font-mono tabular-nums text-[var(--daily-play-text-muted)]"
                            >
                                {{ serialNumber(index) }}
                            </td>
                            <td
                                class="px-4 py-3 font-medium text-[var(--daily-play-text)]"
                            >
                                {{ formatDate(item.game_date) }}
                            </td>
                            <td
                                class="px-4 py-3 font-medium text-[var(--daily-play-text)]"
                            >
                                {{ item.game_difficulty?.name || "N/A" }}
                            </td>
                            <td class="px-4 py-3 text-[var(--daily-play-text)]">
                                {{ formatClock(item.starts_at) }}
                            </td>
                            <td class="px-4 py-3 text-[var(--daily-play-text)]">
                                {{ formatClock(item.ends_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span
                                    class="inline-flex items-center rounded-full border border-[var(--daily-play-border)] px-3 py-1 text-xs font-medium text-[var(--daily-play-text-muted)]"
                                >
                                    Coming soon
                                </span>
                            </td>
                        </tr>

                        <tr v-if="!gamePlays?.data?.length">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-sm text-[var(--daily-play-text-muted)]"
                            >
                                No game plays found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <ModelPagination :pagination="paginationOnly" />
        </div>
    </section>
</template>

<style scoped></style>
