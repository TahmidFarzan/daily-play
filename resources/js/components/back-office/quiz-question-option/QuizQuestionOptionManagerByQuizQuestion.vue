<script setup>
import { ref, watch, inject, nextTick } from 'vue'
import { useForm, router as inertiaJsRoute } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'
import { quizQuestionAnswerTypes } from '@/composables/useQuiz'
import { VueDraggable } from 'vue-draggable-plus'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faPlus,
    faTrash,
    faGripVertical,
    faPencil,
    faList,
    faTimes,
    faSave,
    faSpinner,
} from '@fortawesome/free-solid-svg-icons'
import {
    canAccessQuizQuestionOption,
    canCreateQuizQuestionOption,
    canDeleteQuizQuestionOption,
    canUpdateQuizQuestionOption,
} from '@/composables/useUserPermissions'

FontAwesomeLibrary.add(
    faPlus,
    faTrash,
    faGripVertical,
    faPencil,
    faList,
    faTimes,
    faSave,
    faSpinner
)

const {
    quizQuestion,
    isUpdate,
    quizQuestionSaveForm,
} = defineProps({
    quizQuestion: {
        type: Object,
        default: null,
    },
    isUpdate: {
        type: Boolean,
        default: false,
    },
    quizQuestionSaveForm: {
        type: Object,
        default: null,
    },
})

const { t } = useTranslate()
const authUser = inject('authUser')

const canAccessQuestionOption = () => canAccessQuizQuestionOption(authUser?.value)
const canCreateQuestionOption = () => canCreateQuizQuestionOption(authUser?.value)
const canUpdateQuestionOption = (quizQuestionOption) =>
    canUpdateQuizQuestionOption(authUser?.value, quizQuestionOption)
const canDeleteQuestionOption = (quizQuestionOption) =>
    canDeleteQuizQuestionOption(authUser?.value, quizQuestionOption)

let optionIdCounter = 1
function createOption(data = {}) {
    return {
        id: `opt-${Date.now()}-${optionIdCounter++}`,
        option: data.option ?? '',
        is_correct: data.is_correct ?? false,
        position: data.position ?? null,
        redirect_back_to_same_page: true,
    }
}

function reindexPositions() {
    if (!quizQuestionSaveForm) return
    quizQuestionSaveForm.options.forEach((opt, index) => {
        opt.position = index + 1
    })
}

function addOption() {
    if (!quizQuestionSaveForm) return
    const nextPosition = quizQuestionSaveForm.options.length + 1
    quizQuestionSaveForm.options.push(createOption({ position: nextPosition }))
}

function removeOption(id) {
    if (!quizQuestionSaveForm || quizQuestionSaveForm.options.length <= 1) return
    const index = quizQuestionSaveForm.options.findIndex((o) => o.id === id)
    if (index === -1) return
    quizQuestionSaveForm.options.splice(index, 1)
    reindexPositions()
}

function onDragEnd() {
    reindexPositions()
}

function onManualPositionChange(changedId) {
    if (!quizQuestionSaveForm) return
    const options = [...quizQuestionSaveForm.options]
    options.sort((a, b) => {
        const posA = Number(a.position) || 9999
        const posB = Number(b.position) || 9999
        if (posA === posB) {
            if (a.id === changedId) return -1
            if (b.id === changedId) return 1
        }
        return posA - posB
    })
    quizQuestionSaveForm.options = options
    reindexPositions()
}

function onCorrectChange(changedId, checked) {
    if (!quizQuestionSaveForm || !checked) return
    if (quizQuestionSaveForm.answer_type === quizQuestionAnswerTypes.SINGLE) {
        quizQuestionSaveForm.options.forEach((opt) => {
            opt.is_correct = opt.id === changedId
        })
    }
}

watch(
    () => quizQuestionSaveForm?.answer_type,
    (newType) => {
        if (!quizQuestionSaveForm) return
        if (newType === quizQuestionAnswerTypes.SINGLE) {
            const firstCorrect = quizQuestionSaveForm.options.find((o) => o.is_correct)
            quizQuestionSaveForm.options.forEach((opt) => {
                opt.is_correct = firstCorrect ? opt.id === firstCorrect.id : false
            })
        }
    }
)

const questionOptions = ref([])
const reorderProcessing = ref(false)

watch(
    () => quizQuestion?.quiz_question_options,
    (val) => {
        questionOptions.value = val ? val.map((o) => ({ ...o })) : []
    },
    { immediate: true, deep: true }
)

function reindexQuestionOptionPositions() {
    questionOptions.value.forEach((opt, index) => {
        opt.position = index + 1
    })
}

