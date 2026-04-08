<script setup>
import AssetInput from '@/Components/Forms/AssetInput.vue'

const props = defineProps(['body'])
</script>

<template>
    <div class="space-y-2">
        <AssetInput label="Audio" type="audio" v-model="body.link" />
    </div>
</template>
