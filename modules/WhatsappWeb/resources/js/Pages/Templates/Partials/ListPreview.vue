<script setup>
import { computed } from 'vue'

const props = defineProps(['meta'])

const title = computed(() => props.meta?.title || '')
const text = computed(() => props.meta?.text || 'Your list message text will appear here')
const footer = computed(() => props.meta?.footer || '')
const buttonText = computed(() => props.meta?.button_text || 'Select Option')
const sections = computed(() => props.meta?.sections || [])
</script>

<template>
  <div class="card mx-auto max-w-sm rounded-lg p-3 font-sans shadow-md bg-white dark:bg-dark-800 text-gray-800 dark:text-gray-100">
    <!-- Title Header -->
    <div v-if="title" class="mb-1 text-xs font-bold text-gray-900 dark:text-white border-b pb-1 border-gray-100 dark:border-gray-700">
      {{ title }}
    </div>

    <!-- Body Text -->
    <p class="text-xs leading-4 whitespace-pre-wrap">
      {{ text }}
    </p>

    <!-- Footer -->
    <p v-if="footer" class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
      {{ footer }}
    </p>

    <!-- Interactive Select Button -->
    <div class="mt-3 border-t border-gray-100 dark:border-gray-700 pt-2 flex items-center justify-center">
      <button type="button" class="flex items-center gap-1.5 text-xs font-semibold text-green-600 dark:text-green-400 hover:opacity-80 transition py-1 px-3 bg-green-50 dark:bg-green-950/30 rounded-md border border-green-200 dark:border-green-800 w-full justify-center">
        <Icon icon="bx:list-ul" class="text-base" />
        <span>{{ buttonText }}</span>
      </button>
    </div>

    <!-- Simulated Dropdown/List Content -->
    <div v-if="sections.length > 0" class="mt-3 bg-gray-50 dark:bg-dark-900/50 rounded-lg p-2 border border-gray-100 dark:border-gray-800/80 space-y-3">
      <div v-for="(section, sIdx) in sections" :key="sIdx" class="space-y-1">
        <span class="text-[9px] uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500 block">
          {{ section.title || 'Options' }}
        </span>
        <div class="space-y-1 pl-1">
          <div v-for="(row, rIdx) in section.rows" :key="rIdx" class="text-xs p-1.5 rounded hover:bg-gray-100 dark:hover:bg-dark-800 flex flex-col transition cursor-pointer">
            <span class="font-medium text-gray-800 dark:text-gray-200">{{ row.title || `Row ${rIdx + 1}` }}</span>
            <span v-if="row.description" class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">{{ row.description }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Timestamp -->
    <p class="mt-2 text-right text-[9px] text-gray-400 dark:text-gray-500">
      {{ new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
    </p>
  </div>
</template>
