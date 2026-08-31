<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import OffCanvasMenuItems from '@/components/common/layout/auth-layout/OffCanvasMenuItems.vue'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faBars,
    faXmark
} from '@fortawesome/free-solid-svg-icons'

library.add(
    faBars,
    faXmark
)

const {
    authUser,
    mode = 'trigger'
} = defineProps({
    authUser: {
        type: Object,
        default: null
    },
    mode: {
        type: String,
        default: 'trigger',
        validator: (value) => ['trigger', 'sidebar'].includes(value)
    }
})

const offcanvasSidebarShow = ref(false)
const isMobile = ref(window.innerWidth < 768)

const isTriggerMode = computed(() => mode === 'trigger')
const isSidebarMode = computed(() => mode === 'sidebar')

const toggleOffcanvasSidebarShow = () => {
    offcanvasSidebarShow.value = !offcanvasSidebarShow.value
}

const closeOffcanvasSidebar = () => {
    offcanvasSidebarShow.value = false
}

const handlePageResize = () => {
    isMobile.value = window.innerWidth < 768

    if (!isMobile.value) {
        offcanvasSidebarShow.value = false
    }
}

onMounted(() => {
    window.addEventListener('resize', handlePageResize)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', handlePageResize)
})
</script>

<template>
    <template v-if="isTriggerMode">
        <button v-if="isMobile && !offcanvasSidebarShow" type="button" @click="toggleOffcanvasSidebarShow"
            class="md:hidden border border-gray-200 px-2 py-1 rounded hover:bg-gray-100" aria-label="Open sidebar menu">
            <FontAwesomeIcon icon="bars" />
        </button>

        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="offcanvasSidebarShow" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 md:hidden"
                    @click="closeOffcanvasSidebar" />
            </Transition>

            <Transition enter-active-class="transition transform duration-300 ease-out"
                enter-from-class="-translate-x-full" enter-to-class="translate-x-0"
                leave-active-class="transition transform duration-200 ease-in" leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full">
                <aside v-if="offcanvasSidebarShow"
                    class="fixed top-0 left-0 h-full w-64 bg-white z-50 p-3 md:hidden shadow-lg flex flex-col">
                    <div class="flex items-center justify-end mb-3">
                        <button type="button" @click="closeOffcanvasSidebar"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"
                            aria-label="Close sidebar menu">
                            <FontAwesomeIcon icon="xmark" />
                        </button>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto">
                        <OffCanvasMenuItems :auth-user="authUser" @navigate="closeOffcanvasSidebar" />
                    </div>
                </aside>
            </Transition>
        </Teleport>
    </template>

    <aside v-if="isSidebarMode" class="w-64 border-r border-gray-200 bg-white hidden md:block flex-shrink-0">
        <div class="p-3 sticky top-16 max-h-[calc(100vh-4rem)] overflow-y-auto">
            <OffCanvasMenuItems :auth-user="authUser" />
        </div>
    </aside>
</template>
