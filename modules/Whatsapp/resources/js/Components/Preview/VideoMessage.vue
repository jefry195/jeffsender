<script setup>
import { computed } from 'vue'

import MessageDownloadButton from '@/Components/Chats/MessageDownloadButton.vue'

const { message } = defineProps(['message'])
const url = computed(() => message.meta?.media_url ?? message.body?.link ?? false)
const caption = computed(() => message.body?.caption ?? false)
</script>

<template>
    <MessageDownloadButton :message="message" attachment_key="link" :attachment_id="message.body.id">
        <div>
            <video v-if="url" :src="url" controls />
            <p v-if="caption" class="dark:bg-dark-800 px-2 py-1 rounded">{{ caption }}</p>
        </div>
    </MessageDownloadButton>
</template>