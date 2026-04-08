<script setup lang="ts">
import trans from '@/Composables/transComposable'
const emit = defineEmits(['click'])
</script>

<template>
  <div class="card card-body relative flex flex-col items-center justify-center gap-4">
    <button
      class="absolute left-1 top-1 inline-flex size-9 items-center justify-center rounded-full bg-black/20 xl:hidden"
      @click="emit('click')"
    >
      <Icon icon="bx:menu" class="size-6" />
    </button>
    <img class="w-2/3" src="/assets/svg/chatting.svg" alt="" />
    <h4 class="text-gray-400 dark:text-gray-500">{{ trans('Start a conversation') }}</h4>
  </div>
</template>
