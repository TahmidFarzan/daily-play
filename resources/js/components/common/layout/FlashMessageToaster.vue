<script setup>
import { ref, watch } from 'vue'
import { Toaster, toast } from 'vue-sonner'

const { flashMessage } = defineProps({
    flashMessage: {
        type: Object,
        default: null,
    },
})

const lastFlashKey = ref(null)

watch(
    () => flashMessage,
    (value) => {
        if (!value?.message) return

        const flashKey = `${value.status ?? 'default'}-${value.message}-${value.timestamp ?? Date.now()}`

        if (flashKey === lastFlashKey.value) return

        lastFlashKey.value = flashKey

        switch (value.status) {
            case 'success':
                toast.success(value.message)
                break
            case 'error':
                toast.error(value.message)
                break
            case 'warning':
                toast.warning(value.message)
                break
            case 'info':
                toast.info(value.message)
                break
            default:
                toast.info(value.message)
        }
    },
    { immediate: true, deep: true }
)
</script>

<template>
    <Teleport to="body">
        <Toaster richColors position="top-right" />
    </Teleport>
</template>
