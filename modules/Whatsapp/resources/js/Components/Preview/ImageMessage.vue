<script setup>
import { computed } from 'vue'

import MessageDownloadButton from '@/Components/Chats/MessageDownloadButton.vue'

const { message } = defineProps(['message'])
const mediaUrl = computed(() => message.meta?.media_url ?? message.body?.link ?? false)
const caption = computed(() => message.body?.caption ?? false)
</script>

<template>
  <MessageDownloadButton :message="message" attachment_key="media_url" :attachment_id="message.body.id">
    <img v-if="mediaUrl" :src="mediaUrl" />
    <img v-else src="/assets/images/image-placeholder.jpg" alt="">
    <p v-if="caption" class="text-black dark:text-white">{{ caption }}</p>
  </MessageDownloadButton>
</template>
