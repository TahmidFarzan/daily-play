<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faInfo } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faInfo)

const { t } = useTranslate()

const { news } = defineProps({
    news: {
        type: Array,
        default: () => [],
    },
})
</script>

<template>
    <div class="w-full rounded-xl border border-gray-200 bg-white shadow-sm">
        <div v-if="!news.length" class="px-4 py-8 text-center text-sm text-gray-500">
            {{ t("admin.components.news.relatedOrRelevantNewsList.labels.noNewsFound") }}
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">{{ t("common.labels.title") }}</th>
                        <th class="px-4 py-3 font-semibold">{{ t("common.labels.category") }}</th>
                        <th class="px-4 py-3 font-semibold">{{ t("common.labels.position") }}</th>
                        <th class="px-4 py-3 font-semibold">{{ t("common.labels.action") }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    <tr v-for="perNews in news" :key="perNews.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ perNews.title }}
                        </td>

                        <td class="px-4 py-3 text-gray-700">
                            {{ perNews.category?.name }}
                        </td>

                        <td class="px-4 py-3 text-gray-700">
                            {{ formatDateTime(perNews.created_at) }}
                        </td>

                        <td class="px-4 py-3">
                            <a :href="route('back-office.news.details', { slug: perNews?.slug })"
                                class="inline-flex items-center gap-1 rounded border border-blue-500 px-2 py-1 text-xs text-blue-500 hover:bg-blue-50">
                                <FontAwesomeIcon icon="info" />
                                {{ t("common.actions.details") }}
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
