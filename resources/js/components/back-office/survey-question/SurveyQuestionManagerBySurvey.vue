<script setup>
import { ref, watch, inject, nextTick } from "vue";
import { useForm, router as inertiaJsRoute } from "@inertiajs/vue3";
import { useTranslate } from "@/composables/useTranslate";
import { VueDraggable } from "vue-draggable-plus";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import {
    faPlus,
    faTrash,
    faGripVertical,
    faPencil,
    faList,
    faTimes,
    faSave,
    faSpinner,
    faChevronDown,
    faChevronRight,
} from "@fortawesome/free-solid-svg-icons";
import {
    canAccessSurveyQuestion,
    canCreateSurveyQuestion,
    canUpdateSurveyQuestion,
    canDeleteSurveyQuestion,
} from "@/composables/useUserPermissions";

FontAwesomeLibrary.add(
    faPlus,
    faTrash,
    faGripVertical,
    faPencil,
    faList,
    faTimes,
    faSave,
    faSpinner,
    faChevronDown,
    faChevronRight
);

const { survey, isUpdate, surveySaveForm } = defineProps({
    survey: {
        type: Object,
        default: null,
    },
    isUpdate: {
        type: Boolean,
        default: false,
    },
    surveySaveForm: {
        type: Object,
        default: null,
    },
});

const { t } = useTranslate();
const authUser = inject("authUser");

const canAccessQuestion = () => canAccessSurveyQuestion(authUser?.value);
const canCreateQuestion = () => canCreateSurveyQuestion(authUser?.value);
const canUpdateQuestion = (surveyQuestion) =>
    canUpdateSurveyQuestion(authUser?.value, surveyQuestion);
const canDeleteQuestion = (surveyQuestion) =>
    canDeleteSurveyQuestion(authUser?.value, surveyQuestion);

const createEmptyQuestion = (position = 1) => ({
    question: "",
    position,
});

function reindexPositions(items) {
    items.forEach((item, index) => {
        item.position = index + 1;
    });
}

const openCreateQuestionIndex = ref(0);

function toggleCreateQuestion(index) {
    openCreateQuestionIndex.value =
        openCreateQuestionIndex.value === index ? null : index;
}

function isCreateQuestionOpen(index) {
    return openCreateQuestionIndex.value === index;
}

function addQuestion() {
    if (!surveySaveForm) return;
    const nextPos = surveySaveForm.questions.length + 1;
    surveySaveForm.questions.push(createEmptyQuestion(nextPos));
    openCreateQuestionIndex.value = surveySaveForm.questions.length - 1;
}

function removeQuestion(index) {
    if (!surveySaveForm || surveySaveForm.questions.length <= 1) return;
    surveySaveForm.questions.splice(index, 1);
    reindexPositions(surveySaveForm.questions);
    if (openCreateQuestionIndex.value === index) {
        openCreateQuestionIndex.value = 0;
    } else if (
        openCreateQuestionIndex.value !== null &&
        openCreateQuestionIndex.value > index
    ) {
        openCreateQuestionIndex.value -= 1;
    }
}

function onCreateQuestionDragEnd() {
    if (!surveySaveForm) return;
    reindexPositions(surveySaveForm.questions);
}

const questions = ref([]);
const reorderProcessing = ref(false);
const openQuestionSlug = ref(null);

watch(
    () => survey?.survey_questions,
    (val) => {
        questions.value = val ? val.map((q) => ({ ...q })) : [];
        if (questions.value.length && !openQuestionSlug.value) {
            openQuestionSlug.value = questions.value[0]?.slug || null;
        }
    },
    { immediate: true, deep: true }
);

function toggleQuestion(slug) {
    openQuestionSlug.value = openQuestionSlug.value === slug ? null : slug;
}

function isQuestionOpen(slug) {
    return openQuestionSlug.value === slug;
}

function reindexQuestionPositions() {
    questions.value.forEach((q, index) => {
        q.position = index + 1;
    });
}

