import { nextTick } from 'vue'

import axios from 'axios'
import { defineStore } from 'pinia'

import echoService from '@/Composables/echoServiceComposable'
import toast from '@/Composables/toastComposable'
import { useModalStore } from '@/Store/modalStore'

export const useChatStore = defineStore('chatStore', {
  state: () => ({
    features: {
      voice_message: true
    },
    platform: null,
    leftSidebar: {
      isOpen: true
    },

    // lists
    searchedConversationList: [],
    conversations: [],
    chatTemplates: [],
    quickReplies: [],
    aiTemplates: [],
    badges: [],

    // single values
    activeConversationId: null,
    rightSidebar: {
      isOpen: false
    },
    assetPopup: false,
    messageInputFieldRef: null,
    chatSearchInput: '',
    chatSearchInputRef: null,
    chatSearchedItems: [],
    replying: {},
    searchForm: {
      badge_id: null,
      customer_name: '',
      assigned_to: null
    },
    loading: {
      sendingMessage: false,
      messages: false,
      conversations: false,
      searching: false,
      message_searching: false
    },
    inputMessage: {
      conversation_id: null,
      type: 'text',
      message: '',
      caption: '',
      attachments: [],
      template: null,
      context: null
    },

    // quick reply related
    arrowCounter: 0,
    quickReplySearchInput: '',
    quickReplySuggestionItemsShow: false,
    quickReplyModalIsOpen: false,
    quickReplyModalSearchInput: null
  }),

  getters: {
    filteredConversations() {
      if (
        this.searchForm.customer_name.length > 0 ||
        this.searchForm.badge_id ||
        this.searchForm.assigned_to
      ) {
        return this.searchedConversationList
      }
      return this.conversations
    },

    hasConversations() {
      return this.conversations.length > 0
    },

    hasActiveConversation() {
      return this.activeConversationId !== null
    },

    activeConversation() {
      return this.conversations.find((c) => c.id === this.activeConversationId) ?? {}
    },

    activeConversationMessages() {
      const messages = this.activeConversation.messages ?? []
      return messages.length ? messages.slice().reverse() : []
    },

    isReplying() {
      return this.replying.message_id
    },

    getActiveModuleName() {
      return (
        (this.activeConversation?.module ?? '').charAt(0).toUpperCase() +
        (this.activeConversation?.module ?? '').slice(1)
      )
    },

    quickReplyFilteredItems() {
      let activeModule = this.activeConversation?.module ?? ''
      let list = this.quickReplies
      if (this.quickReplySearchInput.length > 0) {
        list = list.filter((item) =>
          item.message_template.toLowerCase().includes(this.quickReplySearchInput.toLowerCase())
        )
      }
      return list
        .filter((item) => item.module === activeModule)
        .map((item) => item.message_template)
    },

    quickReplySuggestionItems() {
      let activeModule = this.activeConversation?.module ?? ''
      let list = []
      if (this.inputMessage.message?.length > 0) {
        list = this.quickReplies.filter((item) =>
          item.message_template.toLowerCase().includes(this.inputMessage.message?.toLowerCase())
        )
      }
      return list
        .filter((item) => item.module === activeModule)
        .map((item) => item.message_template)
    },

    getActiveModuleTemplates() {
      return this.chatTemplates.filter((item) => item.module == this.activeConversation.module)
    }
  },

  actions: {
    downloadAttachment(messageId, options) {
      axios.post(route('user.messages.download-attachment', messageId), options).then((res) => {
        console.log(res)
      })
    },

    getBadge(badge_id) {
      return this.badges.find((b) => b.id === badge_id) ?? null
    },

    async getChatSearchedItems() {
      if (this.chatSearchInput.length === 0) {
        this.chatSearchedItems = []
        return
      }
      try {
        this.loading.message_searching = true
        const res = await axios.get(route('user.conversations.api', 'messages'), {
          params: {
            module: this.platform?.module,
            conversation_id: this.activeConversationId,
            search: this.chatSearchInput
          }
        })
        this.chatSearchedItems = res.data?.data ?? []
      } catch (err) {
        console.error(err)
      } finally {
        this.loading.message_searching = false
      }
    },

    setActiveConversation(id) {
      this.activeConversationId = id
      this.activeConversation.unread_count = 0
      // this.scrollToLastMessage()
    },

    toggleRightSidebar() {
      this.rightSidebar.isOpen = !this.rightSidebar.isOpen
    },

    scrollToLastMessage(behavior = 'smooth') {
      setTimeout(() => {
        let container = document.querySelector('#scrollContainerRef')
        if (container) container.scrollIntoView({ block: 'end', behavior })
      }, 300)
    },

    setReplying(mgs) {
      this.replying = {
        message_id: mgs.uuid,
        message: mgs.body?.text ?? mgs.body?.caption ?? mgs.type ?? ''
      }
    },

    unsetReplying() {
      this.replying = {}
    },

    touchConversation(conversation) {
      conversation.updated_at = new Date().toISOString()
    },

    shortConversations() {
      this.conversations.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))
    },

    loadMoreMessages(conversationId) {
      console.log('loading more messages')
      if (this.loading.messages) return

      this.loading.messages = true
      axios
        .get(route('user.conversations.api', 'load_more_messages'), {
          params: {
            module: this.platform?.module,
            conversation_id: conversationId ?? this.activeConversationId,
            limit: 10,
            last_message_id: conversationId ? 0 : this.activeConversationMessages[0].id
          }
        })
        .then((res) => {
          let no_more_messages = false
          if (res.data.length === 0) {
            no_more_messages = true
            if (no_more_messages) {
              let conversation = this.conversations.find((c) => c.id == this.activeConversationId)
              if (conversation) {
                conversation.no_more_messages = true
              }
            }
            return
          }
          this.conversations = this.conversations.map((conversation) => {
            if (conversation.id == this.activeConversationId) {
              res.data.forEach((message, index) => {
                conversation.messages.push(message)
              })
            }
            return conversation
          })
        })
        .catch((err) => console.error(err))
        .finally(() => {
          this.loading.messages = false
        })
    },

    loadMoreConversations() {
      console.log('load more conversations')

      if (this.loading.conversations) return
      this.loading.conversations = true
      axios
        .get(route('user.conversations.api', 'load_more_conversations'), {
          params: {
            module: this.platform?.module,
            limit: 10,
            last_conversation_id: this.conversations.length > 0 ? this.conversations[0].id : null
          }
        })
        .then((res) => {
          if (res.data.length === 0) {
            console.log('no more conversations')
            return
          }

          // push unique conversations
          let uniqueConversations = []
          res.data.forEach((conversation) => {
            if (!uniqueConversations.find((c) => c.id == conversation.id)) {
              uniqueConversations.push(conversation)
            }
          })
        })
        .catch((err) => console.error(err))
        .finally(() => (this.loading.conversations = false))
    },

    async loadBadges() {
      try {
        const res = await axios.get(route('user.conversations.api', 'badges'))
        this.badges = res.data
      } catch (err) {
        console.error(err)
      }
    },

    async submitMessage() {
      let messageData = {
        conversation_id: this.activeConversationId ?? null,
        reply_message_uuid: this.replying?.message_id ?? null,
        type: this.inputMessage.type ?? 'text',
        text: this.inputMessage.message ?? null,
        caption: this.inputMessage.caption ?? null,
        template: this.inputMessage.template ?? null,
        attachments: this.inputMessage.attachments ?? []
      }

      try {
        this.loading.sendingMessage = true
        const res = await axios.post(route('user.messages.store'), messageData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        this.conversations = this.conversations.map((conversation) => {
          if (conversation.id == this.activeConversationId) {
            conversation.messages.unshift(res.data)
          }
          return conversation
        })

        // reset to default
        this.resetInputMessage()
        this.messageInputFieldRef?.focus()
        this.unsetReplying()
        this.touchConversation(this.activeConversation)
        this.shortConversations()
      } catch (err) {
        toast.danger(err.response?.data?.message || err.message || 'Something went wrong!')
        console.error(err)
      } finally {
        this.closeTemplateModal()
        this.loading.sendingMessage = false
        this.assetPopup = false
        this.scrollToLastMessage()
      }
    },

    resetInputMessage() {
      this.inputMessage = {
        conversation_id: null,
        type: 'text',
        message: '',
        caption: '',
        template: null,
        attachments: []
      }
    },

    closeTemplateModal() {
      useModalStore().close('templateModal')
    },

    // Quick reply functions
    onArrowDown(evt) {
      if (
        this.quickReplyModalIsOpen &&
        this.arrowCounter < this.quickReplyFilteredItems.length - 1
      ) {
        this.arrowCounter = this.arrowCounter + 1
      } else if (this.arrowCounter < this.quickReplyFilteredItems.length - 1) {
        this.arrowCounter = this.arrowCounter + 1
      }
    },

    onArrowUp(evt) {
      if (this.arrowCounter > 0) {
        this.arrowCounter = this.arrowCounter - 1
      }
    },

    async addQuickReplyToMessageInput(text) {
      const input = document.getElementById('inputMessageField')
      input.blur()
      this.quickReplyModalClose()
      this.inputMessage.message = this.replaceTextWithShortCodes(text) + ' '
      await nextTick()
      input.focus() // Ensure focus remains
    },

    replaceTextWithShortCodes(text) {
      let activeChat = {
        // whatsapp
        name: this.activeConversation.customer?.name,
        phone: this.activeConversation.customer?.uuid,
        // telegram
        username: this.activeConversation.customer?.meta?.username
      }

      return (
        text?.replace(/\{([a-z_]+)\}/g, (match, key) => {
          return activeChat.hasOwnProperty(key) ? activeChat[key] : match
        }) ?? text
      )
    },

    quickReplyModalOpen() {
      const modalStore = useModalStore()
      modalStore.open('quickReplyModal')
      this.quickReplyModalIsOpen = true
    },

    quickReplyModalClose() {
      const modalStore = useModalStore()
      modalStore.close('quickReplyModal')
      this.quickReplyModalIsOpen = false
    },

    selectQuickReply(evt) {
      let text = ''
      if (this.quickReplyModalIsOpen) {
        text = this.quickReplyFilteredItems[this.arrowCounter]
      } else {
        text = this.quickReplyFilteredItems[this.arrowCounter]
      }
      this.addQuickReplyToMessageInput(text)
      this.arrowCounter = -1
    },

    // WebSocket functions
    connectWebSocket(channelName) {
      echoService
        .connect()
        ?.private(channelName)
        .subscribed(() => console.log('Live chat activated successfully'))
        .listen('IncomingNewMessageEvent', this.handleIncomingMessages)
        .listen('IncomingMessageUpdateEvent', this.handleIncomingMessageUpdates)
        .listen('IncomingNewConversationEvent', this.handleIncomingConversations)
        .error((err) => console.error(err))
    },

    disconnectWebSocket() { },

    handleIncomingMessages(newMessage) {
      // TODO: clear logs after test
      console.log('new message received', newMessage)
      this.conversations.forEach((chat) => {
        if (chat.id === newMessage?.conversation_id) {
          // check message if already exists
          const existingMessage = chat.messages.find((message) => message.id === newMessage.id)
          if (existingMessage) return console.log('message already exists')

          console.log('pushing new message')
          chat.messages.unshift(newMessage)
          this.touchConversation(chat)
          this.shortConversations()
          chat.unread_count += 1
          this.playSound()
        } else {
          console.log('conversation not found')
        }
      })
      this.scrollToLastMessage()
    },

    handleIncomingMessageUpdates(updatedMessage) {
      console.log('message update')
      this.conversations.forEach((chat) => {
        if (chat.id == updatedMessage.conversation_id) {
          chat.messages.forEach((message) => {
            if (message.id == updatedMessage.id) {
              message.status = updatedMessage.status
            }
          })
        }
      })
    },

    handleIncomingConversations(newConversation) {
      console.log('new conversation received')
      this.conversations.value = this.conversations.unshift(newConversation)
      this.loadMoreMessages(newConversation.id)
      newConversation.unread_count = newConversation.messages?.length ?? 1
    },

    playSound() {
      let audio = new Audio('/assets/incoming-message-beep.mp3')
      audio.play()
    },

    toggleLeftSidebar() {
      this.leftSidebar.isOpen = !this.leftSidebar.isOpen
    }
  }
})
