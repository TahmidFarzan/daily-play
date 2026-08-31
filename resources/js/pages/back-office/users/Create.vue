<script setup>
import Layout from "@/pages/layouts/AuthLayout.vue";
import InfiniteScrollApiSelect from "@/components/common/multi-select/InfiniteScrollApiSelect.vue";
import ApiGroupedSelect from "@/components/common/multi-select/ApiGroupedSelect.vue";

import { computed, onMounted, nextTick, inject } from "vue";
import { Head, useForm, router as intertiaJsRoute } from "@inertiajs/vue3";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import {
    faSave,
    faEye,
    faEyeSlash,
    faSpinner,
} from "@fortawesome/free-solid-svg-icons";

import { VueTelInput } from "vue-tel-input";

import { formatDate } from "@/composables/useDateTime";
import {
    showPassword,
    showConfirmPassword,
    togglePasswordVisibility,
    toggleConfirmPasswordVisibility,
} from "@/composables/usePassword";

import "vue-tel-input/vue-tel-input.css";

import { fetchFromApi } from "@/composables/useApiClient";

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner);

defineOptions({ layout: Layout });

const authUser = inject("authUser");

const { user } = defineProps({
    user: Object,
});

const isUpdate = computed(() => !!user?.slug);

const pageTitle = computed(() => {
    return isUpdate.value ? `${user?.name} Edit` : "Create User";
});

const saveForm = useForm({
    name: user?.name || "",
    email: user?.email || "",
    gender: user?.gender || null,
    religion: user?.religion || null,
    birth_date: user?.birth_date ? formatDate(user?.birth_date, "Y-m-d") : null,
    marital_status: user?.marital_status || null,
    mobile: user?.mobile || "",
    address: user?.address || "",
    user_permission_ids: user?.user_permissions?.map((item) => item.id) || [],
    is_super_admin: user?.is_super_admin || false,

    password: "",
    password_confirmation: "",
    change_password: false,
    set_as_verify_email: false,
    send_verify_email: false,
});

function validateForm() {
    saveForm.clearErrors();

    let valid = true;

    if (!saveForm.name) {
        saveForm.setError("name", "Name is required");
        valid = false;
    }

    if (!saveForm.email) {
        saveForm.setError("email", "Email is required");
        valid = false;
    }

    if (!saveForm.gender) {
        saveForm.setError("gender", "Gender is required");
        valid = false;
    }

    if (!saveForm.marital_status) {
        saveForm.setError("marital_status", "Marital status is required");
        valid = false;
    }

    if (!saveForm.religion) {
        saveForm.setError("religion", "Religion is required");
        valid = false;
    }

    if (!saveForm.user_permission_ids && !saveForm?.is_super_admin) {
        saveForm.setError("user_permission_ids", "User permission is required");
        valid = false;
    }

    if (saveForm.change_password) {
        if (!saveForm.password) {
            saveForm.setError("password", "Password is required");
            valid = false;
        }

        if (!saveForm.password_confirmation) {
            saveForm.setError(
                "password_confirmation",
                "Password confirmation is required",
            );
            valid = false;
        }
    }

    return valid;
}

function handleSave() {
    if (saveForm.processing) return;

    if (!validateForm()) return;

    saveForm.processing = true;

    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            saveForm.reset();
            saveForm.clearErrors();
        },
        onError: (errors) => {
            saveForm.clearErrors();
            saveForm.setError(errors);
        },
        onFinish: () => {
            saveForm.processing = false;
        },
    };

    if (isUpdate.value) {
        intertiaJsRoute.post(
            route("back-office.users.update", { slug: user?.slug }),
            { ...saveForm.data(), _method: "patch" },
            requestConfig,
        );
    } else {
        saveForm.post(route("back-office.users.save"), requestConfig);
    }
}

onMounted(async () => {
    await nextTick();

    window.dispatchEvent(
        new CustomEvent("set-breadcrumb", {
            detail: [
                { text: "Users", href: route("back-office.users.index") },
                { text: pageTitle.value, active: true },
            ],
        }),
    );
});
</script>

