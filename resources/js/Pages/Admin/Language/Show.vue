<script setup>
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue'
import SpinnerBtn from '@/Components/Dashboard/SpinnerBtn.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref, watch, onMounted, computed } from 'vue'
import { useModalStore } from '@/Store/modalStore'
import trans from '@/Composables/transComposable'
const modalStore = useModalStore()

defineOptions({ layout: AdminLayout })

const props = defineProps([
  'posts',
  'id',
  'buttons',
  'segments',
  'fileModifiedAt',
  'hasBackup',
  'filters'
])

const form = useForm({
  key: '',
  value: '',
  id: props.id
})

const createKey = () => {
  form.post('/admin/language/addkey', {
    onSuccess: () => {
      form.reset()
      modalStore.close('createModal')
    }
  })
}

const updateForm = useForm({
  values: props.posts.data,
  fileModifiedAt: props.fileModifiedAt
})

const hasUnsavedChanges = ref(false)
const originalValues = ref({})

onMounted(() => {
  originalValues.value = JSON.parse(JSON.stringify(props.posts.data))
})

watch(
  () => updateForm.values,
  () => {
    hasUnsavedChanges.value =
      JSON.stringify(updateForm.values) !== JSON.stringify(originalValues.value)
  },
  { deep: true }
)

const updateLanguage = () => {
  router.patch(
    route('admin.language.update', props.id),
    {
      values: updateForm.values,
      fileModifiedAt: updateForm.fileModifiedAt
    },
    {
      onSuccess: () => {
        hasUnsavedChanges.value = false
        originalValues.value = JSON.parse(JSON.stringify(updateForm.values))
        updateForm.fileModifiedAt = props.fileModifiedAt
      }
    }
  )
}

const filterForm = useForm({
  search: props.filters?.search || '',
  perPage: props.filters?.perPage || 15
})

const goToPage = ref(props.posts.current_page)

watch(
  () => props.posts.current_page,
  (newPage) => {
    goToPage.value = newPage
  }
)

const navigateToPage = () => {
  let page = parseInt(goToPage.value)

  if (isNaN(page) || page < 1) {
    page = 1
  } else if (page > props.posts.last_page) {
    page = props.posts.last_page
  }

  if (page === props.posts.current_page) {
    goToPage.value = page
    return
  }

  router.get(
    props.posts.path,
    { ...pickBy(filterForm.data()), page: page },
    {
      preserveState: true,
      replace: true
    }
  )
}

const pickBy = (obj) => {
  const result = {}
  for (const key in obj) {
    if (obj[key] !== '' && obj[key] !== null && obj[key] !== undefined) {
      result[key] = obj[key]
    }
  }
  return result
}

const debouncedSearch = (() => {
  let timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      router.get(route('admin.language.show', props.id), pickBy(filterForm.data()), {
        preserveState: true,
        replace: true
      })
    }, 300)
  }
})()

// AI Translation with checkboxes
const selectedKeys = ref([])
const isProcessingAi = ref(false)

const selectAllCurrentPage = computed({
  get() {
    const currentPageKeys = Object.keys(props.posts.data)
    return (
      currentPageKeys.length > 0 && currentPageKeys.every((key) => selectedKeys.value.includes(key))
    )
  },
  set(value) {
    if (value) {
      const currentPageKeys = Object.keys(props.posts.data)
      selectedKeys.value = [...new Set([...selectedKeys.value, ...currentPageKeys])]
    } else {
      const currentPageKeys = Object.keys(props.posts.data)
      selectedKeys.value = selectedKeys.value.filter((key) => !currentPageKeys.includes(key))
    }
  }
})

const toggleKeySelection = (key) => {
  const index = selectedKeys.value.indexOf(key)
  if (index > -1) {
    selectedKeys.value.splice(index, 1)
  } else {
    selectedKeys.value.push(key)
  }
}

const selectedCount = computed(() => selectedKeys.value.length)

const updateWithAi = () => {
  modalStore.close('aiConfirmModal')
  isProcessingAi.value = true

  const dataToSend = selectedKeys.value.length > 0 ? { keys: selectedKeys.value.slice(0, 100) } : {}

  router.patch(route('admin.language.ai.update', props.id), dataToSend, {
    onFinish: () => {
      isProcessingAi.value = false
      selectedKeys.value = []
    }
  })
}

const isReverting = ref(false)

const revertTranslations = () => {
  modalStore.close('revertModal')
  isReverting.value = true
  router.patch(
    route('admin.language.ai.revert', props.id),
    {},
    {
      onFinish: () => {
        isReverting.value = false
        hasUnsavedChanges.value = false
      }
    }
  )
}

watch(
  () => props.posts.data,
  (newData) => {
    updateForm.values = newData
    if (!hasUnsavedChanges.value) {
      originalValues.value = JSON.parse(JSON.stringify(newData))
    }
  },
  { deep: true }
)

const paginationInfo = computed(() => {
  const from = props.posts.from || 0
  const to = props.posts.to || 0
  const total = props.posts.total || 0
  const currentPage = props.posts.current_page || 0
  const lastPage = props.posts.last_page || 0
  return `${trans('Showing')} ${from} ${trans('to')} ${to} ${trans('of')} ${total} ${trans(
    'entries'
  )} (${trans('Page')} ${currentPage} ${trans('of')} ${lastPage})`
})
</script>