async function submitQuestionReorder() {
    if (reorderProcessing.value || !survey?.slug) return;

    reorderProcessing.value = true;

    inertiaJsRoute.post(
        route("back-office.surveys.survey-questions.reorder", {
            slug: survey?.slug,
        }),
        {
            questions: questions.value.map((q) => ({
                slug: q?.slug,
                position: q?.position,
            })),
            redirect_back_to_same_page: true,
            _method: "patch",
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                reorderProcessing.value = false;
            },
            onError: () => {
                reorderProcessing.value = false;
            },
            onFinish: () => {
                reorderProcessing.value = false;
            },
        }
    );
}

async function onQuestionDragEnd() {
    if (reorderProcessing.value) return;
    await nextTick();
    reindexQuestionPositions();
    await submitQuestionReorder();
}

function onQuestionManualPositionChange(changedSlug) {
    const list = [...questions.value];
    list.sort((a, b) => {
        const posA = Number(a.position) || 9999;
        const posB = Number(b.position) || 9999;
        if (posA === posB) {
            if (a.slug === changedSlug) return -1;
            if (b.slug === changedSlug) return 1;
        }
        return posA - posB;
    });
    questions.value = list;
    reindexQuestionPositions();
    submitQuestionReorder();
}

const showQuestionModal = ref(false);
const editingQuestion = ref(null);

const saveSurveyQuestionForm = useForm({
    question: "",
    position: null,
    redirect_back_to_same_page: true,
});

function openAddQuestionModal() {
    editingQuestion.value = null;
    saveSurveyQuestionForm.reset();
    saveSurveyQuestionForm.clearErrors();
    saveSurveyQuestionForm.question = "";
    saveSurveyQuestionForm.position = questions.value.length + 1;
    showQuestionModal.value = true;
}

function openEditQuestionModal(q) {
    editingQuestion.value = q;
    saveSurveyQuestionForm.reset();
    saveSurveyQuestionForm.clearErrors();
    saveSurveyQuestionForm.question = q.question ?? "";
    saveSurveyQuestionForm.position = q.position ?? null;
    showQuestionModal.value = true;
}

function closeQuestionModal() {
    if (saveSurveyQuestionForm.processing) return;
    showQuestionModal.value = false;
    editingQuestion.value = null;
    saveSurveyQuestionForm.reset();
    saveSurveyQuestionForm.clearErrors();
}

function validateSurveyQuestionForm() {
    saveSurveyQuestionForm.clearErrors();
    let valid = true;

    if (!saveSurveyQuestionForm.question?.trim()) {
        saveSurveyQuestionForm.setError(
            "question",
            t("form-requests.survey.question.required") || "Question is required"
        );
        valid = false;
    }

    return valid;
}

function handleSurveyQuestionSave() {
    if (saveSurveyQuestionForm.processing) return;
    if (!validateSurveyQuestionForm()) return;

    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            saveSurveyQuestionForm.reset();
            saveSurveyQuestionForm.clearErrors();
            closeQuestionModal();
        },
        onError: (errors) => {
            saveSurveyQuestionForm.clearErrors();
            saveSurveyQuestionForm.setError(errors);
        },
    };

    if (editingQuestion.value?.slug) {
        inertiaJsRoute.post(
            route("back-office.surveys.survey-questions.update", {
                slug: survey?.slug,
                surveyQuestionSlug: editingQuestion.value.slug,
            }),
            { ...saveSurveyQuestionForm.data(), _method: "patch" },
            requestConfig
        );
    } else {
        saveSurveyQuestionForm.post(
            route("back-office.surveys.survey-questions.save", {
                slug: survey?.slug,
            }),
            requestConfig
        );
    }
}

const showDeleteModal = ref(false);
const deletingRow = ref(null);
const deleteProcessing = ref(false);

function openDeleteModal(q) {
    deletingRow.value = q;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    if (deleteProcessing.value) return;
    showDeleteModal.value = false;
    deletingRow.value = null;
}

function handleSurveyQuestionDelete(surveyQuestion) {
    if (!surveyQuestion || deleteProcessing.value) return;

    deleteProcessing.value = true;

    inertiaJsRoute.delete(
        route("back-office.surveys.survey-questions.delete", {
            slug: survey?.slug,
            surveyQuestionSlug: surveyQuestion?.slug,
        }),
        {
            preserveScroll: true,
            onFinish: () => {
                showDeleteModal.value = false;
                deletingRow.value = null;
                deleteProcessing.value = false;
            },
        }
    );
}
</script>

