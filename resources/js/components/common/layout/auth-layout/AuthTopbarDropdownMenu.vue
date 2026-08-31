<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { router as inertiaJsRoute } from "@inertiajs/vue3";

import { library } from "@fortawesome/fontawesome-svg-core";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faUser,
    faXmark,
    faUserGear,
    faChartLine,
    faRightFromBracket,
    faSpinner,
    faGears,
} from "@fortawesome/free-solid-svg-icons";

import {
    canAccessActivityLog,
    canAccessLogViewer,
    canAccessQueueMonitor,
    canAccessSetting,
} from "@/composables/useUserPermissions";

library.add(
    faUser,
    faXmark,
    faUserGear,
    faChartLine,
    faRightFromBracket,
    faSpinner,
    faGears,
);

const { authUser } = defineProps({
    authUser: {
        type: Object,
        default: null,
    },
});

const showUserDropdown = ref(false);
const dropdownRef = ref(null);
const logoutProcessing = ref(false);
const logoutShowConfirmationModal = ref(false);

const canAccessActivityLogComputed = computed(() => {
    return canAccessActivityLog(authUser);
});

const canAccessLogViewerComputed = computed(() => {
    return canAccessLogViewer(authUser);
});

const canAccessQueueMonitorComputed = computed(() => {
    return canAccessQueueMonitor(authUser);
});

const canAccessSettingComputed = computed(() => {
    return canAccessSetting(authUser);
});

const toggleDropdown = (event) => {
    event.stopPropagation();
    showUserDropdown.value = !showUserDropdown.value;
};

const closeUserDropdown = () => {
    showUserDropdown.value = false;
};

const openLogoutModal = () => {
    logoutShowConfirmationModal.value = true;
    closeUserDropdown();
};

const closeLogoutModal = () => {
    if (logoutProcessing.value) return;

    logoutShowConfirmationModal.value = false;
};

const handleLogout = () => {
    if (logoutProcessing.value) return;

    logoutProcessing.value = true;

    inertiaJsRoute.post(
        route("logout"),
        {},
        {
            onFinish: () => {
                logoutProcessing.value = false;
            },
        },
    );
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeUserDropdown();
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <button
            type="button"
            @click="toggleDropdown"
            class="flex items-center gap-2"
            aria-label="User menu"
        >
            <img
                :src="
                    authUser?.profile_image?.preview_url ||
                    '/uploads/icons/auth/user.png'
                "
                class="w-8 h-8 rounded-full object-cover"
                :alt="authUser?.name || 'User'"
            />
        </button>

        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1"
        >
            <div
                v-if="showUserDropdown"
                class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-xl shadow-md z-50 origin-top-right overflow-hidden"
            >
                <div class="px-3 py-2 border-b border-gray-100">
                    <div class="font-medium">
                        {{ authUser?.name }}
                    </div>
                </div>

                <a
                    :href="route('auth-user.profile.index')"
                    @click="closeUserDropdown"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100"
                >
                    <FontAwesomeIcon icon="user" class="text-gray-500" />
                    <span>Profile</span>
                </a>

                <a
                    :href="route('auth-user.account.index')"
                    @click="closeUserDropdown"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100"
                >
                    <FontAwesomeIcon icon="user-gear" class="text-gray-500" />
                    <span>Account</span>
                </a>

                <a
                    v-if="canAccessActivityLogComputed"
                    :href="route('back-office.activity-logs.index')"
                    @click="closeUserDropdown"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100"
                >
                    <FontAwesomeIcon icon="chart-line" class="text-gray-500" />
                    <span>Activity Logs</span>
                </a>

                <a
                    v-if="canAccessLogViewerComputed"
                    :href="route('log-viewer.index')"
                    @click="closeUserDropdown"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100"
                >
                    <FontAwesomeIcon icon="chart-line" class="text-gray-500" />
                    <span>Log Viewer</span>
                </a>

                <a
                    v-if="canAccessQueueMonitorComputed"
                    :href="route('back-office.queue-monitor.index')"
                    @click="closeUserDropdown"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100"
                >
                    <FontAwesomeIcon icon="chart-line" class="text-gray-500" />
                    <span>Queue Monitor</span>
                </a>

                <a
                    v-if="canAccessSettingComputed"
                    :href="route('back-office.settings.index')"
                    @click="closeUserDropdown"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100"
                >
                    <FontAwesomeIcon icon="gears" class="text-gray-500" />
                    <span>Settings</span>
                </a>

                <button
                    type="button"
                    @click="openLogoutModal"
                    class="flex items-center gap-2 w-full text-left px-3 py-2 text-red-500 hover:bg-gray-100"
                >
                    <FontAwesomeIcon icon="right-from-bracket" />
                    <span>Logout</span>
                </button>
            </div>
        </Transition>
    </div>

    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="authUser && logoutShowConfirmationModal"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
                @click.self="closeLogoutModal"
            >
                <Transition
                    enter-active-class="transition transform duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition transform duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div class="bg-white p-5 rounded-xl shadow-lg w-80">
                        <div class="flex items-center gap-2 mb-3 text-red-500">
                            <FontAwesomeIcon icon="right-from-bracket" />
                            <span class="font-semibold text-gray-800">
                                Confirm Logout
                            </span>
                        </div>

                        <div class="mb-4 text-gray-600">
                            Are you sure you want to logout?
                        </div>

                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                @click="closeLogoutModal"
                                :disabled="logoutProcessing"
                                class="flex items-center gap-1 px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-70 disabled:cursor-not-allowed"
                            >
                                <FontAwesomeIcon icon="xmark" />
                                <span>Cancel</span>
                            </button>

                            <button
                                type="button"
                                @click="handleLogout"
                                :disabled="logoutProcessing"
                                class="flex items-center gap-1 px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 disabled:opacity-70 disabled:cursor-not-allowed"
                            >
                                <FontAwesomeIcon
                                    v-if="!logoutProcessing"
                                    icon="right-from-bracket"
                                />

                                <FontAwesomeIcon v-else icon="spinner" spin />

                                <span>
                                    {{
                                        logoutProcessing
                                            ? "Logging out..."
                                            : "Logout"
                                    }}
                                </span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
