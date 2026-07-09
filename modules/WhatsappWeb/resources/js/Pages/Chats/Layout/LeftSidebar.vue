<script setup>
import { onMounted, ref, computed } from 'vue'

import HollowDotsSpinner from '@/Components/HollowDotsSpinner.vue'
import IntersectionObserver from '@/Components/IntersectionObserver.vue'
import sharedComposable from '@/Composables/sharedComposable'
import { useChatStore } from '@whatsappWeb/Stores/chatStore'
import moment from 'moment'
import { useModalStore } from '@/Store/modalStore'

const modalStore = useModalStore()
const props = defineProps({
  conversations: {
    type: Array,
    default: []
  }
})

const { debounce, pickBy } = sharedComposable()
const chatStore = useChatStore()

const scrollContainerRef = ref(null)
const endOfScrollRef = ref(null)

// Alerts state
const showNotifAlert = ref(true)
const showBroadcastAlert = ref(true)

// Active filter pill
const activeFilter = ref('all')

onMounted(() => {
  let searchFormQueries = new URLSearchParams(window.location.search)
  chatStore.searchForm.badge_id = searchFormQueries.get('badge_id') || ''
  chatStore.searchForm.customer_name = searchFormQueries.get('customer_name') || ''
})

// Dynamic counts
const unreadTotal = computed(() => {
  return chatStore.conversations.filter(c => c.unreadCount > 0).length
})

const groupsTotal = computed(() => {
  return chatStore.conversations.filter(c => c.id.includes('@g.us')).length
})

// Local filtering based on search AND filter pills
const localFilteredConversations = computed(() => {
  let list = chatStore.filteredConversations || []
  if (activeFilter.value === 'unread') {
    return list.filter(c => c.unreadCount > 0)
  }
  if (activeFilter.value === 'groups') {
    return list.filter(c => c.id.includes('@g.us'))
  }
  return list
})

// Helper to extract last message text or media type
const getLastMessageText = (chat) => {
  if (chat.lastMessageText) return chat.lastMessageText
  let msg = chat.lastMessage
  if (!msg) return ''
  let text = msg.message?.conversation || msg.message?.extendedTextMessage?.text || ''
  if (!text && msg.message?.imageMessage) text = '📷 Photo'
  if (!text && msg.message?.videoMessage) text = '🎥 Video'
  if (!text && msg.message?.audioMessage) text = '🎵 Audio'
  if (!text && msg.message?.documentMessage) text = '📄 Document'
  return text
}
</script>

