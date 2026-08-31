<script setup>
import { ref, watch, onMounted } from "vue";
import { fetchFromApi } from "@/composables/useApiClient";
import { apiCacheKey, apiCacheTTL } from "@/composables/useApiCache";

const {
    selectedItem,
    fieldName,
    form,
    apiUrl,
    apiLabelKey,
    apiValueKey,
    defaultLabel,
} = defineProps({
    selectedItem: {
        type: Array,
        default: () => [],
    },

    fieldName: {
        type: String,
        required: true,
    },

    form: {
        type: Object,
        required: true,
    },

    apiUrl: {
        type: String,
        required: true,
    },

    apiLabelKey: {
        type: String,
        default: "name",
    },

    apiValueKey: {
        type: String,
        default: "id",
    },

    defaultLabel: {
        type: String,
        default: null,
    },
    isRequired: {
        type: Boolean,
        default: false,
    },
});

const groupedItems = ref({});
const expandedGroups = ref([]);

const selectedIds = ref(Array.isArray(selectedItem) ? [...selectedItem] : []);

watch(
    () => selectedItem,
    (value) => {
        selectedIds.value = [...(value || [])];
    },
    {
        immediate: true,
    },
);

function syncForm() {
    const newValue = [...selectedIds.value];

    const current = form[fieldName] || [];

    const changed = JSON.stringify(current) !== JSON.stringify(newValue);

    if (!changed) {
        return;
    }

    form[fieldName] = newValue;
    form.clearErrors?.(fieldName);
}

async function loadData() {
    try {
        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_MULTI_SELECT}:${apiUrl}`,
                ttl: apiCacheTTL.API_MULTI_SELECT,
            },
        );

        groupedItems.value = response?.data || response || {};
        expandedGroups.value = Object.keys(groupedItems.value);
    } catch {
        groupedItems.value = {};
    }
}

function toggleGroup(module) {
    const exists = expandedGroups.value.includes(module);

    expandedGroups.value = exists
        ? expandedGroups.value.filter((item) => item !== module)
        : [...expandedGroups.value, module];
}

function isChecked(value) {
    return selectedIds.value.includes(value);
}

function toggleItem(item) {
    const value = item[apiValueKey];

    if (isChecked(value)) {
        selectedIds.value = selectedIds.value.filter((id) => id !== value);
    } else {
        selectedIds.value.push(value);
    }

    syncForm();
}

function isGroupChecked(module) {
    const items = groupedItems.value[module] || [];

    if (!items.length) {
        return false;
    }

    return items.every((item) => selectedIds.value.includes(item[apiValueKey]));
}

function toggleGroupSelection(module) {
    const items = groupedItems.value[module] || [];
    const ids = items.map((item) => item[apiValueKey]);

    const allSelected = ids.every((id) => selectedIds.value.includes(id));

    if (allSelected) {
        selectedIds.value = selectedIds.value.filter((id) => !ids.includes(id));
    } else {
        selectedIds.value = [...new Set([...selectedIds.value, ...ids])];
    }

    syncForm();
}

onMounted(loadData);
</script>

<template>
    <div>
        <label v-if="defaultLabel" class="block mb-3 text-sm font-medium">
            {{ defaultLabel }}
            <span v-if="isRequired" class="text-red-500">*</span>
        </label>

        <div class="space-y-4">
            <div
                v-for="(items, module) in groupedItems"
                :key="module"
                class="border rounded-xl overflow-hidden"
            >
                <button
                    type="button"
                    class="w-full px-5 py-4 flex items-center justify-between bg-gray-50 hover:bg-gray-100"
                >
                    <div class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            :checked="isGroupChecked(module)"
                            @click.stop
                            @change="toggleGroupSelection(module)"
                            class="h-5 w-5"
                        />
                        <span
                            class="font-semibold cursor-pointer"
                            @click="toggleGroup(module)"
                        >
                            {{ module }}
                        </span>
                    </div>
                    <span class="cursor-pointer" @click="toggleGroup(module)">
                        {{ expandedGroups.includes(module) ? "▲" : "▼" }}
                    </span>
                </button>

                <div v-if="expandedGroups.includes(module)" class="p-4">
                    <div class="grid md:grid-cols-3 gap-3">
                        <label v-for="item in items" :key="item[apiValueKey]">
                            <input
                                type="checkbox"
                                class="hidden"
                                :checked="isChecked(item[apiValueKey])"
                                @change="toggleItem(item)"
                            />

                            <div
                                class="rounded-xl border p-4 cursor-pointer transition"
                                :class="
                                    isChecked(item[apiValueKey])
                                        ? 'bg-blue-50 border-blue-500'
                                        : 'hover:border-gray-400'
                                "
                            >
                                <div class="font-medium">
                                    {{ item[apiLabelKey] }}
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
