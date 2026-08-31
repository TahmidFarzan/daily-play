<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue"
import Multiselect from "vue-multiselect"
import "vue-multiselect/dist/vue-multiselect.css"

const {
    selectedItem,
    fieldName,
    form,
    dataList,
    error,
    multiple,
    debounce,
    placeholder,
    selectedLabelKey,
    selectedValueKey,
    dataListLabelKey,
    dataListValueKey,
    compactDesign,
    useDarkTheme,
} = defineProps({
    selectedItem: { type: [String, Number, Object, Array], default: null },
    fieldName: { type: String, required: true },
    form: { type: Object, required: true },
    dataList: { type: [Array, Object], default: () => [] },
    error: { type: [String, Boolean], default: null },
    multiple: { type: Boolean, default: false },
    debounce: { type: Number, default: 300 },
    placeholder: { type: String, default: "Select" },
    selectedLabelKey: { type: String, default: "text" },
    selectedValueKey: { type: String, default: "value" },
    dataListLabelKey: { type: String, default: "text" },
    dataListValueKey: { type: String, default: "value" },
    compactDesign: { type: Boolean, default: false },
    useDarkTheme: { type: Boolean, default: false },
})

const options = ref([])
const loadingMore = ref(false)
const page = ref(1)
const perPage = 10
const lastPage = ref(1)
const searchQuery = ref("")
const vselectRef = ref(null)
const proxyModel = ref(multiple ? [] : null)

let searchTimeout = null

const allItems = computed(() =>
    Array.isArray(dataList)
        ? dataList.filter(Boolean)
        : Object.values(dataList ?? {}).filter(Boolean)
)

const formattedOptions = computed(() =>
    options.value.map(item => ({
        label: item?.[dataListLabelKey] ?? "",
        value: item?.[dataListValueKey] ?? null,
        raw: item,
    }))
)

const updateForm = val => {
    if (!form || !fieldName) return

    if (multiple) {
        form[fieldName] = Array.isArray(val)
            ? val.map(v => v?.raw?.[selectedValueKey] ?? v?.value ?? v)
            : []
    } else {
        form[fieldName] = val
            ? val?.raw?.[selectedValueKey] ?? val?.value ?? val
            : null
    }
}

const normalizeItem = item => {
    if (!item) return multiple ? [] : null

    if (typeof item === "object") {
        if (multiple && Array.isArray(item)) {
            return item.map(v => ({
                label: v?.[selectedLabelKey] ?? "",
                value: v?.[selectedValueKey] ?? null,
                raw: v,
            }))
        }

        return {
            label: item?.[selectedLabelKey] ?? "",
            value: item?.[selectedValueKey] ?? null,
            raw: item,
        }
    }

    const found = allItems.value.find(i => i?.[dataListValueKey] == item)

    return found
        ? {
            label: found?.[selectedLabelKey] ?? "",
            value: found?.[dataListValueKey],
            raw: found,
        }
        : multiple
            ? []
            : null
}

watch(proxyModel, val => updateForm(val), { deep: true })

const fetchPage = (pageNumber = 1, reset = false) => {
    const start = (pageNumber - 1) * perPage

    const filtered = allItems.value.filter(item => {
        const label = item?.[dataListLabelKey]

        return (
            typeof label === "string" &&
            label.toLowerCase().includes(searchQuery.value.toLowerCase())
        )
    })

    lastPage.value = Math.max(1, Math.ceil(filtered.length / perPage))

    const pageItems = filtered.slice(start, start + perPage)

    if (reset) {
        options.value = pageItems
    } else {
        options.value = [...options.value, ...pageItems]
    }
}

const onSearchDebounced = search => {
    searchQuery.value = search

    if (searchTimeout) clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        page.value = 1
        fetchPage(1, true)
    }, debounce)
}

const loadMoreManual = () => {
    if (loadingMore.value || page.value >= lastPage.value) return

    const dropdown =
        vselectRef.value?.$el?.querySelector(".multiselect__content-wrapper")

    if (!dropdown) return

    const prevScrollTop = dropdown.scrollTop

    loadingMore.value = true
    page.value++
    fetchPage(page.value, false)

    nextTick(() => {
        dropdown.scrollTop = prevScrollTop
        loadingMore.value = false
    })
}

