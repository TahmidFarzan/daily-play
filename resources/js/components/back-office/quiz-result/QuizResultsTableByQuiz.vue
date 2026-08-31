<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faInfo } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faInfo)

const { t } = useTranslate()

const { quizResults } = defineProps({
    quizResults: { type: Object, required: true },
})
</script>

<template>
    <div>
        <div v-if="quizResults && quizResults.length">

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm">
                <table class="min-w-full text-sm text-left">

                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-2">
                                {{ t("common.labels.sl") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("common.labels.quizParticipant") }}
                            </th>

                            <th class="px-4 py-2">
                                {{ t("common.labels.createdAt") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("common.labels.actions") }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(item, index) in quizResults" :key="item.id"
                            class="border-t hover:bg-gray-50">

                            <td class="px-4 py-2">
                                {{ index + 1 }}
                            </td>

                            <td class="px-4 py-2">
                                {{ item?.quiz_participant?.name || t('common.labels.notAvailable') }}
                            </td>

                            <td class="px-4 py-2">
                                {{ formatDateTime(item.created_at) }}
                            </td>

                            <td class="px-4 py-2">
                                <a :href="route('back-office.quizzes.quiz-results.details', { slug: item?.quiz?.slug ,quizResultSlug: item.slug })"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs border border-blue-500 text-blue-500 rounded hover:bg-blue-50">
                                    <FontAwesomeIcon icon="info"/>
                                    {{ t("common.actions.details") }}
                                </a>
                            </td>

                        </tr>
                    </tbody>

                </table>
            </div>

        </div>

        <div v-else>
            <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-3 rounded">
                {{ t("common.labels.noRecordsFound") }}
            </div>
        </div>

    </div>
</template>
