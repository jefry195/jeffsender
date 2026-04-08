<script setup>
defineProps(['message'])
</script>

<template>
  <img :src="message.body.link || '/assets/images/image-placeholder.jpg'" class="max-h-96" />
  <p v-if="message.body?.caption" class="text-black dark:text-white">{{ message.body?.caption }}</p>
</template>
