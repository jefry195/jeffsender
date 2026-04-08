<script setup>
import UserLayout from '@/Layouts/User/UserLayout.vue'
import InteractivePreview from '@whatsapp/Components/Preview/InteractiveMessage.vue'
import MessagePreview from '@whatsapp/Pages/Templates/PreviewIndex.vue'

defineOptions({ layout: UserLayout })
const props = defineProps({
  template: {
    type: Object,
    default: {}
  }
})
</script>
<template>
  <div class="mx-auto max-w-2xl">
    <div class="card p-4">
      <p class="mb-2 ml-1 text-lg">{{ trans('Preview') }}</p>
      <InteractivePreview v-if="template.type == 'interactive'" :components="template.meta || []" />
      <MessagePreview v-else :message="{ type: template.type, body: template.meta || {} }" />
    </div>
  </div>
</template>
