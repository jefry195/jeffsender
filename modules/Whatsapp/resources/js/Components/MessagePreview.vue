<script setup>
import { defineAsyncComponent } from 'vue'

import UnsupportedMessage from '@whatsapp/Components/Preview/UnsupportedMessage.vue'

const props = defineProps({
  message: {
    type: Object,
    required: true
  }
})

const dynamicMessagePreview = () => {

  const messageType = props.message.type
  const componentName = `${messageType[0].toUpperCase()}${messageType.slice(1)}Message`
  return defineAsyncComponent({
    loader: () => import(`@whatsapp/Components/Preview/${componentName}.vue`),
    errorComponent: UnsupportedMessage
  })
}

</script>

<template>
  <div :title="message.type"
    class="text-danger rounded-primary rounded-tl-none bg-slate-100 p-2 text-sm group-[.pr]:rounded-tl-primary group-[.pr]:rounded-tr-none group-[.pr]:text-white dark:bg-slate-700 dark:text-slate-300">
    <component :is="dynamicMessagePreview()" :message="message" />
  </div>
</template>
