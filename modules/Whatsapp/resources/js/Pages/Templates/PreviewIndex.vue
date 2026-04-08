<script setup>
import { defineAsyncComponent } from 'vue'

const props = defineProps({
    message: {
        type: Object,
        required: true
    }
})

const dynamicMessagePreview = () => {
    const messageType = props.message.type
    const componentName = `${messageType[0].toUpperCase()}${messageType.slice(1)}`
    return defineAsyncComponent({
        loader: () => import(`@whatsapp/Pages/Templates/Preview/${componentName}.vue`),
    })
}

</script>

<template>
    <div class="card card-body">
        <component :is="dynamicMessagePreview()" :message="message" />
    </div>
</template>
