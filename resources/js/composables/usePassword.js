import { ref } from 'vue'

export const showPassword = ref(false)
export const showConfirmPassword = ref(false)
export const showCurrentPassword = ref(false)

export const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value
}

export const toggleConfirmPasswordVisibility = () => {
    showConfirmPassword.value = !showConfirmPassword.value
}

export const toggleCurrentPasswordVisibility = () => {
    showCurrentPassword.value = !showCurrentPassword.value
}
