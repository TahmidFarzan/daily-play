<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faInfo } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faInfo)

const { t } = useTranslate()

const { quizQuestion } = defineProps({
    quizQuestion: { type: Object, required: true },
})
</script>

<template>
    <div>

        <div v-if="quizQuestion?.quiz_question_options && quizQuestion?.quiz_question_options?.length">

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm">
                <table class="min-w-full text-sm text-left">

                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-2">
                                {{ t("common.labels.sl") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("common.labels.option") }}
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
                        <tr v-for="(item, index) in quizQuestion?.quiz_question_options" :key="item.id"
                            class="border-t hover:bg-gray-50">

                            <td class="px-4 py-2">
                                {{ index + 1 }}
                            </td>

                            <td class="px-4 py-2">
                                {{ item?.option }}
                            </td>

                            <td class="px-4 py-2">
                                {{ formatDateTime(item.created_at) }}
                            </td>

                            <td class="px-4 py-2">
                                <a :href="route('back-office.quizzes.quiz-questions.quiz-question-options.details', { slug: quizQuestion?.quiz?.slug, quizQuestionSlug: quizQuestion.slug, quizQuestionOptionSlug: item.slug })"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs border border-blue-500 text-blue-500 rounded hover:bg-blue-50">
                                    <FontAwesomeIcon icon="info" />
                                    {{ t("common.actions.details") }}
                                </a>
                            </td>

                        </tr>
                    </tbody>

                </table>
            </div>

            <div class="flex justify-center mt-4">
                <a :href="route('back-office.quizzes.quiz-questions.quiz-question-options.index', { slug: quizQuestion?.quiz?.slug, quizQuestionSlug: quizQuestion.slug })"
                    class="inline-flex items-center justify-center px-4 py-2 border border-gray-400 text-gray-600 rounded hover:bg-gray-100">
                    {{ t("common.messages.showAll") }}
                </a>
            </div>

        </div>

        <div v-else>
            <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-3 rounded">
                {{ t("common.labels.noRecordsFound") }}
            </div>
        </div>

    </div>
</template>
