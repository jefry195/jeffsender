<script setup>
import { defineAsyncComponent } from 'vue'

const props = defineProps({
    type: {
        type: String,
        required: true
    },
    body: {
        type: Object,
        required: true
    }
})

const dynamicMessagePreview = () => {
    const messageType = props.type
    const componentName = `${messageType[0].toUpperCase()}${messageType.slice(1)}`
    return defineAsyncComponent({
        loader: () => import(`@whatsapp/Pages/Templates/Form/${componentName}.vue`),
    })
}

</script>

<template>
    <component :is="dynamicMessagePreview()" :body="body" />
</template>
