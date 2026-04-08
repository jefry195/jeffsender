<script setup>
import {
  computed,
  ref
} from 'vue'
import MultiSelect from '@/Components/Forms/MultiSelect.vue'

import FilterDropdown from '@/Components/Dashboard/FilterDropdown.vue'
import Modal from '@/Components/Dashboard/Modal.vue'
import Paginate from '@/Components/Dashboard/Paginate.vue'
import SpinnerBtn from '@/Components/Dashboard/SpinnerBtn.vue'
import NoDataFound from '@/Components/NoDataFound.vue'
import sharedComposable from '@/Composables/sharedComposable.js'
import UserLayout from '@/Layouts/User/UserLayout.vue'
import { useModalStore } from '@/Store/modalStore'
import { Icon } from '@iconify/vue'
import { router, useForm } from '@inertiajs/vue3'
import { modal as actionModal } from '@/Composables/actionModalComposable'
import toast from '@/Composables/toastComposable'

const modal = useModalStore()

defineOptions({ layout: UserLayout })
const props = defineProps(['groups', 'dialCodes', 'customers', 'platforms'])
const { deleteRow } = sharedComposable()

const importContactForm = useForm({
  csv_file: null,
  group_ids: [],
  platform_id: ''
})

const selectedCustomers = ref([])
const selectedGroups = ref([])

const allCustomersSelected = computed(() => {
  return props.customers.data.length > 0 && selectedCustomers.value.length === props.customers.data.length
})

const toggleSelectAll = () => {
  if (allCustomersSelected.value) {
    selectedCustomers.value = []
  } else {
    selectedCustomers.value = props.customers.data.map(customer => customer.id)
  }
}

const bulkAssignGroupForm = useForm({
  customer_ids: [],
  group_ids: []
})

const bulkDeleteForm = useForm({
  customer_ids: []
})

const importContacts = () => {
  importContactForm.post(route('user.whatsapp.customers.bulk-import'), {
    onSuccess: () => {
      modal.close('importModal')
      importContactForm.reset()
    }
  })
}

const bulkDeleteCustomers = () => {
  if (selectedCustomers.value.length === 0) {
    toast.error('Please select at least one customer to delete.')
    return
  }

  actionModal.initCallback(() => {
    bulkDeleteForm.customer_ids = selectedCustomers.value
    bulkDeleteForm.post(route('user.whatsapp.customers.bulk-delete'), {
      onSuccess: () => {
        selectedCustomers.value = []
        bulkDeleteForm.reset()
      }
    })
  })
}

const bulkAssignGroups = () => {
  if (selectedCustomers.value.length === 0) {
    toast.error('Please select at least one customer to assign groups.')
    return
  }
  if (selectedGroups.value.length === 0) {
    toast.error('Please select at least one group to assign.')
    return
  }

  bulkAssignGroupForm.customer_ids = selectedCustomers.value
  bulkAssignGroupForm.group_ids = selectedGroups.value

  bulkAssignGroupForm.post(route('user.whatsapp.customers.bulk-assign-group'), {
    onSuccess: () => {
      modal.close('bulkAssignGroupModal')
      selectedCustomers.value = []
      selectedGroups.value = []
      bulkAssignGroupForm.reset()
    }
  })
}

const filterOptions = [
  {
    label: 'Name',
    value: 'name'
  },
  {
    label: 'Phone',
    value: 'uuid'
  }
]
const rowCount = ref(new URLSearchParams(window.location.search).get('rows') || '25')

const changeRows = () => {
  const params = new URLSearchParams(window.location.search)
  params.set('rows', rowCount.value)
  params.set('page', 1)
  
  const searchParams = Object.fromEntries(params.entries())
  router.get(route('user.whatsapp.customers.index'), searchParams, { 
    preserveState: true,
    replace: true 
  })
}
</script>

