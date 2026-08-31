<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faInfo } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'

FontAwesomeLibrary.add(faInfo)

const { model, modelSlug } = defineProps({
    model: { type: Object, required: true },
    modelSlug: {
        type: String,
        required: true,
    },
})
</script>

<template>
    <div>
        <div v-if="model?.activity_logs && model?.activity_logs?.length">
            <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2">Description</th>
                            <th class="px-4 py-2">Causer</th>
                            <th class="px-4 py-2">Created At</th>
                            <th class="px-4 py-2">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(item, index) in model.activity_logs" :key="item.id"
                            class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">{{ index + 1 }}</td>
                            <td class="px-4 py-2">{{ item.description }}</td>
                            <td class="px-4 py-2">{{ item.causer ? item.causer.name : 'System' }}</td>
                            <td class="px-4 py-2">{{ formatDateTime(item.created_at) }}</td>
                            <td class="px-4 py-2">
                                <a :href="route('back-office.activity-logs.details', { slug: item.slug })"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs border border-blue-500 text-blue-500 rounded hover:bg-blue-50">
                                    <FontAwesomeIcon icon="info" />
                                    Details
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-center mt-4">
                <a :href="route('back-office.activity-logs.show-all', { modelSlug: modelSlug, recordSlug: model?.slug })"
                    class="inline-flex items-center justify-center px-4 py-2 border border-gray-400 text-gray-600 rounded hover:bg-gray-100">
                    Show All
                </a>
            </div>
        </div>

        <div v-else>
            <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-3 rounded">
                No records found
            </div>
        </div>
    </div>
</template>
