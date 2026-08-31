<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { router as inertia } from '@inertiajs/vue3'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faUser,
    faGauge,
    faUserGear,
    faRightFromBracket,
    faXmark,
    faSpinner,
    faChevronDown
} from '@fortawesome/free-solid-svg-icons'

library.add(
    faUser,
    faGauge,
    faUserGear,
    faRightFromBracket,
    faXmark,
    faSpinner,
    faChevronDown
)

const { authUser } = defineProps({
    authUser: {
        type: Object,
        required: true
    }
})

const dropdownRef = ref(null)

const showDropdown = ref(false)
const showLogoutModal = ref(false)
const loggingOut = ref(false)

const closeDropdown = () => {
    showDropdown.value = false
}

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeDropdown()
    }
}

const openLogoutModal = () => {
    showLogoutModal.value = true
    closeDropdown()
}

const closeLogoutModal = () => {
    if (loggingOut.value) return

    showLogoutModal.value = false
}

const logoutHandler = () => {
    if (loggingOut.value) return

    loggingOut.value = true

    inertia.post(route('logout'), {}, {
        onFinish: () => {
            loggingOut.value = false
            showLogoutModal.value = false
        }
    })
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <button type="button" @click.stop="showDropdown = !showDropdown"
            class="flex items-center gap-1 hover:text-gray-300" aria-label="User menu">
            <FontAwesomeIcon icon="user" />
        </button>

        <Transition enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95 translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1">
            <div v-if="showDropdown"
                class="absolute right-0 mt-2 bg-white text-black shadow-md border border-gray-200 rounded-xl w-44 z-[999] origin-top-right overflow-hidden">
                <a @click="closeDropdown" :href="route('auth-user.dashboard.index')"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                    <FontAwesomeIcon icon="gauge" class="text-gray-500" />
                    <span>Dashboard</span>
                </a>

                <a @click="closeDropdown" :href="route('auth-user.profile.index')"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                    <FontAwesomeIcon icon="user" class="text-gray-500" />
                    <span>Profile</span>
                </a>

                <a @click="closeDropdown" :href="route('auth-user.account.index')"
                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                    <FontAwesomeIcon icon="user-gear" class="text-gray-500" />
                    <span>Account</span>
                </a>

                <button type="button" @click="openLogoutModal"
                    class="flex items-center gap-2 w-full text-left px-3 py-2 text-red-500 hover:bg-gray-100">
                    <FontAwesomeIcon icon="right-from-bracket" />
                    <span>Logout</span>
                </button>
            </div>
        </Transition>
    </div>

    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="authUser && showLogoutModal"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeLogoutModal">
                <Transition enter-active-class="transition transform duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition transform duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2">
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
                            <button type="button" @click="closeLogoutModal" :disabled="loggingOut"
                                class="flex items-center gap-1 px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-70 disabled:cursor-not-allowed">
                                <FontAwesomeIcon icon="xmark" />
                                <span>Cancel</span>
                            </button>

                            <button type="button" @click="logoutHandler" :disabled="loggingOut"
                                class="flex items-center gap-1 px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 disabled:opacity-70 disabled:cursor-not-allowed">
                                <FontAwesomeIcon v-if="!loggingOut" icon="right-from-bracket" />

                                <FontAwesomeIcon v-else icon="spinner" spin />

                                <span>Logout</span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