const handleScroll = e => {
    const el = e.target

    if (
        el.scrollTop + el.clientHeight >= el.scrollHeight - 20 &&
        !loadingMore.value &&
        page.value < lastPage.value
    ) {
        loadMoreManual()
    }
}

const onDropdownOpen = () => {
    nextTick(() => {
        const dropdown =
            vselectRef.value?.$el?.querySelector(".multiselect__content-wrapper")

        if (!dropdown) return

        dropdown.removeEventListener("scroll", handleScroll)
        dropdown.addEventListener("scroll", handleScroll)
    })
}

onMounted(() => {
    proxyModel.value = normalizeItem(selectedItem)
    updateForm(proxyModel.value)
    fetchPage(1, true)
})
</script>

<template>
    <div class="multi-select-cointainer" :class="[
        error ? 'border border-red-500 rounded-md' : '',
        compactDesign ? 'multi-select-compact' : '',
        useDarkTheme ? 'multi-select-dark' : ''
    ]">
        <Multiselect ref="vselectRef" v-model="proxyModel" :options="formattedOptions" :multiple="multiple"
            :searchable="true" :clear-on-select="!multiple" :close-on-select="!multiple" :placeholder="placeholder"
            label="label" track-by="value" @search-change="onSearchDebounced" @open="onDropdownOpen">
            <template #afterList>
                <div v-if="loadingMore" class="text-center py-2 text-xs text-gray-400">
                    Loading...
                </div>
                <div v-else class="text-center py-1 text-xs text-gray-400">
                    Page {{ page }} / {{ lastPage }}
                </div>
            </template>
        </Multiselect>
    </div>
</template>

<style scoped>
.multi-select-compact :deep(.multiselect) {
    min-height: 31px;
    font-size: 13px;
}

.multi-select-compact :deep(.multiselect__select) {
    height: 31px;
    padding: 4px 8px;
}

.multi-select-compact :deep(.multiselect__tags) {
    min-height: 31px;
    padding: 4px 32px 0 8px;
    font-size: 13px;
}

.multi-select-compact :deep(.multiselect__single) {
    margin-bottom: 0;
    padding-top: 2px;
    font-size: 13px;
}

.multi-select-compact :deep(.multiselect__input) {
    font-size: 13px;
    margin-bottom: 0;
    padding: 0;
}

.multi-select-compact :deep(.multiselect__placeholder) {
    margin-bottom: 0;
    padding-top: 2px;
    font-size: 13px;
}

.multi-select-compact :deep(.multiselect__option) {
    min-height: 30px;
    padding: 6px 10px;
    font-size: 13px;
}

.multi-select-compact :deep(.multiselect__tag) {
    margin-bottom: 2px;
    padding: 3px 22px 3px 8px;
    font-size: 12px;
}

.multi-select-dark :deep(.multiselect) {
    color: #e5e7eb;
}

.multi-select-dark :deep(.multiselect__tags) {
    background: #111827;
    border-color: #374151;
    color: #e5e7eb;
}

.multi-select-dark :deep(.multiselect__single) {
    background: transparent;
    color: #e5e7eb;
}

.multi-select-dark :deep(.multiselect__input) {
    background: transparent;
    color: #e5e7eb;
}

.multi-select-dark :deep(.multiselect__placeholder) {
    color: #9ca3af;
}

.multi-select-dark :deep(.multiselect__content-wrapper) {
    background: #111827;
    border-color: #374151;
}

.multi-select-dark :deep(.multiselect__option) {
    background: #111827;
    color: #e5e7eb;
}

.multi-select-dark :deep(.multiselect__option--highlight) {
    background: #1f2937;
    color: #ffffff;
}

.multi-select-dark :deep(.multiselect__option--selected) {
    background: #374151;
    color: #ffffff;
}

.multi-select-dark :deep(.multiselect__tag) {
    background: #374151;
    color: #ffffff;
}

.multi-select-dark :deep(.multiselect__tag-icon::after) {
    color: #ffffff;
}

.multi-select-dark :deep(.multiselect__spinner) {
    background: #111827;
}

:deep(.multiselect__content-wrapper) {
    max-height: 250px;
    overflow-y: auto;
}
</style>
