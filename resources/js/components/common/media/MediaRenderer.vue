<script setup>
const { media, mediaClass, hideCaption } = defineProps({
    media: { type: Object, required: true },
    mediaClass: {
        type: String,
        default: 'w-full h-full object-contain rounded-lg border border-gray-200 shadow-sm'
    },
    hideCaption: {
        type: Boolean,
        default: false
    }
})
</script>

<template>
    <div>
        <figure v-if="media?.mime_type?.startsWith('image/')" class="flex flex-col items-center w-full h-full">
            <img :src="media?.preview_url || media?.original_url" :alt="media?.custom_properties?.alt" :srcset="media?.media_srcset"
                loading="lazy" :class="mediaClass" />

            <figcaption v-if="!hideCaption && media?.custom_properties?.caption" class="mt-2 text-sm text-gray-500 text-center">
                {{ media?.custom_properties?.caption }}
            </figcaption>
        </figure>

        <iframe v-else-if="media?.mime_type?.startsWith('video/')" :src="media?.preview_url || media?.original_url"
            :class="mediaClass"></iframe>

        <audio v-else-if="media?.mime_type?.startsWith('audio/')" controls :class="mediaClass">
            <source :src="media?.preview_url || media?.original_url" />
            Your browser does not support the audio element.
        </audio>

        <iframe v-else-if="media?.mime_type === 'application/pdf'" :src="media?.preview_url || media?.original_url"
            :class="mediaClass"></iframe>

        <iframe v-else-if="['application/json', 'text/plain', 'text/csv'].includes(media?.mime_type)"
            :src="media?.preview_url || media?.original_url" :class="mediaClass"></iframe>

        <div v-else-if="['application/zip', 'application/x-rar-compressed'].includes(media?.mime_type)">
            <a :href="media?.preview_url || media?.original_url" download
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Download
            </a>
        </div>

        <div v-else class="text-sm text-gray-500">
            This media type is not supported.
        </div>
    </div>
</template>