async function onQuestionOptionDragEnd() {
    if (reorderProcessing.value) return

    await nextTick()
    reindexQuestionOptionPositions()

    reorderProcessing.value = true

    inertiaJsRoute.post(
        route('back-office.quizzes.quiz-questions.quiz-question-options.reorder', {
            slug: quizQuestion?.quiz?.slug,
            quizQuestionSlug: quizQuestion?.slug,
        }),
        {
            options: questionOptions.value.map((option) => ({
                slug: option?.slug,
                position: option?.position,
            })),
            redirect_back_to_same_page: true,
            _method: 'patch',
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                reorderProcessing.value = false
            },
            onError: () => {
                reorderProcessing.value = false
            },
            onFinish: () => {
                reorderProcessing.value = false
            },
        }
    )
}

function onQuestionOptionManualPositionChange(changedSlug) {
    const options = [...questionOptions.value]
    options.sort((a, b) => {
        const posA = Number(a.position) || 9999
        const posB = Number(b.position) || 9999
        if (posA === posB) {
            if (a.slug === changedSlug) return -1
            if (b.slug === changedSlug) return 1
        }
        return posA - posB
    })
    questionOptions.value = options
    reindexQuestionOptionPositions()
}

const showOptionModal = ref(false)
const editingOption = ref(null)
const saveFormSaveQuizQuestionOption = useForm({
    option: null,
    is_correct: false,
    position: null,
    redirect_back_to_same_page: true,
})

function openAddOptionModal() {
    editingOption.value = null
    saveFormSaveQuizQuestionOption.reset()
    saveFormSaveQuizQuestionOption.clearErrors()
    saveFormSaveQuizQuestionOption.option = ''
    saveFormSaveQuizQuestionOption.is_correct = false
    saveFormSaveQuizQuestionOption.position = questionOptions.value.length + 1
    showOptionModal.value = true
}

function openEditOptionModal(opt) {
    editingOption.value = opt
    saveFormSaveQuizQuestionOption.reset()
    saveFormSaveQuizQuestionOption.clearErrors()
    saveFormSaveQuizQuestionOption.option = opt.option ?? ''
    saveFormSaveQuizQuestionOption.is_correct = !!opt.is_correct
    saveFormSaveQuizQuestionOption.position = opt.position ?? null
    showOptionModal.value = true
}

function closeOptionModal() {
    if (saveFormSaveQuizQuestionOption.processing) return
    showOptionModal.value = false
    editingOption.value = null
    saveFormSaveQuizQuestionOption.reset()
    saveFormSaveQuizQuestionOption.clearErrors()
}

function validateFormSaveQuizQuestionOption() {
    saveFormSaveQuizQuestionOption.clearErrors()
    let valid = true
    if (
        !saveFormSaveQuizQuestionOption.option ||
        saveFormSaveQuizQuestionOption.option.trim() === ''
    ) {
        saveFormSaveQuizQuestionOption.setError(
            'option',
            t('common.validation.optionTextRequired')
        )
        valid = false
    }
    if (!saveFormSaveQuizQuestionOption.position) {
        saveFormSaveQuizQuestionOption.setError(
            'position',
            t('common.validation.positionIsRequired')
        )
        valid = false
    }
    return valid
}

