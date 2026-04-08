<script setup>
import TextInput from '@/Components/Dashboard/TextInput.vue'
import AssetInput from '@/Components/Forms/AssetInput.vue'

const props = defineProps(['body'])
</script>

<template>
    <div class="space-y-2">
        <AssetInput label="Video" type="video" v-model="body.link" />
        <TextInput v-model="body.caption" label="Caption" placeholder="Caption" />
    </div>
</template>
