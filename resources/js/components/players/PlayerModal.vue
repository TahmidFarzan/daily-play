<script setup>
import { ref } from 'vue'

import apiClient from '@/config/axios'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCircleCheck, faUser } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faCircleCheck, faUser)

const emit = defineEmits(['saved'])

const name = ref('')
const email = ref('')
const mobile = ref('')
const address = ref('')

const submitting = ref(false)
const fieldErrors = ref({})
const generalError = ref('')

const hasIdentity = () => email.value.trim() !== '' || mobile.value.trim() !== ''

const validate = () => {
    const errors = {}

    if (!name.value.trim()) {
        errors.name = 'Name is required.'
    }

    if (!hasIdentity()) {
        errors.email = 'Either email or mobile is required.'
        errors.mobile = 'Either email or mobile is required.'
    }

    fieldErrors.value = errors

    return Object.keys(errors).length === 0
}

const submit = async () => {
    if (submitting.value) return

    generalError.value = ''
    fieldErrors.value = {}

    if (!validate()) return

    submitting.value = true

    try {
        const response = await apiClient.post(route('players.save'), {
            name: name.value.trim(),
            email: email.value.trim() || null,
            mobile: mobile.value.trim() || null,
            address: address.value.trim() || null,
        })

        const data = response?.data

        if (data?.status === 'success' && data?.data) {
            emit('saved', data.data)
            return
        }

        generalError.value = data?.message || 'Failed to save player. Please try again.'
    } catch (error) {
        const status = error?.response?.status
        const responseData = error?.response?.data

        if (status === 422 && responseData?.errors) {
            fieldErrors.value = responseData.errors
        } else if (responseData?.message) {
            generalError.value = responseData.message
        } else {
            generalError.value = 'Failed to save player. Please try again.'
        }
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-[rgb(15_23_42_/_0.45)] p-5"
            role="presentation"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="daily-play-player-title"
                class="w-full max-w-md rounded-2xl border border-[var(--daily-play-border)] bg-[var(--daily-play-surface)] p-7 shadow-[var(--daily-play-shadow-lg)]"
            >
                <div class="flex flex-col items-center text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[var(--daily-play-accent-soft)]">
                        <FontAwesomeIcon
                            icon="user"
                            class="text-2xl text-[var(--daily-play-accent-active)]"
                        />
                    </div>

                    <h2
                        id="daily-play-player-title"
                        class="mt-4 text-2xl font-bold tracking-tight text-[var(--daily-play-text)]"
                    >
                        Tell us about you
                    </h2>

                    <p class="mt-1.5 text-sm text-[var(--daily-play-text-muted)]">
                        Enter your details to start playing today's puzzle.
                    </p>
                </div>

                <form
                    class="mt-6 space-y-4"
                    novalidate
                    @submit.prevent="submit"
                >
                    <div>
                        <label
                            for="daily-play-player-name"
                            class="mb-1.5 block text-sm font-medium text-[var(--daily-play-text)]"
                        >
                            Name *
                        </label>
                        <input
                            id="daily-play-player-name"
                            v-model="name"
                            type="text"
                            autocomplete="name"
                            autofocus
                            class="w-full rounded-xl border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-4 py-2.5 text-sm text-[var(--daily-play-text)] placeholder:text-[var(--daily-play-text-muted)] focus:border-[var(--daily-play-accent)] focus:outline-none"
                            :class="fieldErrors.name ? 'border-[var(--daily-play-danger)]' : ''"
                        />
                        <p
                            v-if="fieldErrors.name"
                            class="mt-1.5 text-xs font-medium text-[var(--daily-play-danger)]"
                        >
                            {{ fieldErrors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="daily-play-player-email"
                            class="mb-1.5 block text-sm font-medium text-[var(--daily-play-text)]"
                        >
                            Email
                        </label>
                        <input
                            id="daily-play-player-email"
                            v-model="email"
                            type="email"
                            autocomplete="email"
                            inputmode="email"
                            class="w-full rounded-xl border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-4 py-2.5 text-sm text-[var(--daily-play-text)] placeholder:text-[var(--daily-play-text-muted)] focus:border-[var(--daily-play-accent)] focus:outline-none"
                            :class="fieldErrors.email ? 'border-[var(--daily-play-danger)]' : ''"
                        />
                        <p
                            v-if="fieldErrors.email"
                            class="mt-1.5 text-xs font-medium text-[var(--daily-play-danger)]"
                        >
                            {{ fieldErrors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="daily-play-player-mobile"
                            class="mb-1.5 block text-sm font-medium text-[var(--daily-play-text)]"
                        >
                            Mobile
                        </label>
                        <input
                            id="daily-play-player-mobile"
                            v-model="mobile"
                            type="tel"
                            autocomplete="tel"
                            inputmode="tel"
                            class="w-full rounded-xl border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-4 py-2.5 text-sm text-[var(--daily-play-text)] placeholder:text-[var(--daily-play-text-muted)] focus:border-[var(--daily-play-accent)] focus:outline-none"
                            :class="fieldErrors.mobile ? 'border-[var(--daily-play-danger)]' : ''"
                        />
                        <p
                            v-if="fieldErrors.mobile"
                            class="mt-1.5 text-xs font-medium text-[var(--daily-play-danger)]"
                        >
                            {{ fieldErrors.mobile }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="daily-play-player-address"
                            class="mb-1.5 block text-sm font-medium text-[var(--daily-play-text)]"
                        >
                            Address
                        </label>
                        <textarea
                            id="daily-play-player-address"
                            v-model="address"
                            rows="2"
                            autocomplete="street-address"
                            class="w-full resize-none rounded-xl border border-[var(--daily-play-border)] bg-[var(--daily-play-background)] px-4 py-2.5 text-sm text-[var(--daily-play-text)] placeholder:text-[var(--daily-play-text-muted)] focus:border-[var(--daily-play-accent)] focus:outline-none"
                        />
                    </div>

                    <p
                        v-if="generalError"
                        class="rounded-xl bg-[var(--daily-play-background)] px-4 py-2.5 text-sm font-medium text-[var(--daily-play-danger)]"
                    >
                        {{ generalError }}
                    </p>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[var(--daily-play-accent)] px-4 py-3 text-sm font-semibold text-[var(--daily-play-text-inverse)] transition hover:bg-[var(--daily-play-accent-hover)] active:bg-[var(--daily-play-accent-active)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--daily-play-accent)] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="submitting"
                    >
                        <FontAwesomeIcon
                            icon="circle-check"
                            class="text-base"
                        />
                        {{ submitting ? 'Saving…' : 'Start Game' }}
                    </button>
                </form>
            </div>
        </div>
    </Teleport>
</template>