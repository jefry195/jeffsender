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
        <a :href="route('user.dashboard')" class="flex items-center gap-x-1.5 text-white hover:opacity-80 transition-opacity">
          <Icon icon="mdi:arrow-left" class="text-lg" />
          <span>BACK TO DASHBOARD</span>
        </a>
        <a :href="route('user.whatsapp-web.platforms.index')" class="flex items-center gap-x-1.5 text-white hover:opacity-80 transition-opacity">
          <Icon icon="mdi:account-multiple" class="text-lg" />
          <span>ACCOUNTS</span>
        </a>
      </div>
    </div>

    <!-- 2. Main Workspace Area -->
    <div class="flex flex-1 overflow-hidden">
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