<template>
  <div class="flex flex-wrap justify-between items-center mb-4 gap-4">
    <div class="flex items-center gap-4">
      <FilterDropdown :options="filterOptions" />
      <div class="flex items-center gap-2">
        <label for="rows" class="text-sm font-medium text-slate-500 whitespace-nowrap">{{ trans('Tampilkan') }}</label>
        <select id="rows" v-model="rowCount" @change="changeRows" class="select !py-1.5 !px-3 !w-24">
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
          <option value="500">500</option>
          <option value="all">{{ trans('Semua') }}</option>
        </select>
      </div>
    </div>
    <div class="flex gap-2">
      <button @click="bulkDeleteCustomers" :disabled="selectedCustomers.length === 0" class="btn btn-danger">
        <Icon icon="bx:trash" />
        {{ trans('Bulk Delete') }}
      </button>
      <button @click="modal.open('bulkAssignGroupModal')" :disabled="selectedCustomers.length === 0"
        class="btn btn-primary">
        <Icon icon="bx:group" />
        {{ trans('Bulk Assign Group') }}
      </button>
    </div>
  </div>
  <div class="table-responsive mt-4 w-full">
    <table class="table">
      <thead>
        <tr>
          <th>
            <input type="checkbox" @change="toggleSelectAll" :checked="allCustomersSelected" />
          </th>
          <th>{{ trans('Name') }}</th>
          <th>
            {{ trans('Phone') }}
          </th>
          <th>{{ trans('Groups') }}</th>
          <th class="!text-right">
            {{ trans('Action') }}
          </th>
        </tr>
      </thead>
      <tbody v-if="customers.data.length" class="tbody">
        <tr v-for="(customer, index) in customers.data" :key="index">
          <td>
            <input type="checkbox" :value="customer.id" v-model="selectedCustomers" />
          </td>
          <td>
            <div class="flex items-center gap-2">
              <img :src="customer.picture ?? 'https://ui-avatars.com/api/?name=' + customer.name"
                class="h-8 w-8 rounded-full" />
              <span>{{ customer.name }}</span>
            </div>
          </td>
          <td>
            {{ customer.uuid }}
          </td>
          <td>
            {{customer.groups.map((g) => g.name).join(', ') || 'N/A'}}
          </td>
          <td>
            <div class="flex justify-end">
              <div class="dropdown" data-placement="bottom-start">
                <div class="dropdown-toggle">
                  <Icon class="h-5 text-3xl text-slate-400" icon="bx:dots-vertical-rounded" />
                </div>
                <div class="dropdown-content w-56">
                  <ul class="dropdown-list">
                    <li class="dropdown-list-item">
                      <Link :href="route('user.whatsapp.customers.edit', customer)" class="dropdown-link">
                      <Icon icon="bx:edit" />
                      {{ trans('Edit') }}
                      </Link>
                    </li>
                    <li class="dropdown-list-item">
                      <button class="dropdown-link delete-confirm"
                        @click="deleteRow(route('user.whatsapp.customers.destroy', customer.id))">
                        <Icon icon="bx:trash" />
                        {{ trans('Delete') }}
                      </button>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
      <NoDataFound :forTable="true" v-else />
    </table>
    <div class="w-full">
      <Paginate v-if="customers.data.length" :links="customers.links" />
    </div>
  </div>

  <Modal state="importModal" :header-state="true" header-title="Import customers">
    <form @submit.prevent="importContacts">
      <div class="w-full">
        <label class="label mb-1">{{ trans('Select CSV') }}
          <a href="/assets/whatsapp-customers-sample.csv" download="">{{
            trans('(Download Sample)')
            }}</a></label>
        <input type="file" accept=".csv" @change="($event) => (importContactForm.csv_file = $event.target.files[0])"
          class="input" />

        <small class="text-red-600" v-if="importContactForm.errors.csv_file">{{
          importContactForm.errors.csv_file
          }}</small>
      </div>

      <div class="w-full">
        <label class="label mb-1">{{ trans('Platforms') }}</label>
        <select v-model="importContactForm.platform_id" class="select">
          <option value="">{{ trans('Select Platform') }}</option>
          <option v-for="(platform, index) in platforms" :key="index" :value="platform.id">
            {{ platform.name }}
          </option>
        </select>

        <small class="text-red-600" v-if="importContactForm.errors.platform_id">{{
          importContactForm.errors.platform_id
          }}</small>

        <small class="text-red-600" v-if="importContactForm.errors.group_id">{{
          importContactForm.errors.group_id
          }}</small>
      </div>
      <div class="mt-4">
        <SpinnerBtn classes="btn btn-primary w-full" :processing="importContactForm.processing">
          {{ trans('Import') }}
        </SpinnerBtn>
      </div>
    </form>
  </Modal>

  <Modal state="bulkAssignGroupModal" :header-state="true" header-title="Bulk Assign Groups">
    <form @submit.prevent="bulkAssignGroups">
      <div class="w-full">
        <MultiSelect label="Groups" valueProp="id" input-label="name" :options="groups" v-model="selectedGroups"
          placeholder="Select groups" :validationMessage="bulkAssignGroupForm.errors?.group_ids" />
      </div>
      <div class="mt-4">
        <SpinnerBtn classes="btn btn-primary w-full" :processing="bulkAssignGroupForm.processing">
          {{ trans('Assign Groups') }}
        </SpinnerBtn>
      </div>
    </form>
  </Modal>
</template>