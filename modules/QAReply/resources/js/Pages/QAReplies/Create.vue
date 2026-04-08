<script setup>
import NoDataFound from '@/Components/Dashboard/NoDataFound.vue'
import InputFieldError from '@/Components/InputFieldError.vue'
import SpinnerBtn from '@/Components/Dashboard/SpinnerBtn.vue'

import UserLayout from '@/Layouts/User/UserLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import SelectField from '@/Components/Forms/SelectField.vue'
import InputField from '@/Components/Forms/InputField.vue'

defineOptions({ layout: UserLayout })

const props = defineProps({
  qaReply: Object,
  templates: Array
})

const isEditing = computed(() => !!props.qaReply?.id)

const form = useForm({
  title: props.qaReply?.title || '',
  module: props.qaReply?.module || '',
  items: props.qaReply?.items || [
    {
      key: '',
      value: ''
    }
  ]
})

const defaultExtraData = {
  key: '',
  type: 'text',
  value: ''
}

const addItem = () => {
  form.items.push({ ...defaultExtraData })
}

const removeItem = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
  }
}

const submit = () => {
  if (isEditing.value) {
    form.patch(`/user/qareply/qareplies/${props.qaReply.id}`)
  } else {
    form.post('/user/qareply/qareplies')
  }
}
</script>

<template>
  <div class="card card-body mb-2">
    <div class="flex items-center justify-between gap-2">
      <InputField label="Title" v-model="form.title" :validationMessage="form.errors.title" />
      <InputField label="Module" v-model="form.module" disabled />
    </div>
  </div>
  <div class="table-responsive whitespace-nowrap rounded-primary">
    <table class="card card-body table shadow-none">
      <thead>
        <tr>
          <th class="w-[50%]">{{ trans('Question') }}</th>
          <th class="w-[50%]">{{ trans('Answer') }}</th>
          <th class="!text-right">
            <button type="button" @click="addItem" class="btn btn-primary">
              <Icon icon="bx:plus" class="text-lg" />
            </button>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in form.items" :key="index">
          <td>
            <input
              type="text"
              class="input"
              v-model="item.key"
              placeholder="Enter question or command or keyword to search"
            />
            <InputFieldError :message="form.errors[`item.${index}.key`]" />
          </td>
          <td class="space-y-2">
            <SelectField
              :placeholder="trans('Answer Type')"
              v-model="item.type"
              :validationMessage="form.errors[`item.${index}.type`]"
              :options="['text', 'template']"
            />
            <InputField
              v-if="item.type === 'text'"
              v-model="item.value"
              placeholder="Enter answer or response of the query"
              :validationMessage="form.errors[`item.${index}.value`]"
            />
            <SelectField
              v-if="item.type === 'template'"
              v-model="item.template_id"
              :validationMessage="form.errors[`item.${index}.template_id`]"
              :options="templates"
            />
          </td>

          <td>
            <div class="flex justify-end">
              <button type="button" @click="removeItem(index)" class="btn btn-danger">
                <Icon icon="bx:x" class="text-lg" />
              </button>
            </div>
          </td>
        </tr>
      </tbody>
      <NoDataFound :for-table="true" v-if="form.items.length < 1" />
    </table>
    <div class="mt-6 flex justify-end">
      <SpinnerBtn
        type="button"
        @click="submit"
        classes="btn btn-primary"
        :processing="form.processing"
        :btn-text="isEditing ? trans('Update') : trans('Create')"
      />
    </div>
  </div>
</template>
