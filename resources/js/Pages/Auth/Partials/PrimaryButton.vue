<script setup>
defineProps({
  type: {
    type: String,
    default: 'submit'
  },
  disabled: Boolean,
  variant: {
    type: String,
    default: 'primary' // 'primary' or 'outline'
  }
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-md px-4 py-2 text-sm font-medium ring-offset-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 dark:ring-offset-neutral-950 dark:focus-visible:ring-neutral-300"
    :class="[
      variant === 'primary'
        ? 'bg-neutral-900 text-neutral-50 hover:bg-neutral-900/90 dark:bg-neutral-50 dark:text-neutral-900 dark:hover:bg-neutral-50/90'
        : 'border border-neutral-200 bg-white hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:bg-neutral-800 dark:hover:text-neutral-50'
    ]"
  >
    <slot />
  </button>
</template>
