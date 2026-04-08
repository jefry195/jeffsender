<script setup>
defineProps({
  modelValue: [String, Number],
  label: String,
  type: {
    type: String,
    default: 'text'
  },
  placeholder: String,
  error: String,
  required: Boolean,
  autofocus: Boolean,
  autocomplete: String,
  id: String,
  disabled: Boolean
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="flex flex-col gap-2">
    <!-- Label -->
    <div v-if="label" class="flex items-center justify-between">
      <label
        :for="id"
        class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
      >
        {{ label }} <span v-if="required" class="text-red-500">*</span>
      </label>

      <slot name="label-suffix"></slot>
    </div>

    <!-- Input Wrapper -->
    <div class="relative">
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
        :required="required"
        :autofocus="autofocus"
        :autocomplete="autocomplete"
        :disabled="disabled"
        class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm placeholder:text-zinc-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus-visible:ring-zinc-300"
        :class="{ 'border-red-500 focus-visible:ring-red-500 dark:border-red-500': error }"
      />

      <div v-if="$slots.suffix" class="absolute right-3 top-1/2 -translate-y-1/2 transform">
        <slot name="suffix"></slot>
      </div>
    </div>

    <!-- Error Message -->
    <p v-if="error" class="text-sm font-medium text-red-500">
      {{ error }}
    </p>
  </div>
</template>
