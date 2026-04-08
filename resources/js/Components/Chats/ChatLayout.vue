<script setup>
import UserLayout from '@/Layouts/User/UserLayout.vue'

import LeftSidebar from './LeftSidebar.vue'

defineOptions({ layout: UserLayout })
</script>

<template>
  <div class="relative flex h-[calc(100vh-110px)] w-full gap-x-3 overflow-hidden">
    <LeftSidebar />
    <slot />
  </div>
</template>
