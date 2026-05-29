import { defineStore } from 'pinia'
import { router } from '@inertiajs/vue3'

// Reset body overflow on every Inertia navigation to prevent scroll lock leaks
router.on('start', () => {
  document.body.style.overflow = ''
})

export const useModalStore = defineStore({
  id: 'modal',
  state: () => ({
    states: {}
  }),

  actions: {
    validateKey(key) {
      // Check if the key is a valid format
      if (/^[a-zA-Z0-9-]+$/.test(key)) {
        return key
      }
      // Convert the key to a valid format
      return key.replace(/[^a-zA-Z0-9-]/g, '-').toLowerCase()
    },
    open(key) {
      const validKey = this.validateKey(key)

      // Toggle: if already open, close it
      if (this.states[validKey] === true) {
        this.states[validKey] = false
        // Only restore scroll if no other modals are open
        const anyOpen = Object.values(this.states).some(v => v === true)
        if (!anyOpen) {
          document.body.style.overflow = ''
        }
        return
      }

      this.states[validKey] = true
      document.body.style.overflow = 'hidden'
    },
    close(key = null) {
      if (typeof key !== 'string') {
        this.$reset()
        document.body.style.overflow = ''
        return
      }

      if (key) {
        const validKey = this.validateKey(key)
        if (this.states.hasOwnProperty(validKey)) {
          this.states[validKey] = false
        }
      } else {
        this.$reset()
      }

      // Only restore scroll if no other modals are open
      const anyOpen = Object.values(this.states).some(v => v === true)
      if (!anyOpen) {
        document.body.style.overflow = ''
      }
    }
  },
  getters: {
    getState: (state) => (key) => {
      let validKey = key

      if (!/^[a-zA-Z0-9-]+$/.test(validKey)) {
        validKey = key.replace(/[^a-zA-Z0-9-]/g, '-').toLowerCase()
      }

      return (state.states.hasOwnProperty(validKey) && state.states[validKey]) || false
    }
  }
})
