<script setup>
import {
  computed,
  watch
} from 'vue'

import Modal from '@/Components/Dashboard/Modal.vue'
import SpinnerBtn from '@/Components/Dashboard/SpinnerBtn.vue'
import { useModalStore } from '@/Store/modalStore'
import { useForm } from '@inertiajs/vue3'

const modalStore = useModalStore()

const props = defineProps({
  platform: {
    type: Object,
    required: true
  },
  autoReplyServices: {
    type: Array,
    default: []
  },
  hideFields: {
    type: Array,
    default: []
  }
})

const form = useForm({
  module: props.platform.module,
  name: '',
  access_token: '',
  send_auto_reply: false,
  auto_reply_method: '',
  auto_reply_method_name: '',
  auto_reply_dataset: '',
  auto_reply_dataset_name: '',
  send_welcome_message: false,
  welcome_message_template: '',
})

watch(
  () => props.platform.id,
  () => {
    if (props.platform?.uuid) {
      let meta = props.platform.meta ?? {}
      form.name = props.platform.name
      form.access_token = props.platform.access_token
      form.send_auto_reply = meta.send_auto_reply
      form.auto_reply_method = meta.auto_reply_method
      form.auto_reply_method_name = meta.auto_reply_method_name
      form.auto_reply_dataset = meta.auto_reply_dataset
      form.auto_reply_dataset_name = meta.auto_reply_dataset_name
      form.send_welcome_message = meta.send_welcome_message
      form.welcome_message_template = meta.welcome_message_template
    }
  }
)

watch(
  () => form.auto_reply_dataset,
  () => {
    let dataset = selectedAutoReplyService.value.datasets?.find(
      (item) => item.id === form.auto_reply_dataset
    )
    form.auto_reply_dataset_name = dataset?.title ?? dataset?.name ?? 'NA'
  }
)

watch(
  () => form.auto_reply_method,
  () => {
    form.auto_reply_method_name = selectedAutoReplyService.value.title || 'NA'
    if (selectedAutoReplyService.value.module === 'default') {
      form.auto_reply_dataset = ''
    }
  }
)

const submit = () => {
  form.put(route(`user.${props.platform.module}.platforms.update`, props.platform.uuid), {
    onSuccess: () => {
      modalStore.close('platformSettingModal')
    }
  })
}

const selectedAutoReplyService = computed(() => {
  return props.autoReplyServices.find((item) => item.module === form.auto_reply_method) || {}
})

</script>

<template>
  <Modal :header-state="true" header-title="Platform Setting" state="platformSettingModal">
    <form @submit.prevent="submit">
      <slot name="top" />
      <!-- name -->
      <div class="mb-2">
        <label for="name" class="label">{{ trans('Platform Name') }}</label>
        <input type="text" class="input" v-model="form.name" />
      </div>

      <!-- access_token -->
      <div class="mb-2" v-if="!hideFields.includes('access_token')">
        <label for="access_token" class="label">{{ trans('Access Token') }}</label>
        <input type="text" class="input" v-model="form.access_token" />
      </div>

      <div class="mb-2">
        <label>{{ trans('Send auto reply') }}</label>
        <select class="select" v-model="form.send_auto_reply">
          <option :value="true">{{ trans('Yes') }}</option>
          <option :value="false">{{ trans('No') }}</option>
        </select>
        <small>{{ trans('Enable or disable auto reply') }}</small>
      </div>

      <div class="mb-2" v-if="form.send_auto_reply == true">
        <label>{{ trans('Auto Reply Service') }}</label>
        <select class="select" v-model="form.auto_reply_method">
          <option v-for="service in autoReplyServices" :value="service.module" :key="service.module">
            {{ service.title }}
          </option>
        </select>
        <small>{{ trans('How message will be replied') }}</small>
      </div>

      <div class="mb-2" v-if="selectedAutoReplyService.has_datasets">
        <label>{{ trans('Auto Reply Using') }}</label>
        <select class="select" v-model="form.auto_reply_dataset">
          <option v-for="dataset in selectedAutoReplyService.datasets ?? []" :value="dataset.id" :key="dataset.id">
            {{ dataset.title }}
          </option>
        </select>
        <small>{{ trans('The auto reply dataset will be used') }}</small>
      </div>

      <!-- toggle welcome message -->
      <div class="mb-2">
        <label>{{ trans('Send Welcome message') }}</label>
        <select class="select" v-model="form.send_welcome_message">
          <option :value="true">{{ trans('Yes') }}</option>
          <option :value="false">{{ trans('No') }}</option>
        </select>
        <div class="mt-2" v-if="form.send_welcome_message === true">
          <textarea v-model="form.welcome_message_template" class="input" rows="5"></textarea>
          <small>{{ trans('This message will be sent to new users') }}</small>
        </div>
      </div>

      <slot />

      <div class="mt-2 text-end">
        <SpinnerBtn classes="btn btn-primary" :processing="form.processing" :btn-text="trans('Submit')" />
      </div>
    </form>
  </Modal>
</template>
