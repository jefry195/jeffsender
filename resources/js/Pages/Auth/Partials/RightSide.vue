<script setup></script>
<template>
  <div class="flex flex-col gap-4 p-6 dark:bg-zinc-950 md:p-10">
    <div class="flex justify-center gap-2 md:justify-start">
      <a href="/" class="flex items-center gap-2 font-medium text-gray-900 dark:text-gray-200">
        <div
          class="bg-primary text-primary-foreground flex h-6 w-6 items-center justify-center rounded-md"
        >
          <Icon icon="lucide:gallery-vertical-end" />
        </div>
        {{ $page.props?.app_name || 'App' }}
      </a>
    </div>

    <div class="flex flex-1 items-center justify-center">
      <div class="w-full max-w-md text-gray-900 dark:text-gray-200">
        <slot></slot>
      </div>
    </div>
  </div>
</template>
