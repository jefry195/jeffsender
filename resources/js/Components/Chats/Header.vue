<script setup>
import { useChatStore } from '@/Store/chatStore'
import { useForm } from '@inertiajs/vue3'

const chatStore = useChatStore()

const toggleAutoReply = () => {
  let form = useForm({
    update_type: 'auto_reply',
    module: chatStore.activeConversation.module
  })

  form.put(`/user/conversations/${chatStore.activeConversationId}`)
}
</script>

<template>
  <!-- Chat Wrapper Header Starts -->
  <div class="flex items-center justify-between border-b border-b-slate-200 p-3 dark:border-b-dark-900">
    <!-- Avatar and Menu Button Start -->
    <div class="flex items-center justify-start gap-x-1 md:gap-x-3">
      <button @click="chatStore.toggleLeftSidebar" type="button"
        class="text-slate-500 hover:text-slate-700 focus:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 dark:focus:text-slate-300 xl:hidden">
        <Icon icon="bx:menu" class="size-6" />
      </button>

      <button type="button" class="avatar avatar-circle" @click="chatStore.toggleRightSidebar">
        <img class="avatar-img relative size-8" v-if="chatStore.activeConversation.customer?.picture"
          :src="chatStore.activeConversation.customer?.picture" />
        <div class="absolute -right-0 bottom-0 rounded-full bg-white p-0.5">
          <!-- whatsapp -->
          <Icon v-if="chatStore.activeConversation.module == 'whatsapp'" icon="ri:whatsapp-fill"
            class="size-4 text-green-600" />
          <!-- messenger -->
          <Icon v-if="chatStore.activeConversation.module == 'messenger'" icon="uil:facebook-messenger"
            class="size-4 text-blue-700" />
          <!-- telegram -->
          <Icon v-if="chatStore.activeConversation.module == 'telegram'" icon="ri:telegram-fill"
            class="size-4 text-blue-600" />
          <!-- instagram -->
          <Icon v-if="chatStore.activeConversation.module == 'instagram'" icon="ri:instagram-fill"
            class="size-4 text-pink-600" />
          <!-- viber -->
        </div>
      </button>

      <div>
        <h6 class="whitespace-nowrap text-xs font-medium text-slate-700 dark:text-slate-100 md:text-sm">
          {{ chatStore.activeConversation.customer?.name }}
          <template v-if="chatStore.activeConversation.badge_id">
            <span class="rounded p-0 px-2 text-sm text-white" :style="{
              background: chatStore.getBadge(chatStore.activeConversation.badge_id)?.color
            }">
              {{ chatStore.getBadge(chatStore.activeConversation.badge_id)?.text }}
            </span>
          </template>
        </h6>
        <p class="truncate text-xs font-normal text-slate-500 dark:text-slate-400 md:text-sm">
          {{ chatStore.activeConversation.customer?.uuid }}
        </p>
      </div>
    </div>
    <!-- Avatar and Menu Button End -->

    <!-- Actions and More Dropdown Start -->
    <div class="flex flex-wrap items-center gap-1">
      <label for="toggle-unchecked-input" class="toggle">
        <input @change="toggleAutoReply" class="toggle-input peer sr-only" id="toggle-unchecked-input" type="checkbox"
          :checked="chatStore.activeConversation.auto_reply_enabled" />
        <div class="toggle-body"></div>
        <span class="label">{{ trans('Auto Reply') }}</span>
      </label>

      <button class="btn px-1 md:px-2" type="button" @click="chatStore.toggleRightSidebar">
        <Icon icon="bx:bxs-info-circle" class="size-6 text-primary-500" />
      </button>
    </div>
    <!-- Actions and More Dropdown End -->
  </div>
  <!-- Chat Wrapper Header Ends -->
</template>
