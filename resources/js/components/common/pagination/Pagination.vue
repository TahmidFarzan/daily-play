<script setup>
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import {
    faChevronLeft,
    faChevronRight,
} from "@fortawesome/free-solid-svg-icons";

FontAwesomeLibrary.add(faChevronLeft, faChevronRight);

const { pagination } = defineProps({
    pagination: { type: Object, required: true },
});

const links = computed(() => pagination?.links || []);
const total = computed(() => pagination?.total || 0);
const from = computed(() => pagination?.from || 0);
const to = computed(() => pagination?.to || 0);
const currentPage = computed(() => pagination?.current_page || 1);
const lastPage = computed(() => pagination?.last_page || 1);

const hasPaginationInfo = computed(
    () => total.value > 0 && from.value > 0 && to.value > 0,
);

const goTo = (url) => {
    if (url) {
        router.get(
            url,
            {},
            {
                replace: true,
                preserveState: true,
                preserveScroll: true,
            },
        );
    }
};

const sanitizeLabel = (label) => label.replace(/&laquo;|&raquo;/g, "").trim();
const isPrevious = (label) =>
    label.includes("&laquo;") || label.toLowerCase().includes("previous");
const isNext = (label) =>
    label.includes("&raquo;") || label.toLowerCase().includes("next");
</script>

<template>
    <nav v-if="links.length > 3" class="mt-4">
        <div
            class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3 text-sm text-gray-600"
        >
            <div v-if="hasPaginationInfo">
                Showing <span class="font-medium">{{ from }}</span> to
                <span class="font-medium">{{ to }}</span> of
                <span class="font-medium">{{ total }}</span> entries
            </div>

            <div v-if="hasPaginationInfo">
                Page <span class="font-medium">{{ currentPage }}</span> of
                <span class="font-medium">{{ lastPage }}</span>
            </div>
        </div>

        <div class="flex justify-center md:justify-end overflow-x-auto">
            <ul class="inline-flex items-center gap-1">
                <li v-for="(link, index) in links" :key="index">
                    <button
                        v-if="link.url"
                        @click="goTo(link.url)"
                        class="px-3 py-1.5 text-sm rounded border transition"
                        :class="[
                            link.active
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100',
                        ]"
                    >
                        <span v-if="isPrevious(link.label)">
                            <FontAwesomeIcon icon="chevron-left" />
                        </span>

                        <span v-else-if="isNext(link.label)">
                            <FontAwesomeIcon icon="chevron-right" />
                        </span>

                        <span v-else v-html="sanitizeLabel(link.label)" />
                    </button>

                    <span
                        v-else
                        class="px-3 py-1.5 text-sm rounded border bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed inline-flex items-center"
                    >
                        <span v-if="isPrevious(link.label)">
                            <FontAwesomeIcon icon="chevron-left" />
                        </span>

                        <span v-else-if="isNext(link.label)">
                            <FontAwesomeIcon icon="chevron-right" />
                        </span>

                        <span v-else v-html="sanitizeLabel(link.label)" />
                    </span>
                </li>
            </ul>
        </div>
    </nav>
</template>
