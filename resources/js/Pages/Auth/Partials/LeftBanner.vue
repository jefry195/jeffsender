<script setup>
defineProps(['data'])
</script>

<template>
  <div class="relative hidden overflow-hidden bg-zinc-200 lg:block">
    <div
      class="auth-left-parallax-bg absolute inset-0"
      :style="{
        backgroundImage: `url(${data?.bg_image || '/assets/images/placeholder.svg'})`
      }"
    ></div>

    <div v-if="data?.banner_text" class="absolute left-8 top-8 z-10 p-4">
      <div
        v-html="data.banner_text"
        class="mb-2 text-3xl font-bold text-white drop-shadow-lg"
      ></div>
    </div>
  </div>
</template>

<style scoped>
.auth-left-parallax-bg {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  will-change: transform;
  animation: parallaxMove 5s ease-in-out infinite alternate;
  transform: scale(1.05); /* subtle zoom for depth */
}

@keyframes parallaxMove {
  0% {
    transform: scale(1.05) translateY(-10px);
  }
  100% {
    transform: scale(1.05) translateY(10px);
  }
}
</style>
