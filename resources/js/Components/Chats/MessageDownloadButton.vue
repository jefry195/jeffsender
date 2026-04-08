<script setup>
import {
    computed,
    ref
} from 'vue'

import DotLoader from '@/Components/DotLoader.vue'
import sharedComposable from '@/Composables/sharedComposable'
import toastComposable from '@/Composables/toastComposable'

const props = defineProps(['message', 'attachment_key', 'attachment_id', 'loader'])

const loading = ref(false)

const { data_get } = sharedComposable()

const downloadAttachments = () => {
    loading.value = true
    axios.post(route('user.messages.download-attachments'), {
        message_uuid: props.message.uuid,
        attachment_id: props.attachment_id,
        attachment_key: props.attachment_key
    }).then((res) => {
        console.log(res.data)
        props.message.body = res.data.body
    }).catch((err) => {
        console.error(err)
        toastComposable.danger(err.response.data.message ?? 'Failed to download attachment')
    }).finally(() => {
        loading.value = false
    })
}

const isAttachmentLoaded = computed(() => data_get(props.message.body, props.attachment_key, null) !== null)
const isOutgoingMessage = computed(() => props.message.direction === 'out')

</script>
<template>
    <div class="relative min-h-2">
        <slot v-if="!loader || isOutgoingMessage || isAttachmentLoaded" />
        <div v-else
            class="border min-h-28  rounded border-zinc-600 aspect-square w-full bg-slate-200 dark:bg-slate-700 flex justify-center items-center">
            <img v-if="message.type === 'image'" src="/assets/images/image-placeholder.jpg" alt="">
            <div @click="downloadAttachments"
                class="absolute group backdrop-blur-[1px] inset-0 cursor-pointer bg-black/20 hover:bg-black/30 transition-colors flex justify-center items-center">
                <DotLoader v-if="loading" />
                <Icon v-else icon="carbon:download"
                    class="text-2xl bg-black/40 shadow text-white size-8 rounded-full p-2 " />
            </div>
        </div>
    </div>
</template>