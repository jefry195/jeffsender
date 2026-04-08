<script setup>
import ShortCodes from '@whatsapp/Components/ShortCodes.vue'

const props = defineProps(['body'])
</script>

<template>
  <div>
    <textarea class="textarea" v-model="body.body" placeholder="enter message"></textarea>
    <ShortCodes v-model="body.body" class="mb-2" />

    <label for="preview_url" class="label">
      <input type="checkbox" id="preview_url" class="checkbox" v-model="body.preview_url" />
      {{ trans('Preview Url') }}</label>
  </div>
</template>
