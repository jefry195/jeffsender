<script setup>
import { computed } from 'vue'

import MessageDownloadButton from '@/Components/Chats/MessageDownloadButton.vue'

const { message } = defineProps(['message'])
const url = computed(() => message.meta?.media_url ?? message.body?.link ?? false)
</script>

<template>
    <MessageDownloadButton :message="message" attachment_key="link" :attachment_id="message.body.id">
        <audio v-if="url" controls :src="url" />
    </MessageDownloadButton>
</template>