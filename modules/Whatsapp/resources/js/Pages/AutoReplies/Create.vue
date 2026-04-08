<script setup>
import {
  computed,
  onMounted,
  watch
} from 'vue'

import { get } from 'jquery'

import SpinnerBtn from '@/Components/Dashboard/SpinnerBtn.vue'
import ValidationErrors from '@/Components/Dashboard/ValidationErrors.vue'
import MultiSelect from '@/Components/Forms/MultiSelect.vue'
import SelectField from '@/Components/Forms/SelectField.vue'
import PageHeader from '@/Layouts/Admin/PageHeader.vue'
import BlankLayout from '@/Layouts/BlankLayout.vue'
import { useForm } from '@inertiajs/vue3'
import InteractivePreview from '@whatsapp/Components/Preview/InteractiveMessage.vue'
import ShortCodes from '@whatsapp/Components/ShortCodes.vue'
import TemplatePreview from '@whatsapp/Pages/Templates/PreviewIndex.vue'

defineOptions({ layout: BlankLayout })

const props = defineProps(['platforms', 'autoReply', 'templates'])

const form = useForm({
  platform_id: '',
  keywords: [],
  match_type: '',
  message_type: 'text',
  message_template: undefined,
  template_id: undefined,
  meta: {},
  status: 'active',
  _method: 'POST'
})

const isEditing = !!props.autoReply?.id

onMounted(() => {
  if (isEditing) {
    form.platform_id = props.autoReply.platform_id
    form.keywords = props.autoReply.keywords ?? []
    form.match_type = props.autoReply.match_type
    form.message_type = props.autoReply.message_type
    form.message_template = props.autoReply.message_template ?? ''
    form.template_id = props.autoReply.template_id
    form.meta = props.autoReply.meta
    form.status = props.autoReply.status
    form._method = 'PUT'
  }
})

const handleFormSubmit = () => {
  let url = route('user.whatsapp.auto-replies.store')

  if (isEditing) {
    url = route('user.whatsapp.auto-replies.update', props.autoReply.id)
  }

  form.post(url)
}


const filteredTemplates = computed(() => {
  return props.templates.filter((template) => [form.platform_id, null].includes(template.platform_id)) || []
})

const activeTemplate = computed(() => {
  return filteredTemplates.value.find((t) => t.id == form.template_id) || {}
})

watch(
  () => form.template_id,
  (newValue) => {
    if (!newValue) return
    form.meta = filteredTemplates.value.find((t) => t.id == form.template_id)?.meta || {}
  }
)

const getDynamicMessage = computed(() => {
  if (form.message_type == 'template') {
    return {
      type: activeTemplate.value.type,
      body: activeTemplate.value.meta || {}
    }
  }

  return {
    type: 'text',
    body: {
      text: form.message_template
    }
  }
})

</script>
<template>
  <main class="p-4 sm:p-6">
    <PageHeader />

    <ValidationErrors />
    <form @submit.prevent="handleFormSubmit">
      <div class="grid grid-cols-3 gap-4">
        <div class="card card-body ">
          <SelectField label="Select A Device" v-model="form.platform_id" placeholder="SELECT"
            :validationMessage="form.errors?.platform_id" :options="platforms" />

          <MultiSelect label="Keywords" v-model="form.keywords" placeholder="enter keywords"
            :validationMessage="form.errors?.keywords" :options="form.keywords" />

          <SelectField label="Message Type" v-model="form.message_type" placeholder="SELECT"
            :validationMessage="form.errors?.message_type" :options="['text', 'template']" />

          <SelectField v-if="form.message_type != 'text'" label="Select a Template" v-model="form.template_id"
            placeholder="SELECT" :validationMessage="form.errors?.template_id" :options="filteredTemplates" />

          <SelectField label="Status" v-model="form.status" placeholder="SELECT"
            :validationMessage="form.errors?.status" :options="['active', 'inactive']" />
        </div>
        <div class="card card-body ">

          <div class="flex gap-4">
            <div v-if="form.message_type == 'text'" class="w-full">
              <textarea class=" input" v-model="form.message_template" rows="5"></textarea>
              <div v-if="form.errors?.message_template" class="text-red-500">
                {{ form.errors?.message_template }}
              </div>
              <ShortCodes v-model="form.message_template" />
            </div>
          </div>

        </div>
        <div>
          <InteractivePreview v-if="form.message_type == 'template' && activeTemplate.type == 'interactive'"
            :components="form.meta" />
          <TemplatePreview v-else-if="getDynamicMessage?.type" :message="getDynamicMessage" />
          <div class="mt-4 flex justify-end">
            <SpinnerBtn :processing="form.processing" :btn-text="isEditing ? 'Save Changes' : 'Create Auto Reply'" />
          </div>
        </div>
      </div>
    </form>
  </main>
</template>
