<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { modal } from '@/Composables/actionModalComposable'
import trans from '@/Composables/transComposable'
import { router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import NoDataFound from '@/Components/NoDataFound.vue'
import HollowDotsSpinner from '@/Components/HollowDotsSpinner.vue'
import sharedComposable from '@/Composables/sharedComposable'

import UserLayout from '@/Layouts/User/UserLayout.vue'
import toastComposable from '@/Composables/toastComposable'
import SpinnerBtn from '@/Components/Dashboard/SpinnerBtn.vue'
import Paginate from '@/Components/Dashboard/Paginate.vue'

defineOptions({ layout: UserLayout })

const { deleteRow } = sharedComposable()
const props = defineProps(['record', 'scraped_data', 'groups', 'platforms'])
const places = ref([])
const isLoading = ref(false)
const hasSavedData = ref(false)
const scrapingStatus = ref(props.record.status) // pending|in_progress|completed|failed
const scrapingTotal = ref(0)

let pollTimer = null

// --- Import Modal ---
const showImportModal = ref(false)
const importForm = useForm({
  scraping_id: props.record.id,
  group_ids: [],
  check_wa: false,
  platform_id: null,
})

const submitImport = () => {
  importForm.post(route('user.web-scraping.scrape.import_audience', props.record.id), {
    onSuccess: () => {
      showImportModal.value = false
      importForm.reset('group_ids', 'check_wa', 'platform_id')
    },
  })
}

// --- Background Scraping Logic ---

/**
 * Dispatch scraping ke background job, return langsung tanpa menunggu.
 */
const startScraping = async () => {
  isLoading.value = true
  try {
    const res = await axios.post(route('user.web-scraping.api.scrape.start', props.record.uuid))
    scrapingStatus.value = res.data.status
    toastComposable.success(res.data.message ?? 'Scraping dimulai di latar belakang!')
    startPolling()
  } catch (error) {
    isLoading.value = false
    const msg = error.response?.data?.error ?? error.response?.data?.message ?? 'Gagal memulai scraping'
    toastComposable.danger(typeof msg === 'string' ? msg : JSON.stringify(msg))
  }
}

/**
 * Polling status setiap 4 detik sampai selesai / gagal.
 */
const startPolling = () => {
  if (pollTimer) clearInterval(pollTimer)
  pollTimer = setInterval(checkStatus, 4000)
}

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

const checkStatus = async () => {
  try {
    const res = await axios.get(route('user.web-scraping.api.scrape.status', props.record.uuid))
    scrapingStatus.value = res.data.status
    scrapingTotal.value  = res.data.total ?? 0

    if (res.data.status === 'completed') {
      stopPolling()
      isLoading.value = false
      hasSavedData.value = true
      if (res.data.data?.length) {
        places.value = res.data.data
      }
      toastComposable.success(`Scraping selesai! ${scrapingTotal.value} data berhasil dikumpulkan.`)
    } else if (res.data.status === 'failed') {
      stopPolling()
      isLoading.value = false
      toastComposable.danger('Scraping gagal. Silahkan coba lagi.')
    }
    // status 'in_progress' -> lanjutkan polling
  } catch (e) {
    // Error network sementara, tetap lanjutkan polling
    console.warn('Polling error, will retry...', e)
  }
}

const submit = () => {
  isLoading.value = true
  router.patch(
    route('user.web-scraping.scrape.store_data', props.record.uuid),
    {},
    {
      onSuccess: () => {
        isLoading.value = false
        toastComposable.success('Places added successfully')
      },
      onFinish: () => {
        isLoading.value = false
      }
    }
  )
}

onMounted(() => {
  if (props.scraped_data?.data?.length > 0) {
    // Data sudah ada dari sebelumnya
    places.value = props.scraped_data.data.map((item) => ({ id: item.id, ...item.data }))
    hasSavedData.value = true
  } else if (scrapingStatus.value === 'in_progress') {
    // Sedang berjalan di background (user baru balik ke halaman ini)
    isLoading.value = true
    startPolling()
  } else if (scrapingStatus.value !== 'completed') {
    // Belum pernah dijalankan -> mulai otomatis
    startScraping()
  }
})

onUnmounted(() => {
  // Bersihkan timer saat navigasi ke halaman lain
  stopPolling()
})

const placesData = computed(() => places.value)

// Hitung nomor ponsel valid
const validPhoneCount = computed(() =>
  placesData.value.filter(p => {
    const raw = p.phone_number
    if (!raw) return false
    const cleaned = raw.replace(/\D/g, '')
    return /^(08|628)/.test(cleaned) && cleaned.length >= 10
  }).length
)

// Helper cek nomor ponsel
const isMobile = (phone) => {
  if (!phone) return false
  const cleaned = phone.replace(/\D/g, '')
  return /^(08|628)/.test(cleaned) && cleaned.length >= 10
}

const deleteData = (id) => {
  modal.init(route('user.web-scraping.scrape.destroy_data', id), {
    method: 'delete',
    options: {
      message: trans('You would not be revert it back!'),
      confirm_text: trans('Are you sure?'),
      accept_btn_text: trans('Yes, Sure!'),
      reject_btn_text: trans('No, Cancel')
    },
    callback: () => {
      places.value = places.value.filter((place) => place.id !== id)
    }
  })
}
</script>

<template>
  <!-- Toolbar -->
  <div class="flex flex-wrap justify-between items-center gap-3">
    <div class="flex flex-wrap items-center gap-3">
      <a
        v-if="hasSavedData"
        class="btn btn-primary"
        :href="route('user.web-scraping.scrape.export_data', record.id)"
        target="_blank"
      >
        <Icon icon="bx:export" />
        <span>{{ trans('Export Data') }}</span>
      </a>
      <SpinnerBtn
        v-if="record.status !== 'completed' && hasSavedData"
        type="button"
        icon="bx:refresh"
        @click="submit"
        :processing="isLoading"
        btnText="Sync Number"
      />
      <!-- Tombol Import ke Audience -->
      <button
        v-if="hasSavedData"
        class="btn btn-success"
        @click="showImportModal = true"
      >
        <Icon icon="bx:user-plus" />
        <span>{{ trans('Import ke Audience') }}</span>
      </button>
    </div>
    <!-- Badge ringkasan -->
    <div class="flex items-center gap-2 flex-wrap">
      <span v-if="scrapingStatus === 'in_progress'" class="badge badge-warning text-xs gap-1">
        <span class="loading loading-spinner loading-xs"></span>
        {{ trans('Scraping berjalan di background') }}
        <span v-if="scrapingTotal > 0">· {{ scrapingTotal }} ditemukan</span>
      </span>
      <span v-if="placesData.length" class="badge badge-info text-xs">
        {{ placesData.length }} {{ trans('total hasil') }}
      </span>
      <span v-if="validPhoneCount > 0" class="badge badge-success text-xs">
        {{ validPhoneCount }} {{ trans('nomor HP valid') }}
      </span>
    </div>
  </div>

  <!-- Tabel Data -->
  <div
    class="table-responsive mt-6 w-full"
    v-if="(hasSavedData && !isLoading) || placesData.length"
  >
    <table class="table">
      <thead>
        <tr>
          <th>{{ trans('Nama Bisnis') }}</th>
          <th class="whitespace-nowrap">{{ trans('Telepon / WA') }}</th>
          <th>{{ trans('Email') }}</th>
          <th>{{ trans('Website') }}</th>
          <th>{{ trans('Alamat') }}</th>
          <th>{{ trans('Rating') }}</th>
          <th>{{ trans('Kategori') }}</th>
          <th class="!text-right">{{ trans('Aksi') }}</th>
        </tr>
      </thead>
      <tbody v-if="placesData.length" class="tbody">
        <tr v-for="(place, index) in placesData" :key="index">
          <td class="font-medium">
            <a
              v-if="place.maps_url"
              :href="place.maps_url"
              target="_blank"
              class="link link-primary"
            >{{ place.name }}</a>
            <span v-else>{{ place.name }}</span>
          </td>
          <td>
            <span v-if="!hasSavedData && !place.phone_number" class="text-gray-400 text-xs">
              {{ trans('Hidden') }}
            </span>
            <span
              v-else-if="place.phone_number"
              class="font-mono text-sm"
              :class="{
                'text-green-500': isMobile(place.phone_number),
                'text-orange-400': !isMobile(place.phone_number)
              }"
            >
              {{ place.phone_number }}
              <span
                v-if="!isMobile(place.phone_number)"
                class="badge badge-warning badge-sm ml-1"
              >PSTN</span>
            </span>
            <span v-else class="text-gray-400">—</span>
          </td>
          <td>
            <a
              v-if="place.email"
              :href="'mailto:' + place.email"
              class="link link-info text-xs"
            >{{ place.email }}</a>
            <span v-else class="text-gray-400 text-xs">—</span>
          </td>
          <td>
            <a
              v-if="place.website"
              :href="place.website"
              target="_blank"
              class="link link-secondary text-xs truncate max-w-[120px] block"
            >{{ place.website }}</a>
            <span v-else class="text-gray-400 text-xs">—</span>
          </td>
          <td class="text-xs max-w-[150px]">{{ place.formatted_address || '—' }}</td>
          <td>
            <span v-if="place.rating" class="flex items-center gap-1">
              <Icon icon="bx:star" class="text-yellow-400" />
              {{ place.rating }}
              <span v-if="place.reviews" class="text-xs text-gray-400">({{ place.reviews }})</span>
            </span>
            <span v-else class="text-gray-400 text-xs">—</span>
          </td>
          <td class="capitalize text-xs">{{ place.types?.join(', ') || '—' }}</td>
          <td class="!text-right">
            <button class="btn btn-danger btn-sm" @click="deleteData(place.id)">
              <Icon icon="bx:trash" />
            </button>
          </td>
        </tr>
      </tbody>
      <NoDataFound :forTable="true" v-else />
    </table>
  </div>
  <NoDataFound v-else-if="!isLoading && !hasSavedData && !placesData.length && scrapingStatus !== 'in_progress'" />

  <!-- Loading State (saat scraping berjalan) -->
  <div class="flex items-center justify-center" v-if="isLoading">
    <div class="flex flex-col items-center gap-3 my-8">
      <HollowDotsSpinner class="scale-150" />
      <p class="text-sm text-gray-400 animate-pulse">
        {{ trans('Scraping berjalan di latar belakang...') }}
      </p>
      <p class="text-xs text-gray-500">
        {{ trans('Anda bisa membuka halaman lain. Data akan muncul otomatis saat selesai.') }}
      </p>
      <span v-if="scrapingTotal > 0" class="badge badge-info">
        {{ scrapingTotal }} data sudah terkumpul...
      </span>
    </div>
  </div>

  <Paginate :links="scraped_data.links" />

  <!-- ===================== MODAL IMPORT KE AUDIENCE ===================== -->
  <div
    v-if="showImportModal"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
    @click.self="showImportModal = false"
  >
    <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
      <!-- Header Modal -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-base-200">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center">
            <Icon icon="bx:user-plus" class="text-success text-xl" />
          </div>
          <div>
            <h3 class="font-semibold text-base">{{ trans('Import ke Audience') }}</h3>
            <p class="text-xs text-gray-500">{{ trans('Data dari') }}: <span class="font-medium">{{ record.title }}</span></p>
          </div>
        </div>
        <button class="btn btn-ghost btn-sm btn-circle" @click="showImportModal = false">
          <Icon icon="bx:x" class="text-xl" />
        </button>
      </div>

      <!-- Body Modal -->
      <form @submit.prevent="submitImport" class="px-6 py-5 space-y-5">

        <!-- Info Box -->
        <div class="alert alert-info text-xs py-2">
          <Icon icon="bx:info-circle" />
          <div>
            <p><strong>{{ validPhoneCount }}</strong> dari <strong>{{ placesData.length }}</strong> bisnis memiliki nomor HP valid (62 8xx).</p>
            <p class="mt-0.5 opacity-75">Nomor rumah (021, 031, dll) dan nomor tanpa telepon akan otomatis dilewati.</p>
          </div>
        </div>

        <!-- Pilih Group -->
        <div>
          <label class="label mb-1">
            <span class="label-text font-medium">{{ trans('Masukkan ke Group') }} <span class="text-error">*</span></span>
          </label>
          <select
            v-model="importForm.group_ids"
            multiple
            class="select select-bordered w-full h-32"
            :class="{ 'select-error': importForm.errors.group_ids }"
          >
            <option v-for="g in groups" :key="g.value" :value="g.value">{{ g.label }}</option>
          </select>
          <p v-if="importForm.errors.group_ids" class="text-error text-xs mt-1">
            {{ importForm.errors.group_ids }}
          </p>
          <p class="text-xs text-gray-400 mt-1">Tahan Ctrl/Cmd untuk pilih lebih dari satu group.</p>
        </div>

        <!-- Toggle: Cek WA aktif -->
        <div class="border border-base-200 rounded-xl p-4 space-y-3">
          <label class="flex items-center justify-between cursor-pointer">
            <div>
              <span class="font-medium text-sm">{{ trans('Verifikasi via Number Checker') }}</span>
              <p class="text-xs text-gray-400 mt-0.5">Hanya import nomor yang terkonfirmasi aktif di WhatsApp (lebih lambat)</p>
            </div>
            <input
              type="checkbox"
              class="toggle toggle-success"
              v-model="importForm.check_wa"
            />
          </label>

          <!-- Pilih Platform jika check_wa aktif -->
          <div v-if="importForm.check_wa" class="pt-1">
            <label class="label mb-1">
              <span class="label-text text-sm">{{ trans('Gunakan Perangkat WA') }} <span class="text-error">*</span></span>
            </label>
            <select
              v-model="importForm.platform_id"
              class="select select-bordered w-full"
              :class="{ 'select-error': importForm.errors.platform_id }"
            >
              <option value="">-- Pilih perangkat --</option>
              <option v-for="p in platforms" :key="p.value" :value="p.value">{{ p.label }}</option>
            </select>
            <p v-if="importForm.errors.platform_id" class="text-error text-xs mt-1">
              {{ importForm.errors.platform_id }}
            </p>
            <p class="text-xs text-warning mt-1">
              Proses ini memakan waktu. Gunakan saat data tidak terlalu banyak (max ~200).
            </p>
          </div>
        </div>

        <!-- Tombol Submit -->
        <div class="flex justify-end gap-3 pt-1">
          <button
            type="button"
            class="btn btn-ghost"
            @click="showImportModal = false"
          >
            {{ trans('Batal') }}
          </button>
          <button
            type="submit"
            class="btn btn-success"
            :disabled="importForm.processing || !importForm.group_ids.length || (importForm.check_wa && !importForm.platform_id)"
          >
            <span v-if="importForm.processing" class="loading loading-spinner loading-sm"></span>
            <Icon v-else icon="bx:user-plus" />
            {{ importForm.processing ? trans('Mengimport...') : trans('Import Sekarang') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