<template>
  <!-- Unsaved Changes Warning -->
  <div v-if="hasUnsavedChanges" class="alert alert-warning mb-3">
    <strong>{{ trans('Unsaved Changes') }}</strong>
    <p class="mb-0">
      {{ trans("You have unsaved changes. Don't forget to click 'Save Changes' below.") }}
    </p>
  </div>

  <!-- Search and Per Page Controls -->
  <div class="mb-3 flex items-center justify-between gap-3">
    <div class="flex-1">
      <input
        type="search"
        v-model="filterForm.search"
        @input="debouncedSearch"
        :placeholder="trans('Search by key or value...')"
        class="input max-w-72"
      />
    </div>
    <div class="flex items-center gap-2">
      <label class="label whitespace-nowrap">{{ trans('Per Page') }}</label>
      <select v-model="filterForm.perPage" @change="debouncedSearch" class="select w-24">
        <option :value="15">15</option>
        <option :value="25">25</option>
        <option :value="50">50</option>
        <option :value="100">100</option>
      </select>
    </div>
  </div>

  <form @submit.prevent="updateLanguage" method="post">
    <div class="table-responsive whitespace-nowrap rounded-primary">
      <table class="table">
        <thead>
          <tr>
            <th class="w-1">
              <div class="flex w-8 items-center gap-2">
                <input
                  type="checkbox"
                  :checked="selectAllCurrentPage"
                  @change="selectAllCurrentPage = $event.target.checked"
                  class="checkbox"
                />
                <span v-if="selectedCount > 0">({{ selectedCount }})</span>
              </div>
            </th>
            <th class="w-1/2">{{ trans('Translation Key') }}</th>
            <th class="w-1/2">{{ trans('Translated Value') }}</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="(value, key) in posts.data" :key="key">
            <td>
              <input
                type="checkbox"
                :checked="selectedKeys.includes(key)"
                @change="toggleKeySelection(key)"
                class="checkbox"
              />
            </td>
            <td :title="key" class="whitespace-break-spaces">
              {{ key }}
            </td>
            <td>
              <input type="text" class="input" v-model="updateForm.values[key]" />
            </td>
          </tr>
          <tr v-if="Object.keys(posts.data).length === 0">
            <td colspan="3" class="text-muted text-center">
              {{ trans('No results found.') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Controls -->
    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
      <div class="text-muted text-sm">
        {{ paginationInfo }}
      </div>
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-1">
          <input
            type="number"
            min="1"
            :max="posts.last_page"
            v-model.number="goToPage"
            class="input"
            style="width: 80px"
            :placeholder="trans('Page...')"
          />
          <button type="button" class="btn btn-secondary" @click="navigateToPage">
            {{ trans('Go') }}
          </button>
        </div>
        <Link
          v-if="posts.prev_page_url"
          :href="posts.prev_page_url"
          class="btn btn-secondary"
          :class="{ disabled: !posts.prev_page_url }"
        >
          {{ trans('Previous') }}
        </Link>
        <Link
          v-if="posts.next_page_url"
          :href="posts.next_page_url"
          class="btn btn-secondary"
          :class="{ disabled: !posts.next_page_url }"
        >
          {{ trans('Next') }}
        </Link>
      </div>
    </div>

    <div class="mt-2 flex items-center gap-1">
      <SpinnerBtn
        :btn-text="trans('Save Changes')"
        :processing="updateForm.processing"
        :disabled="!hasUnsavedChanges"
      />
      <span v-if="!hasUnsavedChanges" class="ms-2 text-sm text-green-600">
        {{ trans('All changes saved') }}
      </span>
    </div>
  </form>

  <!-- Add New Key Modal -->
  <Modal
    state="createModal"
    :header-state="true"
    :header-title="trans('Add New Key')"
    :action-btn-text="trans('Create')"
    :action-btn-state="true"
    :action-processing="form.processing"
    @action="createKey"
  >
    <div class="mb-2">
      <label>{{ trans('Key') }}</label>
      <input type="text" name="key" v-model="form.key" class="input" required />
      <small class="text-muted d-block mt-1">
        {{ trans('Use letters, numbers, underscores, hyphens, dots, and spaces only.') }}
      </small>
    </div>
    <div class="mb-2">
      <label>{{ trans('Value') }}</label>
      <input type="text" name="value" v-model="form.value" class="input" required />
    </div>
  </Modal>

  <!-- AI Translation Confirmation Modal -->
  <Modal
    state="aiConfirmModal"
    :header-state="true"
    :header-title="trans('Confirm AI Translation')"
    :action-btn-text="trans('Translate')"
    :action-btn-state="true"
    @action="updateWithAi"
  >
    <div class="space-y-3">
      <div v-if="selectedCount > 0" class="alert alert-success">
        You have selected <strong>{{ selectedCount }}</strong>
        {{ selectedCount === 1 ? 'key' : 'keys' }} for translation.
        <span v-if="selectedCount > 100" class="text-warning">
          Only the first 100 keys will be translated.
        </span>
      </div>
      <div v-else class="alert alert-warning">
        No keys selected. Please select up to <strong>100 keys</strong> to translate.
      </div>

      <p>
        {{ trans('This will automatically translate the selected entries using AI.') }}
      </p>
      <div class="alert alert-info">
        {{ trans('A backup will be created automatically. You can revert the changes if needed.') }}
      </div>
      <p class="text-muted small">
        {{ trans('This process may take a few moments depending on the number of keys.') }}
      </p>
    </div>
  </Modal>

  <!-- Revert Confirmation Modal -->
  <Modal
    state="revertModal"
    :header-state="true"
    :header-title="trans('Revert to Backup')"
    :action-btn-text="trans('Revert')"
    :action-btn-state="true"
    @action="revertTranslations"
  >
    <div class="space-y-2">
      <div class="alert alert-danger flex flex-col items-start">
        <strong class="block">{{ trans('Warning') }}</strong>
        <p class="mb-0">
          {{
            trans(
              'This will restore the previous version before AI translation. All current changes will be lost.'
            )
          }}
        </p>
      </div>
      <p class="text-muted small">
        {{ trans('Are you sure you want to continue?') }}
      </p>
    </div>
  </Modal>
</template>