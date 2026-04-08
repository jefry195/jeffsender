<script setup>
import {
  nextTick,
  ref,
  watch
} from 'vue'

import QRCode from 'qrcode'

import Modal from '@/Components/Dashboard/Modal.vue'

const props = defineProps({
  platformShareLink: String
})

const qrCanvas = ref(null)
const isLoading = ref(false)
const qrError = ref(null)

// Generate QR code function
const generateQRCode = async () => {
  if (!props.platformShareLink) {
    return
  }

  // Wait for DOM to update
  await nextTick()

  if (!qrCanvas.value) {
    console.error('Canvas element not found')
    return
  }

  isLoading.value = true
  qrError.value = null

  try {

    await QRCode.toCanvas(qrCanvas.value, props.platformShareLink, {
      width: 200,
      margin: 2,
      color: {
        dark: '#000000',
        light: '#FFFFFF'
      }
    })

  } catch (error) {
    console.error('QR generation error:', error)
    qrError.value = 'Failed to generate QR code'
  } finally {
    isLoading.value = false
  }
}

// Watch for changes in platformShareLink
watch(
  () => props.platformShareLink,
  async (newValue) => {
    if (newValue) {
      // Small delay to ensure modal is fully rendered
      await nextTick()
      setTimeout(() => {
        generateQRCode()
      }, 100)
    }
  },
  { immediate: true } // Generate immediately if prop exists
)

// Download QR code
const downloadQR = () => {
  if (!qrCanvas.value) {
    console.error('Canvas not available')
    return
  }

  try {
    const link = document.createElement('a')
    link.download = 'qr-code.png'
    link.href = qrCanvas.value.toDataURL('image/png')
    link.click()
  } catch (error) {
    console.error('Download error:', error)
  }
}
</script>

<template>
  <Modal state="platformQrCodeModal" :close-btn="false" modal-size="w-md">
    <div class="p-2 max-w-md mx-auto text-center space-y-4">
      <h2 class="text-2xl font-semibold">Share QR Code</h2>

      <div class="flex flex-col items-center space-y-3 mt-4">
        <!-- Loading state -->
        <div v-if="isLoading" class="text-gray-600">
          <svg class="animate-spin h-8 w-8 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
          </svg>
          Generating QR code...
        </div>

        <!-- Error state -->
        <div v-else-if="qrError" class="text-red-600 p-4">
          {{ qrError }}
          <button @click="generateQRCode" class="mt-2 text-blue-600 underline">
            Try Again
          </button>
        </div>

        <!-- Canvas for QR code -->
        <div v-show="!isLoading && !qrError" class="border border-gray-200 rounded p-2 bg-white">
          <canvas ref="qrCanvas"></canvas>
        </div>

        <!-- Download button -->
        <button v-if="!isLoading && !qrError" @click="downloadQR"
          class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
          Download QR Code
        </button>
      </div>
    </div>
  </Modal>
</template>