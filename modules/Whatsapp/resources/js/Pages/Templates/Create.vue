<script setup>
import {
  computed,
  watch
} from 'vue'

import AssetModal from '@/Components/Dashboard/AssetModal.vue'
import SelectField from '@/Components/Forms/SelectField.vue'
import PageHeader from '@/Layouts/Admin/PageHeader.vue'
import BlankLayout from '@/Layouts/BlankLayout.vue'
import { useForm } from '@inertiajs/vue3'
import InputField from '@whatsapp/Components/InputField.vue'
import InteractiveCreateForm from '@whatsapp/Components/Interactive/CreateForm.vue'
import InteractivePreview from '@whatsapp/Components/Preview/InteractiveMessage.vue'
import MessageForm from '@whatsapp/Pages/Templates/FormIndex.vue'
import MessagePreview from '@whatsapp/Pages/Templates/PreviewIndex.vue'

const props = defineProps(['template'])

defineOptions({ layout: BlankLayout })

const randomId = () => {
  return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15)
}

const generalMessageTypes = [
  'text',
  'image',
  'audio',
  'video',
  'document',
  'location',
  'contact',
  'interactive'
]

const messageTemplates = {
  text: {
    preview_url: false,
    body: ''
  },

  image: {
    caption: '',
    link: ''
  },

  audio: {
    link: ''
  },

  video: {
    caption: '',
    link: ''
  },

  document: {
    caption: '',
    link: ''
  },
  location: {
    longitude: '',
    latitude: '',
    name: '',
    address: ''
  },

  contact: {
    addresses: [
      {
        street: 'STREET',
        city: 'CITY',
        state: 'STATE',
        zip: 'ZIP',
        country: 'COUNTRY',
        country_code: 'COUNTRY_CODE',
        type: 'HOME'
      }
    ],
    birthday: 'YEAR_MONTH_DAY',
    emails: [
      {
        email: 'EMAIL',
        type: 'WORK'
      },
      {
        email: 'EMAIL',
        type: 'HOME'
      }
    ],
    name: {
      formatted_name: 'NAME',
      first_name: 'FIRST_NAME',
      last_name: 'LAST_NAME',
      middle_name: 'MIDDLE_NAME',
      suffix: 'SUFFIX',
      prefix: 'PREFIX'
    },
    org: {
      company: 'COMPANY',
      department: 'DEPARTMENT',
      title: 'TITLE'
    },
    phones: [
      {
        phone: 'PHONE_NUMBER',
        type: 'HOME'
      },
      {
        phone: 'PHONE_NUMBER',
        type: 'WORK',
        wa_id: 'PHONE_OR_WA_ID'
      }
    ],
    urls: [
      {
        url: 'URL',
        type: 'WORK'
      },
      {
        url: 'URL',
        type: 'HOME'
      }
    ]
  }
}

const interactiveTypes = ['button', 'cta_url', 'product', 'list', 'product_list', 'catalog_message']

const interactionAction = {
  button: {
    buttons: [
      {
        type: 'reply',
        reply: {
          id: randomId(),
          title: ''
        }
      }
    ]
  },

  cta_url: {
    name: 'cta_url',
    parameters: {
      display_text: 'Visit our website',
      url: 'https://example.com'
    }
  },

  list: {
    button: 'BUTTON_TEXT',
    sections: [
      {
        title: '',
        rows: [
          {
            id: randomId(),
            title: '',
            description: ''
          }
        ]
      }
    ]
  },

  catalog_message: {
    name: 'catalog_message',
    parameters: {
      thumbnail_product_retailer_id: ''
    }
  },

  product: {
    catalog_id: '',
    product_retailer_id: ''
  },

  product_list: {
    catalog_id: '',
    sections: [
      {
        title: '',
        product_items: [
          {
            product_retailer_id: ''
          }
        ]
      }
    ]
  }
}

const interactiveMeta = {
  type: 'button',
  header: {
    type: 'text',
    text: ''
  },

  body: {
    text: ''
  },

  footer: {
    text: ''
  },
  action: interactionAction.button
}

const form = useForm({
  name: props.template?.name ?? '',
  message_type: props.template?.type ?? 'text',
  meta: props.template?.meta ?? messageTemplates.text,
  status: props.template?.status ?? 'active',
  _method: props.template ? 'put' : 'post'
})

const whenMessageTypeChanged = (value) => {
  if (value == 'interactive') {
    form.meta = interactiveMeta
  } else {
    form.meta = messageTemplates[value]
  }
}

const whenInteractiveMessageTypeChanged = (value) => {
  if (value) {
    form.meta.action = {
      ...interactionAction[value]
    }
  }
}

watch(() => form.message_type, whenMessageTypeChanged)
watch(() => form.meta?.type, whenInteractiveMessageTypeChanged)

const handleFormSubmit = () => {
  let url = '/user/whatsapp/templates'
  if (props.template) {
    url = `/user/whatsapp/templates/${props.template.id}`
  }
  form.post(url)
}

const getDynamicMessage = computed(() => {
  return {
    type: form.message_type,
    body: form.meta
  }
})
</script>
<template>
  <AssetModal />
  <main class="p-4 sm:p-6">
    <PageHeader />

    <div class="mt-4 grid grid-cols-2 place-items-start gap-6 md:grid-cols-12">
      <div class="col-span-full w-full md:col-span-4 lg:col-span-2">
        <div
          class="mb-2 rounded border-b bg-slate-100 px-2 py-2 text-center capitalize dark:border-dark-700 dark:bg-dark-800">
          {{ trans('Template') }}
        </div>

        <div class="card flex flex-col gap-4 p-3">
          <InputField label="Name" v-model="form.name" placeholder="Template name"
            :validationMessage="form.errors.name" />

          <SelectField label="Message Type" v-model="form.message_type" placeholder="SELECT"
            :options="generalMessageTypes" />

          <SelectField label="Status" v-model="form.status" placeholder="SELECT" :options="['active', 'inactive']" />

          <select v-if="form.message_type == 'interactive'" class="select" v-model="form.meta.type">
            <option value="button">{{ trans('Button') }}</option>
            <option value="cta_url">{{ trans('Call to Action URL') }}</option>
            <option value="product">{{ trans('Product') }}</option>
            <option value="list">{{ trans('List') }}</option>
            <option value="product_list">{{ trans('Product List') }}</option>
            <option value="catalog_message">{{ trans('Catalog Message') }}</option>
          </select>
        </div>
      </div>
      <div class="col-span-full w-full md:col-span-8 lg:col-span-10">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-5">
          <div class="md:col-span-3">
            <div v-if="form.message_type !== 'interactive'"
              class="mb-2 rounded border-b bg-slate-100 px-2 py-2 text-center capitalize dark:border-dark-700 dark:bg-dark-800">
              {{ form.message_type }} {{ trans('Message') }}
            </div>

            <!-- interactive -->
            <InteractiveCreateForm v-if="form.message_type == 'interactive'" :meta="form.meta" :errors="form.errors" />
            <MessageForm v-else :type="form.message_type" :body="form.meta" />
          </div>

          <div class="md:col-span-2">
            <div
              class="mb-2 rounded border-b bg-slate-100 px-2 py-2 text-center capitalize dark:border-dark-700 dark:bg-dark-800">
              {{ trans('Preview') }}
            </div>
            <InteractivePreview v-if="form.message_type == 'interactive'" :components="form.meta" />
            <MessagePreview v-else :message="getDynamicMessage" />
            <div class="mt-2 flex justify-end">
              <button @click="handleFormSubmit" class="btn btn-primary">
                {{ trans('Save Template') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>
