<script setup>
import { computed, ref, watch } from "vue";
import axios from "axios";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import {
    faAngleDown,
    faAngleUp,
    faSpinner,
} from "@fortawesome/free-solid-svg-icons";

FontAwesomeLibrary.add(faAngleDown, faAngleUp, faSpinner);

const { pagination, propName = "news" } = defineProps({
    pagination: {
        type: Object,
        required: true,
    },

    propName: {
        type: String,
    },
});

const emit = defineEmits(["append", "remove-last", "replace"]);

const currentPagination = ref({});
const historyStack = ref([]);
const loadingDirection = ref(null);

const cloneObject = (value) => {
    return JSON.parse(JSON.stringify(value || {}));
};

watch(
    () => pagination,
    (value) => {
        currentPagination.value = cloneObject(value);
    },
    {
        immediate: true,
        deep: true,
    },
);

const getItems = (value) => {
    if (Array.isArray(value?.data)) {
        return value.data;
    }

    return [];
};

const buildCursorUrl = (cursor) => {
    if (!cursor) {
        return null;
    }

    const basePath = currentPagination.value?.path || window.location.pathname;
    const url = new URL(basePath, window.location.origin);
    const currentQuery = new URLSearchParams(window.location.search);

    currentQuery.forEach((value, key) => {
        if (key !== "cursor") {
            url.searchParams.set(key, value);
        }
    });

    url.searchParams.set("cursor", cursor);

    if (currentPagination.value?.per_page) {
        url.searchParams.set("per_page", currentPagination.value.per_page);
    }

    return url.toString();
};

const nextPageUrl = computed(() => {
    return (
        currentPagination.value?.next_page_url ||
        currentPagination.value?.links?.next ||
        buildCursorUrl(currentPagination.value?.next_cursor)
    );
});

const prevPageUrl = computed(() => {
    return (
        currentPagination.value?.prev_page_url ||
        currentPagination.value?.links?.prev ||
        buildCursorUrl(currentPagination.value?.prev_cursor)
    );
});

const canLoadMore = computed(() => {
    return Boolean(nextPageUrl.value);
});

const canLoadLess = computed(() => {
    return historyStack.value.length > 0 || Boolean(prevPageUrl.value);
});

const isLoadingMore = computed(() => {
    return loadingDirection.value === "next";
});

const isLoadingLess = computed(() => {
    return loadingDirection.value === "prev";
});

const getPaginationFromResponse = (response) => {
    if (response?.data?.[propName]) {
        return response.data[propName];
    }

    if (response?.data?.data) {
        return response.data;
    }

    return null;
};

const fetchCursorUrl = async (url, direction) => {
    if (!url || loadingDirection.value) {
        return;
    }

    loadingDirection.value = direction;

    try {
        const response = await axios.get(url, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });

        const newPagination = getPaginationFromResponse(response);

        if (!newPagination) {
            return;
        }

        if (direction === "next") {
            const newItemsCount = getItems(newPagination).length;

            historyStack.value.push({
                pagination: cloneObject(currentPagination.value),
                count: newItemsCount,
            });

            currentPagination.value = cloneObject(newPagination);

            emit("append", newPagination);
        }

        if (direction === "prev") {
            historyStack.value = [];
            currentPagination.value = cloneObject(newPagination);

            emit("replace", newPagination);
        }
    } finally {
        loadingDirection.value = null;
    }
};

const loadMore = () => {
    fetchCursorUrl(nextPageUrl.value, "next");
};

const loadLess = () => {
    if (loadingDirection.value) {
        return;
    }

    if (historyStack.value.length > 0) {
        const lastHistory = historyStack.value.pop();

        currentPagination.value = cloneObject(lastHistory.pagination);

        emit("remove-last", {
            count: lastHistory.count,
            pagination: cloneObject(lastHistory.pagination),
        });

        return;
    }

    fetchCursorUrl(prevPageUrl.value, "prev");
};
</script>

<template>
    <div
        v-if="canLoadMore || canLoadLess"
        class="flex flex-col items-center justify-center gap-3 pt-4 sm:flex-row"
    >
        <button
            v-if="canLoadLess"
            type="button"
            :disabled="Boolean(loadingDirection)"
            class="group inline-flex min-w-32 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-950 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            @click="loadLess"
        >
            <FontAwesomeIcon
                v-if="isLoadingLess"
                :icon="faSpinner"
                class="animate-spin text-xs"
            />

            <FontAwesomeIcon
                v-else
                :icon="faAngleUp"
                class="text-xs transition duration-300 group-hover:-translate-y-0.5"
            />

            <span>
                {{ isLoadingLess ? "Loading..." : "Load Less" }}
            </span>
        </button>

        <button
            v-if="canLoadMore"
            type="button"
            :disabled="Boolean(loadingDirection)"
            class="group inline-flex min-w-32 items-center justify-center gap-2 rounded-xl border border-blue-600 bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-200 transition duration-300 hover:-translate-y-0.5 hover:border-blue-700 hover:bg-blue-700 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
            @click="loadMore"
        >
            <span>
                {{ isLoadingMore ? "Loading..." : "Load More" }}
            </span>

            <FontAwesomeIcon
                v-if="isLoadingMore"
                :icon="faSpinner"
                class="animate-spin text-xs"
            />

            <FontAwesomeIcon
                v-else
                :icon="faAngleDown"
                class="text-xs transition duration-300 group-hover:translate-y-0.5"
            />
        </button>
    </div>
</template>
