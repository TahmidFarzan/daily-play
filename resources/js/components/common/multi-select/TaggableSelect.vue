<script setup>
import { ref, watch, onMounted } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";

const { selectedItem, fieldName, form, error, placeholder } = defineProps({
    selectedItem: { type: [String, Number, Object, Array], default: null },
    fieldName: { type: String, required: true },
    form: { type: Object, required: true },
    error: { type: [String, Boolean], default: null },
    placeholder: { type: String, default: "Select" },
});

const proxyModel = ref([]);
const options = ref([]);

const normalizeInput = (input) => {
    if (!input) return [];

    if (Array.isArray(input)) return input;

    if (typeof input === "string") {
        return input
            .split(",")
            .map((i) => i.trim().toLowerCase())
            .filter((i) => i);
    }

    return [];
};

onMounted(() => {
    proxyModel.value = normalizeInput(selectedItem);
    updateForm();
});

const addTag = (tag) => {
    const value = tag.trim().toLowerCase();
    if (!proxyModel.value.includes(value)) {
        proxyModel.value.push(value);
    }
};

const updateForm = () => {
    form[fieldName] = proxyModel.value;
};

watch(
    proxyModel,
    () => {
        updateForm();
    },
    { deep: true },
);
</script>

<template>
    <div :class="error ? 'border border-red-500 rounded-md' : ''">
        <Multiselect
            v-model="proxyModel"
            :options="options"
            :multiple="true"
            :taggable="true"
            :close-on-select="false"
            :searchable="true"
            :placeholder="placeholder"
            @tag="addTag"
        />
    </div>
</template>