<template>
    <div v-if="!isUpdate && surveySaveForm" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold">
                {{ t("common.labels.questions") || "Questions" }}
                <span class="text-red-500">*</span>
            </h3>
            <button type="button" @click="addQuestion"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm flex items-center gap-2">
                <FontAwesomeIcon icon="plus" />
                {{ t("common.actions.add") || "Add Question" }}
            </button>
        </div>

        <p v-if="surveySaveForm.errors.questions" class="text-red-500 text-sm">
            {{ surveySaveForm.errors.questions }}
        </p>

        <VueDraggable v-model="surveySaveForm.questions" :animation="200" handle=".question-drag-handle"
            @end="onCreateQuestionDragEnd" class="space-y-3">
            <div v-for="(question, qIndex) in surveySaveForm.questions" :key="qIndex"
                class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 cursor-pointer select-none"
                    @click="toggleCreateQuestion(qIndex)">
                    <button type="button"
                        class="question-drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600"
                        @click.stop>
                        <FontAwesomeIcon icon="grip-vertical" />
                    </button>

                    <FontAwesomeIcon :icon="isCreateQuestionOpen(qIndex)
                            ? 'chevron-down'
                            : 'chevron-right'
                        " class="text-gray-500 text-sm w-3" />

                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-800">
                            #{{ qIndex + 1 }} —
                            {{
                                question.question ||
                                t("common.labels.question") ||
                                "Question"
                            }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2" @click.stop>
                        <input v-model.number="question.position" type="number" min="1"
                            class="w-16 border border-gray-300 rounded-md px-2 py-1 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                        <button v-if="surveySaveForm.questions.length > 1" type="button" @click="removeQuestion(qIndex)"
                            class="text-red-500 hover:text-red-700 p-1">
                            <FontAwesomeIcon icon="trash" />
                        </button>
                    </div>
                </div>

                <div v-show="isCreateQuestionOpen(qIndex)" class="p-4 border-t border-gray-200 bg-white space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t("common.labels.question") || "Question" }}
                            <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="question.question" rows="2"
                            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :class="surveySaveForm.errors[`questions.${qIndex}.question`]
                                    ? 'border-red-500'
                                    : 'border-gray-300'
                                " :placeholder="t('common.placeholders.question') || 'Enter question'
                                "></textarea>
                        <p v-if="surveySaveForm.errors[`questions.${qIndex}.question`]"
                            class="text-red-500 text-sm mt-1">
                            {{ surveySaveForm.errors[`questions.${qIndex}.question`] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t("common.labels.position") || "Position" }}
                        </label>
                        <input v-model.number="question.position" type="number" min="1"
                            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :class="surveySaveForm.errors[`questions.${qIndex}.position`]
                                    ? 'border-red-500'
                                    : 'border-gray-300'
                                " />
                        <p v-if="surveySaveForm.errors[`questions.${qIndex}.position`]"
                            class="text-red-500 text-sm mt-1">
                            {{ surveySaveForm.errors[`questions.${qIndex}.position`] }}
                        </p>
                    </div>
                </div>
            </div>
        </VueDraggable>
    </div>

    <div v-else-if="isUpdate" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold">
                {{ t("common.labels.questions") || "Questions" }}
            </h3>
            <button v-if="canCreateQuestion()" type="button" @click="openAddQuestionModal"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm flex items-center gap-2">
                <FontAwesomeIcon icon="plus" />
                {{ t("common.actions.add") || "Add Question" }}
            </button>
        </div>

        <VueDraggable v-model="questions" :animation="150" handle=".drag-handle" :disabled="reorderProcessing"
            @end="onQuestionDragEnd" class="space-y-3">
            <div v-for="(q, qIndex) in questions" :key="q.slug || q.id"
                class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 cursor-pointer select-none"
                    @click="toggleQuestion(q.slug)">
                    <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600"
                        :class="{
                            'opacity-50 pointer-events-none': reorderProcessing,
                        }" @click.stop>
                        <FontAwesomeIcon icon="grip-vertical" />
                    </span>

                    <FontAwesomeIcon :icon="isQuestionOpen(q.slug) ? 'chevron-down' : 'chevron-right'
                        " class="text-gray-500 text-sm w-3" />

                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-800">
                            #{{ qIndex + 1 }} —
                            {{ q.question || t("common.labels.question") }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2" @click.stop>
                        <input v-model.number="q.position" type="number" min="1"
                            class="w-16 border border-gray-300 rounded-md px-2 py-1 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :disabled="reorderProcessing" @change="onQuestionManualPositionChange(q.slug)" />
                        <button v-if="canUpdateQuestion(q)" type="button" @click="openEditQuestionModal(q)"
                            class="text-blue-600 hover:text-blue-800 p-1">
                            <FontAwesomeIcon icon="pencil" />
                        </button>
                        <button v-if="canDeleteQuestion(q)" type="button" @click="openDeleteModal(q)"
                            class="text-red-500 hover:text-red-700 p-1">
                            <FontAwesomeIcon icon="trash" />
                        </button>
                    </div>
                </div>

                <div v-show="isQuestionOpen(q.slug)" class="p-4 border-t border-gray-200 bg-white">
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">
                        {{ q.question }}
                    </p>
                </div>
            </div>
        </VueDraggable>

        <div class="space-y-3 pt-2">
            <div class="flex justify-start">
                <a v-if="canAccessQuestion()" :href="route('back-office.surveys.survey-questions.index', {
                    slug: survey?.slug,
                })
                    "
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="list" />
                    {{ t("common.messages.questions") || "Questions" }}
                </a>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showQuestionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="closeQuestionModal">
                <div
                    class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">
                            {{
                                editingQuestion
                                    ? t("common.actions.edit")
                                    : t("common.actions.add")
                            }}
                            {{ t("common.labels.question") || "Question" }}
                        </h3>
                        <button type="button" @click="closeQuestionModal" class="text-gray-400 hover:text-gray-600"
                            :disabled="saveSurveyQuestionForm.processing">
                            <FontAwesomeIcon icon="times" />
                        </button>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t("common.labels.question") || "Question" }}
                            <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="saveSurveyQuestionForm.question" rows="3"
                            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :class="saveSurveyQuestionForm.errors.question
                                    ? 'border-red-500'
                                    : 'border-gray-300'
                                " :disabled="saveSurveyQuestionForm.processing" :placeholder="t('common.placeholders.question') || 'Enter question'
                                "></textarea>
                        <p v-if="saveSurveyQuestionForm.errors.question" class="text-red-500 text-sm mt-1">
                            {{ saveSurveyQuestionForm.errors.question }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            {{ t("common.labels.position") || "Position" }}
                        </label>
                        <input v-model.number="saveSurveyQuestionForm.position" type="number" min="1"
                            class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :class="saveSurveyQuestionForm.errors.position
                                    ? 'border-red-500'
                                    : 'border-gray-300'
                                " :disabled="saveSurveyQuestionForm.processing" />
                        <p v-if="saveSurveyQuestionForm.errors.position" class="text-red-500 text-sm mt-1">
                            {{ saveSurveyQuestionForm.errors.position }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeQuestionModal"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50"
                            :disabled="saveSurveyQuestionForm.processing">
                            {{ t("common.actions.cancel") }}
                        </button>
                        <button type="button" @click="handleSurveyQuestionSave"
                            :disabled="saveSurveyQuestionForm.processing"
                            class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-md text-sm flex items-center gap-2">
                            <FontAwesomeIcon v-if="saveSurveyQuestionForm.processing" icon="spinner" spin />
                            <FontAwesomeIcon v-else icon="save" />
                            {{
                                saveSurveyQuestionForm.processing
                                    ? t("common.actions.saving")
                                    : t("common.actions.save")
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
                        {{ t("common.actions.delete") }}
                    </h3>
                    <p class="text-sm text-gray-600">
                        {{ t("common.messages.confirmDelete") }}
                    </p>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeDeleteModal"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50"
                            :disabled="deleteProcessing">
                            {{ t("common.actions.cancel") }}
                        </button>
                        <button type="button" @click="handleSurveyQuestionDelete(deletingRow)"
                            :disabled="deleteProcessing"
                            class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white px-4 py-2 rounded-md text-sm flex items-center gap-2">
                            <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                            <FontAwesomeIcon v-else icon="trash" />
                            {{
                                deleteProcessing
                                    ? t("common.actions.deleting")
                                    : t("common.actions.delete")
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
