<script setup>
// Icon is globally registered in the application
</script>

<template>
  <div class="flex-grow flex flex-col items-center justify-center bg-[#f8f9fa] dark:bg-dark-900 border-l border-[#e9edef] dark:border-dark-700 p-8 text-center relative select-none">
    <div class="flex flex-col items-center max-w-md">
      <!-- Premium WhatsApp Web style icon/illustration -->
      <div class="relative mb-8 text-[#00a884]">
        <div class="p-6 bg-[#00a884]/10 rounded-full">
          <Icon icon="ri:whatsapp-fill" class="text-8xl" />
        </div>
      </div>

      <h1 class="text-3xl font-light text-[#41525d] dark:text-slate-100 mb-3">
        WhatsApp Business on Web
      </h1>
      
      <p class="text-sm text-[#667781] dark:text-slate-400 leading-relaxed mb-6">
        Grow, organise and manage your business account. Send and receive messages directly from your web dashboard.
      </p>
    </div>

    <!-- Secure Encryption Footer -->
    <div class="absolute bottom-10 flex items-center gap-x-2 text-xs text-[#8696a0] dark:text-slate-500">
      <Icon icon="mdi:lock" class="text-sm" />
      <span>Your personal messages are end-to-end encrypted</span>
    </div>
  </div>
</template>
