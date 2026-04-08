<script setup>
import Modal from '@/Components/Dashboard/Modal.vue'
import NoDataFound from '@/Components/Dashboard/NoDataFound.vue'
import Paginate from '@/Components/Dashboard/Paginate.vue'
import InputField from '@/Components/Forms/InputField.vue'
import SelectField from '@/Components/Forms/SelectField.vue'
import sharedComposable from '@/Composables/sharedComposable'
import UserLayout from '@/Layouts/User/UserLayout.vue'
import { useModalStore } from '@/Store/modalStore'
import { useForm } from '@inertiajs/vue3'

defineOptions({ layout: UserLayout })

const { deleteRow } = sharedComposable()
const modalStore = useModalStore()
const props = defineProps(['qaReplies', 'modules'])

const form = useForm({
  title: '',
  module: ''
})

const submit = () => {
  form.post('/user/qareply/qareplies', {
    onSuccess: () => {
      form.reset()
      modalStore.close('createModal')
    }
  })
}
</script>

<template>
  <div class="table-responsive w-full">
    <table class="table">
      <thead>
        <tr>
          <th class="w-[5%]">{{ trans('#') }}</th>
          <th>{{ trans('Title') }}</th>
          <th>{{ trans('Module') }}</th>
          <th>{{ trans('Items') }}</th>
          <th class="flex justify-end">{{ trans('Actions') }}</th>
        </tr>
      </thead>
      <tbody v-if="qaReplies.total" class="text-start">
        <tr v-for="(qaReplies, index) in qaReplies.data" :key="qaReplies.id">
          <td>{{ index + 1 }}</td>
          <td>{{ qaReplies.title }}</td>
          <td>{{ qaReplies.module }}</td>
          <td>{{ qaReplies.items_count }}</td>
          <td>
            <div class="flex justify-end">
              <div class="dropdown" data-placement="bottom-start">
                <div class="dropdown-toggle">
                  <Icon class="h-5 text-3xl text-slate-400" icon="bx:dots-vertical-rounded" />
                </div>
                <div class="dropdown-content w-56">
                  <ul class="dropdown-list">
                    <li class="dropdown-list-item">
                      <Link class="dropdown-link" :href="route('user.qareply.qareplies.edit', qaReplies)">
                      <Icon class="h-5 text-3xl text-slate-400" icon="bx:edit" />
                      {{ trans('Edit') }}
                      </Link>
                    </li>
                    <li class="dropdown-list-item">
                      <a class="dropdown-link" @click="deleteRow(route('user.qareply.qareplies.destroy', qaReplies))">
                        <Icon class="h-5 text-3xl text-slate-400" icon="bx:trash" />
                        {{ trans('Delete') }}
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
      <NoDataFound v-else for-table="true" />
    </table>
  </div>

  <Paginate :links="qaReplies.links" />

  <Modal state="createModal" :header-state="true" :header-title="trans('Create New Auto Reply Dataset')"
    :action-btn-text="trans('Create')" :action-btn-state="true" @action="submit">
    <InputField :label="trans('Title')" v-model="form.title" class="mb-2" required :placeholder="trans('Enter Title')"
      :error="form.errors.title" />

    <SelectField :label="trans('Module')" v-model="form.module" :options="modules" class="mb-2" required
      :error="form.errors.module" />
  </Modal>
</template>