<template>
  <!-- Chat Left Sidebar Starts -->
  <div
    id="chat-sidebar"
    class="absolute bottom-0 top-0 z-30 h-full w-full shrink-0 border-r border-[#e9edef] dark:border-dark-700 bg-white dark:bg-dark-800 flex flex-col transition-all duration-300 sm:w-80 lg:relative xl:w-3/12 select-none"
    :class="[
      chatStore.leftSidebar.isOpen ? 'translate-x-0' : 'translate-x-[-110%]',
      'lg:translate-x-0'
    ]"
  >
    <!-- Chat Sidebar Header Starts -->
    <div class="p-3 border-b border-[#e9edef] dark:border-dark-700 shrink-0">
      <!-- Title and Action Buttons -->
      <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-bold text-[#111b21] dark:text-slate-100">WhatsApp</h1>
        <div class="flex items-center gap-x-2 text-[#54656f] dark:text-slate-300">
          <button class="hover:bg-slate-100 dark:hover:bg-dark-700 p-1.5 rounded-full transition-all" title="New Chat">
            <Icon icon="mdi:message-plus" class="text-xl" />
          </button>
          <button class="hover:bg-slate-100 dark:hover:bg-dark-700 p-1.5 rounded-full transition-all" title="Menu">
            <Icon icon="mdi:dots-vertical" class="text-xl" />
          </button>
        </div>
      </div>

      <!-- Badge selection dropdown -->
      <div class="mb-3 flex items-center gap-2 text-nowrap">
        <select
          class="select text-xs py-1"
          v-model="chatStore.searchForm.badge_id"
        >
          <option value="">{{ trans('Filter by badge') }}</option>
          <option v-for="badge in chatStore.badges" :value="badge.id" :key="badge.id">
            {{ badge.text }}
          </option>
        </select>
        <button class="btn btn-primary btn-xs py-1 px-2" @click="modalStore.open('badgeModal')">+</button>
      </div>

      <!-- Search Input -->
      <div
        class="group flex h-9 w-full items-center overflow-hidden rounded-lg bg-[#f0f2f5] dark:bg-dark-900 border border-transparent focus-within:border-[#008069] focus-within:ring-1 focus-within:ring-[#008069]"
      >
        <div class="flex h-full items-center px-3 text-slate-500">
          <Icon
            icon="fe:search"
            class="text-base text-slate-400 group-focus-within:text-[#008069]"
          />
        </div>
        <input
          class="h-full w-full border-transparent bg-transparent px-0 py-1 text-sm placeholder-slate-400 focus:border-transparent focus:outline-none focus:ring-0 dark:text-slate-200"
          type="text"
          v-model="chatStore.searchForm.customer_name"
          placeholder="Search or start a new chat"
        />
      </div>

      <!-- Filter Pills -->
      <div class="flex gap-x-2 mt-3 text-xs overflow-x-auto pb-1 scrollbar-none">
        <button
          @click="activeFilter = 'all'"
          class="px-3.5 py-1.5 rounded-full font-medium transition-all text-nowrap shrink-0"
          :class="[
            activeFilter === 'all'
              ? 'bg-[#e7f5f4] text-[#008069] dark:bg-[#008069]/20'
              : 'bg-[#f0f2f5] text-[#54656f] hover:bg-slate-200 dark:bg-dark-700 dark:text-slate-300'
          ]"
        >
          All
        </button>
        <button
          @click="activeFilter = 'unread'"
          class="px-3.5 py-1.5 rounded-full font-medium transition-all flex items-center gap-x-1.5 text-nowrap shrink-0"
          :class="[
            activeFilter === 'unread'
              ? 'bg-[#e7f5f4] text-[#008069] dark:bg-[#008069]/20'
              : 'bg-[#f0f2f5] text-[#54656f] hover:bg-slate-200 dark:bg-dark-700 dark:text-slate-300'
          ]"
        >
          Unread
          <span v-if="unreadTotal > 0" class="bg-[#00a884] text-white text-[9px] font-bold h-4 w-4 rounded-full flex items-center justify-center">{{ unreadTotal }}</span>
        </button>
        <button
          @click="activeFilter = 'groups'"
          class="px-3.5 py-1.5 rounded-full font-medium transition-all flex items-center gap-x-1.5 text-nowrap shrink-0"
          :class="[
            activeFilter === 'groups'
              ? 'bg-[#e7f5f4] text-[#008069] dark:bg-[#008069]/20'
              : 'bg-[#f0f2f5] text-[#54656f] hover:bg-slate-200 dark:bg-dark-700 dark:text-slate-300'
          ]"
        >
          Groups
          <span v-if="groupsTotal > 0" class="bg-[#00a884] text-white text-[9px] font-bold h-4 w-4 rounded-full flex items-center justify-center">{{ groupsTotal }}</span>
        </button>
      </div>

      <!-- Chat Sidebar Close Button Starts -->
      <button
        id="chat-btn-hide-sidebar"
        type="button"
        v-if="chatStore.leftSidebar.isOpen"
        @click="chatStore.toggleLeftSidebar"
        class="absolute left-full top-1 z-20 inline-flex h-8 w-8 translate-x-4 items-center justify-center rounded-full bg-black/60 text-white dark:text-slate-300 xl:hidden"
      >
        <Icon icon="fe:close" />
      </button>
    </div>

    <!-- Alert Banners -->
    <div class="flex flex-col shrink-0" v-if="showNotifAlert || showBroadcastAlert">
      <!-- Notifications Alert Banner -->
      <div v-if="showNotifAlert" class="flex items-center gap-x-3 bg-[#53bdeb] px-4 py-3 text-white text-xs relative">
        <div class="p-1.5 bg-white/20 rounded-full shrink-0">
          <Icon icon="mdi:bell-off" class="text-base text-white" />
        </div>
        <div class="flex-1 pr-5">
          <p class="font-medium text-[#111b21]">Message notifications are off.</p>
          <a href="#" class="underline hover:text-slate-100 font-semibold">Turn on desktop notifications ></a>
        </div>
        <button @click="showNotifAlert = false" class="absolute right-2 top-2 text-white/80 hover:text-white">
          <Icon icon="mdi:close" class="text-sm" />
        </button>
      </div>

      <!-- Broadcast Alert Banner -->
      <div v-if="showBroadcastAlert" class="flex items-center gap-x-3 bg-[#e7f5f4] dark:bg-dark-700 px-4 py-3 text-[#54656f] dark:text-slate-300 text-xs relative border-b border-[#e9edef] dark:border-dark-700">
        <div class="p-1.5 bg-[#00a884]/10 rounded-full shrink-0">
          <Icon icon="mdi:bullhorn" class="text-base text-[#00a884]" />
        </div>
        <div class="flex-1 pr-5 text-[#111b21] dark:text-slate-200">
          <p class="font-semibold text-xs mb-0.5">Create broadcasts faster on web</p>
          <p class="text-[11px] leading-tight text-[#54656f] dark:text-slate-400">You can now use your computer to send business broadcasts. <a href="#" class="text-[#008069] underline font-semibold">Try it now</a></p>
        </div>
        <button @click="showBroadcastAlert = false" class="absolute right-2 top-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
          <Icon icon="mdi:close" class="text-sm" />
        </button>
      </div>
    </div>

    <!-- Chat Sidebar Body Starts -->
    <div class="styled-scrollbar flex-1 overflow-y-auto">
      <ul id="chat-list" ref="scrollContainerRef" class="divide-y divide-[#f0f2f5] dark:divide-dark-700">
        <!-- Archived list item -->
        <li class="group hover:bg-[#f0f2f5] dark:hover:bg-[#202c33] cursor-pointer">
          <div class="flex items-center justify-between px-4 py-3.5">
            <div class="flex items-center gap-x-4">
              <Icon icon="mdi:archive" class="text-[#00a884] text-xl" />
              <span class="text-sm font-semibold text-[#111b21] dark:text-slate-100">Archived</span>
            </div>
            <span class="text-[#008069] text-xs font-bold font-mono">@</span>
          </div>
        </li>

        <!-- Chat List Items -->
        <li
          v-for="chat in localFilteredConversations"
          class="group cursor-pointer"
          :class="{ active: chatStore.activeConversation?.id == chat.id }"
          :key="chat.id"
        >
          <a
            @click="chatStore.setActiveConversation(chat)"
            class="flex items-center gap-x-3 px-4 py-3 transition-colors duration-150 hover:bg-[#f0f2f5] group-[.active]:bg-[#e9edef] dark:hover:bg-[#202c33] group-[.active]:dark:bg-dark-900 border-none"
          >
            <div class="avatar avatar-circle size-11 shrink-0 relative">
              <img class="avatar-img relative" :src="chatStore.getConversationProfilePic(chat)" />
              <div class="absolute -right-1 -bottom-0.5 rounded-full bg-white dark:bg-dark-800 p-0.5 border border-[#e9edef] dark:border-dark-700">
                <Icon :icon="`ri:whatsapp-fill`" class="text-xs text-[#00c67e]" />
              </div>
            </div>
            <div class="flex-grow min-w-0">
              <div class="flex items-center justify-between mb-0.5">
                <p
                  class="truncate text-sm text-[#111b21] dark:text-slate-100 flex items-center gap-x-1.5"
                  :class="[chat.unreadCount > 0 ? 'font-semibold' : 'font-medium']"
                >
                  <span class="truncate">{{ chat.name ?? chat.id.split('@')[0].substring(2) ?? 'Unknown' }}</span>
                  <span
                    v-if="chat?.badge_id"
                    class="rounded p-0 px-2 text-[10px] text-white shrink-0"
                    :style="{ background: chatStore.getBadge(chat?.badge_id)?.color }"
                  >
                    {{ chatStore.getBadge(chat?.badge_id)?.text }}
                  </span>
                </p>
                <span class="text-[11px] font-normal text-gray-500 shrink-0">
                  {{
                    chat.conversationTimestamp
                      ? moment.unix(chat.conversationTimestamp).local().format('h:mm A')
                      : ''
                  }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <p class="truncate text-xs font-normal text-[#667781] dark:text-slate-400 max-w-[180px]">
                  <span v-if="chat.lastMessage?.key?.fromMe" class="mr-1">
                    <Icon icon="mdi:check-all" class="inline text-xs text-[#53bdeb]" />
                  </span>
                  {{ getLastMessageText(chat) || 'No messages' }}
                </p>
                <span
                  v-if="chat.unreadCount > 0"
                  class="flex h-5 min-w-5 px-1 items-center justify-center rounded-full bg-[#00a884] text-[10px] font-bold text-white shrink-0"
                >
                  {{ chat.unreadCount ?? '' }}
                </span>
              </div>
            </div>
          </a>
        </li>

        <li class="py-1 border-none">
          <IntersectionObserver
            :intersectionStart="0"
            :afterIntersection="chatStore.loadMoreConversations"
            :loader="chatStore.loading.conversations"
          />
        </li>
        <li
          v-if="
            localFilteredConversations.length === 0 &&
            (chatStore.searchForm.customer_name.length > 0 || chatStore.searchForm.badge_id)
          "
          class="border-none"
        >
          <HollowDotsSpinner class="mx-auto my-4" v-if="chatStore.loading.searching" />
          <div
            v-else
            class="text-center py-6 text-sm text-slate-400"
          >
            {{ trans('No results found') }}
          </div>
        </li>
      </ul>
    </div>
    <!-- Chat Sidebar Body Ends -->
  </div>
  <div
    @click="chatStore.toggleLeftSidebar"
    v-if="chatStore.leftSidebar.isOpen"
    class="absolute bottom-0 left-0 right-0 top-0 z-10 h-full w-full bg-black/20 transition-colors duration-300 ease-in-out xl:hidden"
  ></div>
</template>