<template>
    <Head :title="pageTitle" />

    <div class="w-full">
        <div
            class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6"
        >
            <form @submit.prevent="handleSave" class="space-y-6 mt-3">
                <ul class="list-disc pl-5">
                    <li class="text-base font-semibold text-blue-700">
                        Only super admins can create and manage users.
                    </li>
                </ul>
                <div
                    class="bg-white border rounded-xl p-5 shadow-sm space-y-4 mt-3"
                >
                    <h3 class="text-base font-semibold">Basic Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Name <span class="text-red-500">*</span>
                            </label>

                            <input
                                v-model="saveForm.name"
                                placeholder="Enter name"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="
                                    saveForm.errors.name
                                        ? 'border-red-500'
                                        : 'border-gray-300'
                                "
                            />

                            <p
                                v-if="saveForm.errors.name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ saveForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Email <span class="text-red-500">*</span>
                            </label>

                            <input
                                v-model="saveForm.email"
                                type="email"
                                placeholder="Enter email address"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="
                                    saveForm.errors.email
                                        ? 'border-red-500'
                                        : 'border-gray-300'
                                "
                            />

                            <p
                                v-if="saveForm.errors.email"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ saveForm.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Birth Date
                            </label>

                            <input
                                type="date"
                                v-model="saveForm.birth_date"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none border-gray-300"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Gender <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect
                                :form="saveForm"
                                fieldName="gender"
                                :selectedItem="saveForm.gender"
                                :apiUrl="route('search.genders')"
                                :multiple="false"
                                placeholder="Select"
                                :error="saveForm.errors.gender"
                            />

                            <p
                                v-if="saveForm.errors.gender"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ saveForm.errors.gender }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Religion <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect
                                :form="saveForm"
                                fieldName="religion"
                                :selectedItem="saveForm.religion"
                                :apiUrl="route('search.religions')"
                                :multiple="false"
                                placeholder="Select"
                                :error="saveForm.errors.religion"
                            />

                            <p
                                v-if="saveForm.errors.religion"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ saveForm.errors.religion }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Marital Status
                                <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect
                                :form="saveForm"
                                fieldName="marital_status"
                                :selectedItem="saveForm.marital_status"
                                :apiUrl="route('search.marital-statuses')"
                                :multiple="false"
                                placeholder="Select"
                                :error="saveForm.errors.marital_status"
                            />

                            <p
                                v-if="saveForm.errors.marital_status"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ saveForm.errors.marital_status }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Mobile
                            </label>

                            <VueTelInput
                                v-model="saveForm.mobile"
                                class="w-full border rounded-md px-2 py-1"
                                :class="
                                    saveForm.errors.mobile
                                        ? 'border-red-500'
                                        : 'border-gray-300'
                                "
                            />

                            <p
                                v-if="saveForm.errors.mobile"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ saveForm.errors.mobile }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Address
                            </label>

                            <textarea
                                v-model="saveForm.address"
                                rows="3"
                                placeholder="Enter address"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none border-gray-300"
                            ></textarea>
                        </div>

                        <div v-if="authUser?.is_super_admin">
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Is Super Admin
                            </label>

                            <label
                                class="inline-flex cursor-pointer items-center gap-3"
                            >
                                <input
                                    v-model="saveForm.is_super_admin"
                                    type="checkbox"
                                    class="peer sr-only"
                                    :checked="saveForm.is_super_admin"
                                />

                                <span
                                    class="relative h-7 w-14 rounded-full bg-gray-300 transition after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-600 peer-checked:after:translate-x-7"
                                >
                                </span>

                                <span class="text-sm text-gray-600">
                                    {{ saveForm.is_super_admin ? "Yes" : "No" }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div v-if="!saveForm.is_super_admin">
                        <ApiGroupedSelect
                            :selectedItem="saveForm.user_permission_ids"
                            fieldName="user_permission_ids"
                            :form="saveForm"
                            :apiUrl="route('search.user-permissions-by-group')"
                            apiLabelKey="access"
                            apiValueKey="id"
                            :isRequired="!saveForm?.is_super_admin"
                            defaultLabel="User Permission"
                        />

                        <p
                            v-if="saveForm.errors.user_permission_ids"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ saveForm.errors.user_permission_ids }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2 flex gap-6 items-center pt-2">
                            <label class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    v-model="saveForm.set_as_verify_email"
                                />
                                <span class="text-sm"
                                    >Set as verified email</span
                                >
                            </label>

                            <label class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    v-model="saveForm.send_verify_email"
                                />
                                <span class="text-sm"
                                    >Send verification email</span
                                >
                            </label>

                            <label class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    v-model="saveForm.change_password"
                                />
                                <span class="text-sm">Change password</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div
                    v-if="saveForm.change_password"
                    class="bg-white border rounded-xl p-5 shadow-sm space-y-4"
                >
                    <h3 class="text-base font-semibold">Change Password</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                New Password <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="saveForm.password"
                                    placeholder="Enter new password"
                                    class="w-full border rounded-md px-3 py-2 text-sm pr-10 focus:ring-2 focus:ring-blue-500 focus:outline-none border-gray-300"
                                />

                                <button
                                    type="button"
                                    @click="togglePasswordVisibility"
                                    class="absolute right-2 top-2"
                                    :title="
                                        showPassword
                                            ? 'Hide password'
                                            : 'Show password'
                                    "
                                >
                                    <FontAwesomeIcon
                                        :icon="
                                            showPassword ? 'eye-slash' : 'eye'
                                        "
                                    />
                                </button>
                            </div>

                            <p
                                v-if="saveForm.errors.password"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ saveForm.errors.password }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Confirm Password
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <input
                                    :type="
                                        showConfirmPassword
                                            ? 'text'
                                            : 'password'
                                    "
                                    v-model="saveForm.password_confirmation"
                                    placeholder="Confirm password"
                                    class="w-full border rounded-md px-3 py-2 text-sm pr-10 focus:ring-2 focus:ring-blue-500 focus:outline-none border-gray-300"
                                />

                                <button
                                    type="button"
                                    @click="toggleConfirmPasswordVisibility"
                                    class="absolute right-2 top-2"
                                    :title="
                                        showConfirmPassword
                                            ? 'Hide confirm password'
                                            : 'Show confirm password'
                                    "
                                >
                                    <FontAwesomeIcon
                                        :icon="
                                            showConfirmPassword
                                                ? 'eye-slash'
                                                : 'eye'
                                        "
                                    />
                                </button>
                            </div>

                            <p
                                v-if="saveForm.errors.password_confirmation"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ saveForm.errors.password_confirmation }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center">
                    <button
                        type="submit"
                        :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <FontAwesomeIcon
                            v-if="saveForm.processing"
                            icon="spinner"
                            spin
                        />
                        <FontAwesomeIcon v-else icon="save" />
                        {{ saveForm.processing ? "Saving..." : "Save" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
