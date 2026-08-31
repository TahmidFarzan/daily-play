<script setup>
import { computed, ref } from 'vue'

import ModelPropertyAttributes from '@/components/back-office/activity-log/ModelPropertyAttributes.vue'

import { titleFormat } from '@/composables/useStringFormat'
import { formatDateTime } from '@/composables/useDateTime'
import { fetchUser } from '@/composables/useApiClient'

const { property, activityLog } = defineProps({
    property: {
        required: true,
    },
    activityLog: {
        type: Object,
    },
})

const tUser = ref({})

function isObject(val) {
    return Object.prototype.toString.call(val) === '[object Object]'
}

function isBase64Image(val) {
    return typeof val === 'string' && val.startsWith('data:image')
}

function isEmptyValue(value) {
    if (value === null || value === undefined) return true

    if (typeof value === 'string') {
        return value.trim() === ''
    }

    if (Array.isArray(value)) {
        return value.length === 0
    }

    if (isObject(value)) {
        return Object.values(value).every(isEmptyValue)
    }

    return false
}

function isThemeSubject() {
    return activityLog?.subject_type?.includes('Theme') ?? false
}

function isThemeOption(value) {
    return (
        isThemeSubject() &&
        isObject(value) &&
        Object.prototype.hasOwnProperty.call(value, 'valueType') &&
        Object.prototype.hasOwnProperty.call(value, 'value')
    )
}

function isEqualValue(first, second) {
    if (Object.is(first, second)) {
        return true
    }

    if (isObject(first) && isObject(second)) {
        const firstKeys = Object.keys(first)
        const secondKeys = Object.keys(second)

        if (firstKeys.length !== secondKeys.length) {
            return false
        }

        return firstKeys.every(
            (key) =>
                Object.prototype.hasOwnProperty.call(second, key) &&
                isEqualValue(first[key], second[key]),
        )
    }

    if (Array.isArray(first) && Array.isArray(second)) {
        if (first.length !== second.length) {
            return false
        }

        return first.every((item, index) =>
            isEqualValue(item, second[index]),
        )
    }

    return false
}

function getChangedThemeOptions(options) {
    if (!isThemeSubject() || !isObject(options)) {
        return options
    }

    const attributesOptions =
        activityLog?.attribute_changes?.attributes?.options ?? {}

    const oldOptions =
        activityLog?.attribute_changes?.old?.options ?? {}

    return Object.fromEntries(
        Object.entries(options).filter(([optionKey, optionValue]) => {
            if (!isThemeOption(optionValue)) {
                return true
            }

            const attributeOption = attributesOptions[optionKey]
            const oldOption = oldOptions[optionKey]

            if (!isThemeOption(attributeOption) || !isThemeOption(oldOption)) {
                return true
            }

            return !isEqualValue(
                attributeOption.value,
                oldOption.value,
            )
        }),
    )
}

const filteredProperty = computed(() => {
    if (!isObject(property)) {
        return {}
    }

    return Object.fromEntries(
        Object.entries(property)
            .map(([key, value]) => {
                if (key === 'options' && isThemeSubject()) {
                    return [key, getChangedThemeOptions(value)]
                }

                return [key, value]
            })
            .filter(([, value]) => !isEmptyValue(value)),
    )
})

function formatValue(key, value) {
    if (
        ['created_at', 'updated_at', 'deleted_at', 'email_verified_at'].includes(key) &&
        value
    ) {
        return formatDateTime(value)
    }

    if (
        ['user_id', 'created_by_id'].includes(key) &&
        Number.isInteger(Number(value))
    ) {
        if (!(value in tUser.value)) {
            tUser.value[value] = null

            fetchUser(value)
                .then((user) => {
                    tUser.value[value] = user || null
                })
                .catch(() => {
                    tUser.value[value] = null
                })

            return 'Loading...'
        }

        return tUser.value[value]?.name || 'Unknown'
    }

    return value
}
</script>

<template>
    <div v-if="Object.keys(filteredProperty).length" class="space-y-2">
        <template v-for="(value, key) in filteredProperty" :key="key">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <div class="mb-1 text-sm font-semibold text-gray-700">
                    {{ titleFormat(key) }}
                </div>

                <div class="text-sm text-gray-800">
                    <template v-if="isObject(value)">
                        <div class="mt-2 ml-4 border-l-2 border-gray-200 pl-3">
                            <template v-for="(optionValue, optionKey) in value" :key="optionKey">
                                <div v-if="!isEmptyValue(optionValue)" class="mb-2">
                                    <template v-if="isThemeOption(optionValue)">
                                        <div class="flex items-start gap-3">
                                            <div class="min-w-0 flex-1 font-medium">
                                                {{ titleFormat(optionKey) }}
                                            </div>

                                            <div class="min-w-0 flex-1 font-medium">
                                                {{ formatValue(optionKey, optionValue.value) }}
                                            </div>
                                        </div>
                                    </template>

                                    <template v-else-if="isObject(optionValue)">
                                        <div class="mb-1 text-sm font-semibold text-gray-700">
                                            {{ titleFormat(optionKey) }}
                                        </div>

                                        <div class="ml-4 border-l-2 border-gray-200 pl-3">
                                            <ModelPropertyAttributes :property="optionValue"
                                                :activityLog="activityLog" />
                                        </div>
                                    </template>

                                    <template v-else-if="Array.isArray(optionValue)">
                                        <div class="mb-1 text-sm font-semibold text-gray-700">
                                            {{ titleFormat(optionKey) }}
                                        </div>

                                        <ul class="ml-4 list-disc space-y-1">
                                            <li v-for="(item, index) in optionValue" :key="index">
                                                {{ formatValue(optionKey, item) }}
                                            </li>
                                        </ul>
                                    </template>

                                    <template v-else>
                                        <div class="flex items-start gap-3">
                                            <div class="min-w-0 flex-1 font-medium">
                                                {{ titleFormat(optionKey) }}
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                {{ formatValue(optionKey, optionValue) }}
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template v-else-if="Array.isArray(value)">
                        <ul class="ml-4 list-disc space-y-1">
                            <li v-for="(item, index) in value" :key="index">
                                <template v-if="isObject(item)">
                                    <div class="rounded border border-gray-200 bg-white p-2">
                                        <ModelPropertyAttributes :property="item" :activityLog="activityLog" />
                                    </div>
                                </template>

                                <template v-else-if="Array.isArray(item)">
                                    <div class="rounded border border-gray-200 bg-white p-2">
                                        {{ item.join(', ') }}
                                    </div>
                                </template>

                                <template v-else>
                                    {{ formatValue(key, item) }}
                                </template>
                            </li>
                        </ul>
                    </template>

                    <template v-else-if="isBase64Image(value)">
                        <div class="mt-2">
                            <img :src="value" class="max-w-[150px] rounded-lg border shadow-sm">
                        </div>
                    </template>

                    <template v-else-if="key === 'content'">
                        <div class="prose prose-sm max-w-none" v-html="value" />
                    </template>

                    <template v-else>
                        {{ formatValue(key, value) }}
                    </template>
                </div>
            </div>
        </template>
    </div>

    <div v-else-if="property !== null && property !== undefined" class="text-sm text-gray-800">
        {{ property }}
    </div>
</template>