function handleSaveQuizQuestionOption() {
    if (saveFormSaveQuizQuestionOption.processing) return
    if (!validateFormSaveQuizQuestionOption()) return
    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            saveFormSaveQuizQuestionOption.reset()
            saveFormSaveQuizQuestionOption.clearErrors()
            closeOptionModal()
        },
        onError: (errors) => {
            saveFormSaveQuizQuestionOption.clearErrors()
            saveFormSaveQuizQuestionOption.setError(errors)
        },
    }
    if (editingOption.value?.slug) {
        inertiaJsRoute.post(
            route('back-office.quizzes.quiz-questions.quiz-question-options.update', {
                slug: quizQuestion?.quiz?.slug,
                quizQuestionSlug: quizQuestion?.slug,
                quizQuestionOptionSlug: editingOption.value.slug,
            }),
            { ...saveFormSaveQuizQuestionOption.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveFormSaveQuizQuestionOption.post(
            route('back-office.quizzes.quiz-questions.quiz-question-options.save', {
                slug: quizQuestion?.quiz?.slug,
                quizQuestionSlug: quizQuestion?.slug,
            }),
            requestConfig
        )
    }
}

const showDeleteModal = ref(false)
const deletingRow = ref(null)
const deleteProcessing = ref(false)

function openDeleteModal(opt) {
    deletingRow.value = opt
    showDeleteModal.value = true
}

function closeDeleteModal() {
    if (deleteProcessing.value) return
    showDeleteModal.value = false
    deletingRow.value = null
}

const handleDelete = (quizQuestionOption) => {
    if (!quizQuestionOption || deleteProcessing.value) return
    deleteProcessing.value = true
    inertiaJsRoute.delete(
        route('back-office.quizzes.quiz-questions.quiz-question-options.delete', {
            slug: quizQuestion?.quiz?.slug,
            quizQuestionSlug: quizQuestion?.slug,
            quizQuestionOptionSlug: quizQuestionOption?.slug,
        }),
        {
            preserveScroll: true,
            onFinish: () => {
                showDeleteModal.value = false
                deletingRow.value = null
                deleteProcessing.value = false
            },
        }
    )
}
</script>

<template>
    <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold">
                {{ t('common.labels.options') }}
                <span v-if="!isUpdate" class="text-red-500">*</span>
            </h3>
            <button v-if="!isUpdate" type="button" @click="addOption"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm flex items-center gap-2">
                <FontAwesomeIcon icon="plus" />
                {{ t('common.actions.add') }}
            </button>
            <button v-else-if="canCreateQuestionOption()" type="button" @click="openAddOptionModal"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm flex items-center gap-2">
                <FontAwesomeIcon icon="plus" />
                {{ t('common.actions.add') }}
            </button>
        </div>

        <p v-if="!isUpdate && quizQuestionSaveForm?.errors?.options" class="text-red-500 text-sm">
            {{ quizQuestionSaveForm.errors.options }}
        </p>

        <div v-if="!isUpdate" class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="w-10 px-2 py-2"></th>
                        <th class="text-left px-3 py-2 font-medium">
                            {{ t('common.labels.option') }}
                        </th>
                        <th class="text-center px-3 py-2 font-medium w-24">
                            {{ t('common.labels.correct') }}
                        </th>
                        <th class="text-center px-3 py-2 font-medium w-28">
                            {{ t('common.labels.position') }}
                        </th>
                        <th class="text-center px-3 py-2 font-medium w-20">
                            {{ t('common.labels.action') }}
                        </th>
                    </tr>
                </thead>
                <VueDraggable v-model="quizQuestionSaveForm.options" tag="tbody" :animation="150" handle=".drag-handle"
                    @end="onDragEnd">
                    <tr v-for="opt in quizQuestionSaveForm.options" :key="opt.id" class="border-b hover:bg-gray-50">
                        <td class="px-2 py-2 text-center">
                            <span
                                class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600">
                                <FontAwesomeIcon icon="grip-vertical" />
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <input v-model="opt.option" type="text"
                                class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :placeholder="t('common.labels.option')" />
                        </td>
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox" :checked="opt.is_correct" @change="
                                opt.is_correct = $event.target.checked;
                            onCorrectChange(opt.id, $event.target.checked)
                                " class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        </td>
                        <td class="px-3 py-2 text-center">
                            <input v-model.number="opt.position" type="number" min="1"
                                class="w-20 border border-gray-300 rounded-md px-2 py-1.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                @change="onManualPositionChange(opt.id)" />
                        </td>
                        <td class="px-3 py-2 text-center">
                            <button type="button" @click="removeOption(opt.id)"
                                :disabled="quizQuestionSaveForm.options.length <= 1"
                                class="text-red-500 hover:text-red-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                <FontAwesomeIcon icon="trash" />
                            </button>
                        </td>
                    </tr>
                </VueDraggable>
            </table>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="w-10 px-2 py-2"></th>
                        <th class="text-left px-3 py-2 font-medium">
                            {{ t('common.labels.option') }}
                        </th>
                        <th class="text-center px-3 py-2 font-medium w-24">
                            {{ t('common.labels.correct') }}
                        </th>
                        <th class="text-center px-3 py-2 font-medium w-28">
                            {{ t('common.labels.position') }}
                        </th>
                        <th class="text-center px-3 py-2 font-medium w-28">
                            {{ t('common.labels.action') }}
                        </th>
                    </tr>
                </thead>
                <VueDraggable v-model="questionOptions" tag="tbody" :animation="150" handle=".drag-handle"
                    :disabled="reorderProcessing" @end="onQuestionOptionDragEnd">
                    <tr v-for="opt in questionOptions" :key="opt.slug || opt.id" class="border-b hover:bg-gray-50">
                        <td class="px-2 py-2 text-center">
                            <span
                                class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600"
                                :class="{
                                    'opacity-50 pointer-events-none': reorderProcessing,
                                }">
                                <FontAwesomeIcon icon="grip-vertical" />
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            {{ opt.option }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox" :checked="!!opt.is_correct" disabled
                                class="w-4 h-4 rounded border-gray-300 text-blue-600" />
                        </td>
                        <td class="px-3 py-2 text-center">
                            <input v-model.number="opt.position" type="number" min="1"
                                class="w-20 border border-gray-300 rounded-md px-2 py-1.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                @change="onQuestionOptionManualPositionChange(opt.slug)" />
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-3">
                                <button v-if="canUpdateQuestionOption(opt)" type="button"
                                    @click="openEditOptionModal(opt)" class="text-blue-600 hover:text-blue-800">
                                    <FontAwesomeIcon icon="pencil" />
                                </button>
                                <button v-if="canDeleteQuestionOption(opt)" type="button" @click="openDeleteModal(opt)"
                                    class="text-red-500 hover:text-red-700">
                                    <FontAwesomeIcon icon="trash" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </VueDraggable>
            </table>
        </div>

        <div v-if="isUpdate" class="space-y-3 pt-2">
            <p class="text-sm text-gray-600">
                {{ t('common.messages.onlyLastTenOptionsAvailableForQuickEdit') }}
            </p>
            <div class="flex justify-start">
                <a v-if="canAccessQuestionOption()" :href="route(
                    'back-office.quizzes.quiz-questions.quiz-question-options.index',
                    {
                        slug: quizQuestion?.quiz?.slug,
                        quizQuestionSlug: quizQuestion?.slug,
                    }
                )
                    "
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="list" />
                    {{ t('common.messages.questionOptions') }}
                </a>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showOptionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="closeOptionModal">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">
                            {{
                                editingOption
                                    ? t('common.actions.edit')
                                    : t('common.actions.add')
                            }}
                            {{ t('common.labels.option') }}
                        </h3>
                        <button type="button" @click="closeOptionModal" class="text-gray-400 hover:text-gray-600"
                            :disabled="saveFormSaveQuizQuestionOption.processing">
                            <FontAwesomeIcon icon="times" />
                        </button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t('common.labels.option') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input v-model="saveFormSaveQuizQuestionOption.option" type="text"
                            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :class="saveFormSaveQuizQuestionOption.errors.option
                                    ? 'border-red-500'
                                    : 'border-gray-300'
                                " :disabled="saveFormSaveQuizQuestionOption.processing"
                            :placeholder="t('common.labels.option')" />
                        <p v-if="saveFormSaveQuizQuestionOption.errors.option" class="text-red-500 text-sm mt-1">
                            {{ saveFormSaveQuizQuestionOption.errors.option }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t('common.labels.position') }}
                        </label>
                        <input v-model.number="saveFormSaveQuizQuestionOption.position" type="number" min="1"
                            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :class="saveFormSaveQuizQuestionOption.errors.position
                                    ? 'border-red-500'
                                    : 'border-gray-300'
                                " :disabled="saveFormSaveQuizQuestionOption.processing"
                            :placeholder="t('common.placeholders.position')" />
                        <p v-if="saveFormSaveQuizQuestionOption.errors.position" class="text-red-500 text-sm mt-1">
                            {{ saveFormSaveQuizQuestionOption.errors.position }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="option-is-correct" v-model="saveFormSaveQuizQuestionOption.is_correct"
                            type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            :disabled="saveFormSaveQuizQuestionOption.processing" />
                        <label for="option-is-correct" class="text-sm font-medium">
                            {{ t('common.labels.correct') }}
                        </label>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeOptionModal"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50"
                            :disabled="saveFormSaveQuizQuestionOption.processing">
                            {{ t('common.actions.cancel') }}
                        </button>
                        <button type="button" @click="handleSaveQuizQuestionOption"
                            :disabled="saveFormSaveQuizQuestionOption.processing"
                            class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-md text-sm flex items-center gap-2">
                            <FontAwesomeIcon v-if="saveFormSaveQuizQuestionOption.processing" icon="spinner" spin />
                            <FontAwesomeIcon v-else icon="save" />
                            {{
                                saveFormSaveQuizQuestionOption.processing
                                    ? t('common.actions.saving')
                                    : t('common.actions.save')
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="closeDeleteModal">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 space-y-4">
                    <h3 class="text-lg font-semibold">
                        {{ t('common.actions.delete') }}
                    </h3>
                    <p class="text-sm text-gray-600">
                        {{ t('common.messages.confirmDelete') }}
                    </p>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeDeleteModal"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50"
                            :disabled="deleteProcessing">
                            {{ t('common.actions.cancel') }}
                        </button>
                        <button type="button" @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                            class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white px-4 py-2 rounded-md text-sm flex items-center gap-2">
                            <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                            <FontAwesomeIcon v-else icon="trash" />
                            {{
                                deleteProcessing
                                    ? t('common.actions.deleting')
                                    : t('common.actions.delete')
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-active>div,
.modal-leave-active>div {
    transition: transform 0.2s ease, opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from>div,
.modal-leave-to>div {
    opacity: 0;
    transform: scale(0.95) translateY(8px);
}
</style>
