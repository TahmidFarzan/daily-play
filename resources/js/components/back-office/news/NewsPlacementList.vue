<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faInfo } from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faInfo)

const { t } = useTranslate()

const { news, newsPlacements } = defineProps({
    news: {
        type: Object,
        required: true,
    },

    newsPlacements: {
        type: Array,
        default: () => [],
    },
})
</script>

<template>
    <div class="w-full rounded-xl border border-gray-200 bg-white shadow-sm">
        <div v-if="!newsPlacements.length" class="px-4 py-8 text-center text-sm text-gray-500">
            {{ t("common.labels.noRecordsFound") }}
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">{{ t("common.labels.page") }}</th>
                        <th class="px-4 py-3 font-semibold">{{ t("common.labels.section") }}</th>
                        <th class="px-4 py-3 font-semibold">{{ t("common.labels.position") }}</th>
                        <th class="px-4 py-3 font-semibold">{{ t("common.labels.action") }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    <tr v-for="newsPlacement in newsPlacements" :key="newsPlacement.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ newsPlacement.page }}
                        </td>

                        <td class="px-4 py-3 text-gray-700">
                            {{ newsPlacement.page_section }}
                        </td>

                        <td class="px-4 py-3 text-gray-700">
                            {{ newsPlacement.position }}
                        </td>

                        <td class="px-4 py-3">
                            <a :href="route('back-office.news.news-placements.details', {
                                slug: news?.slug,
                                newsPlacementSlug: newsPlacement.slug,
                            })"
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
