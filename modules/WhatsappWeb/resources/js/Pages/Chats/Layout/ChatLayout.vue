<script setup>
import { useChatStore } from '@whatsappWeb/Stores/chatStore'
import LeftSidebar from './LeftSidebar.vue'
const chatStore = useChatStore()
</script>

<template>
  <div class="flex h-screen w-screen flex-col overflow-hidden bg-[#eae6df] dark:bg-dark-900 text-slate-800 dark:text-slate-200">
    <!-- Connection lost alert banner -->
    <div class="alert alert-danger mb-0 rounded-none py-2 text-center text-xs font-semibold" v-if="chatStore.disconnected">
      {{ trans('Connection lost, cannot connect to whatsapp server') }}
    </div>

    <!-- 1. Top Green Header (WaCRM Style) -->
    <div class="flex h-12 w-full shrink-0 items-center justify-between bg-[#008069] px-6 text-white shadow-sm select-none">
      <div class="flex items-center gap-x-3 font-semibold text-lg">
        <Icon icon="ri:whatsapp-fill" class="text-2xl text-white" />
        <span>WaCRM</span>
      </div>
      <div class="flex items-center gap-x-6 text-sm font-medium">
        <a :href="route('user.whatsapp-web.platforms.index')" class="flex items-center gap-x-1.5 text-white hover:opacity-80 transition-opacity">
          <Icon icon="mdi:account-multiple" class="text-lg" />
          <span>ACCOUNTS</span>
        </a>
      </div>
    </div>

    <!-- 2. Tabs Sub-header -->
    <div class="flex h-9 w-full shrink-0 items-center bg-[#f0f2f5] dark:bg-dark-800 px-4 border-b border-[#e9edef] dark:border-dark-700 select-none">
      <div class="flex h-full items-center gap-x-1">
        <div class="flex h-full items-center bg-white dark:bg-dark-700 px-5 text-xs font-semibold text-[#008069] border-t-2 border-[#008069] rounded-t-sm shadow-sm cursor-pointer">
          w
        </div>
      </div>
    </div>

    <!-- 3. Main Workspace Area -->
    <div class="flex flex-1 overflow-hidden">
      <!-- Left Vertical Icon Bar (Narrow Menu) -->
      <div class="flex w-16 shrink-0 flex-col items-center justify-between border-r border-[#e9edef] dark:border-dark-700 bg-[#f0f2f5] dark:bg-dark-800 py-4 select-none">
        <div class="flex flex-col items-center gap-y-6 w-full">
          <button class="relative flex h-10 w-10 items-center justify-center rounded-full text-[#54656f] dark:text-slate-300 hover:bg-[#d9dbde] dark:hover:bg-dark-700 active transition-all">
            <Icon icon="mdi:message-text" class="text-xl text-[#008069]" />
            <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#00a884] text-[9px] font-bold text-white shadow-sm">12</span>
          </button>
          <button class="flex h-10 w-10 items-center justify-center rounded-full text-[#54656f] dark:text-slate-300 hover:bg-[#d9dbde] dark:hover:bg-dark-700 transition-all">
            <Icon icon="mdi:circle-slice-8" class="text-xl" />
          </button>
          <button class="flex h-10 w-10 items-center justify-center rounded-full text-[#54656f] dark:text-slate-300 hover:bg-[#d9dbde] dark:hover:bg-dark-700 transition-all">
            <Icon icon="mdi:account-group" class="text-xl" />
          </button>
          <button class="flex h-10 w-10 items-center justify-center rounded-full text-[#54656f] dark:text-slate-300 hover:bg-[#d9dbde] dark:hover:bg-dark-700 transition-all">
            <Icon icon="mdi:bullhorn" class="text-xl" />
          </button>
          <button class="flex h-10 w-10 items-center justify-center rounded-full text-[#54656f] dark:text-slate-300 hover:bg-[#d9dbde] dark:hover:bg-dark-700 transition-all">
            <Icon icon="mdi:database" class="text-xl" />
          </button>
        </div>
        <div class="flex flex-col items-center gap-y-6 w-full">
          <button class="flex h-10 w-10 items-center justify-center rounded-full text-[#54656f] dark:text-slate-300 hover:bg-[#d9dbde] dark:hover:bg-dark-700 transition-all">
            <Icon icon="mdi:cog" class="text-xl" />
          </button>
          <div class="h-8 w-8 overflow-hidden rounded-full border border-gray-300 bg-white dark:bg-dark-700">
            <Icon icon="mdi:account" class="h-full w-full p-1 text-gray-500 dark:text-gray-400" />
          </div>
        </div>
      </div>

      <!-- LeftSidebar & Slot Container -->
      <div class="flex flex-1 overflow-hidden relative">
        <LeftSidebar />
        <div class="flex-1 flex overflow-hidden">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
