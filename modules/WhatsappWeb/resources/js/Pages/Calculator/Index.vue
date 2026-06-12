<script setup>
import { ref, computed, watch } from 'vue'
import UserLayout from '@/Layouts/User/UserLayout.vue'

defineOptions({ layout: UserLayout })

const props = defineProps(['productTypes', 'bahanOptions', 'laminasiOptions'])

// ─── Form State ───────────────────────────────────────────────
const form = ref({
  type: '',
  qty: 1000,
  material: '',
  laminasi: 'none',
  dimensions: {},
})

// Dimension fields per product type
const dimensionFields = {
  lunchBox: [
    { key: 'p_atas', label: 'Panjang Atas (cm)', placeholder: '18' },
    { key: 'l_atas', label: 'Lebar Atas (cm)', placeholder: '10.5' },
    { key: 'p_bawah', label: 'Panjang Bawah (cm)', placeholder: '16' },
    { key: 'l_bawah', label: 'Lebar Bawah (cm)', placeholder: '8.5' },
    { key: 't', label: 'Tinggi (cm)', placeholder: '5' },
    { key: 'tutup', label: 'Tutup Atas (cm)', placeholder: '2.5' },
  ],
  riceBox: [
    { key: 'p_atas', label: 'Panjang Atas (cm)', placeholder: '18' },
    { key: 'l_atas', label: 'Lebar Atas (cm)', placeholder: '10.5' },
    { key: 'p_bawah', label: 'Panjang Bawah (cm)', placeholder: '16' },
    { key: 'l_bawah', label: 'Lebar Bawah (cm)', placeholder: '8.5' },
    { key: 't', label: 'Tinggi (cm)', placeholder: '5' },
    { key: 'tutup', label: 'Tutup Atas (cm)', placeholder: '2.5' },
  ],
  dineIn: [
    { key: 'p', label: 'Panjang (cm)', placeholder: '18' },
    { key: 'l', label: 'Lebar (cm)', placeholder: '10' },
    { key: 't', label: 'Tinggi (cm)', placeholder: '4' },
  ],
  kotakTutupTerpisah: [
    { key: 'p_bawah', label: 'P Bawah (cm)', placeholder: '23' },
    { key: 'l_bawah', label: 'L Bawah (cm)', placeholder: '23' },
    { key: 't_bawah', label: 'T Bawah (cm)', placeholder: '7.5' },
    { key: 'p_atas', label: 'P Atas (cm)', placeholder: '18' },
    { key: 'l_atas', label: 'L Atas (cm)', placeholder: '18' },
    { key: 't_atas', label: 'T Atas (cm)', placeholder: '3' },
  ],
  kotakSambung: [
    { key: 'p', label: 'Panjang (cm)', placeholder: '14' },
    { key: 'l', label: 'Lebar (cm)', placeholder: '10.5' },
    { key: 't', label: 'Tinggi (cm)', placeholder: '6.5' },
    { key: 'tutup', label: 'Tutup (cm)', placeholder: '3.5' },
  ],
  straightTuckEnd: [
    { key: 'p', label: 'Panjang (cm)', placeholder: '10' },
    { key: 'l', label: 'Lebar (cm)', placeholder: '5' },
    { key: 't', label: 'Tinggi (cm)', placeholder: '15' },
    { key: 'lem', label: 'Kuping Lem (cm)', placeholder: '1.5' },
  ],
  kebab: [
    { key: 'p', label: 'Panjang (cm)', placeholder: '26' },
    { key: 'l', label: 'Lebar (cm)', placeholder: '9' },
    { key: 'lem', label: 'Lidah Lem (cm)', placeholder: '1.5' },
  ],
  kotakMug: [
    { key: 'p', label: 'Panjang (cm)', placeholder: '8' },
    { key: 'l', label: 'Lebar (cm)', placeholder: '11' },
    { key: 't', label: 'Tinggi (cm)', placeholder: '10' },
    { key: 'lem', label: 'Lidah Lem (cm)', placeholder: '1.3' },
    { key: 'kunci_bawah', label: 'Kunci Bawah (cm)', placeholder: '8' },
  ],
  burger: [
    { key: 'p', label: 'Panjang (cm)', placeholder: '10' },
    { key: 'l', label: 'Lebar (cm)', placeholder: '10' },
    { key: 't_bawah', label: 'T Bawah (cm)', placeholder: '4' },
    { key: 't_krkn', label: 'T Karkasan (cm)', placeholder: '5' },
    { key: 't_tutup', label: 'T Tutup (cm)', placeholder: '5' },
  ],
}

const currentFields = computed(() => dimensionFields[form.value.type] || [])

// Reset dimensions when type changes
watch(() => form.value.type, () => {
  form.value.dimensions = {}
})

// ─── Calculation ───────────────────────────────────────────────
const loading = ref(false)
const result  = ref(null)
const error   = ref(null)

const canCalculate = computed(() => {
  if (!form.value.type || !form.value.material || !form.value.qty) return false
  const fields = currentFields.value
  return fields.every(f => form.value.dimensions[f.key] && parseFloat(form.value.dimensions[f.key]) > 0)
})

const calculate = async () => {
  if (!canCalculate.value) return
  loading.value = true
  error.value   = null
  result.value  = null

  try {
    const res = await axios.post(route('user.whatsapp-web.calculator.calculate'), {
      type:       form.value.type,
      qty:        form.value.qty,
      material:   form.value.material,
      laminasi:   form.value.laminasi,
      dimensions: form.value.dimensions,
    })
    result.value = res.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Terjadi kesalahan saat menghitung.'
  } finally {
    loading.value = false
  }
}

// ─── Helpers ───────────────────────────────────────────────────
const formatRp = (val) => {
  if (!val && val !== 0) return '-'
  return 'Rp ' + Number(val).toLocaleString('id-ID')
}

const productIcon = (type) => {
  const icons = {
    lunchBox: '🥡', riceBox: '🍱', dineIn: '🥗', kotakTutupTerpisah: '📦',
    kotakSambung: '📫', straightTuckEnd: '📮', kebab: '🌯', kotakMug: '☕', burger: '🍔',
  }
  return icons[type] || '📦'
}

const materialIcon = (key) => {
  if (key.includes('kraft')) return '🟫'
  if (key.includes('ivory')) return '⬜'
  if (key.includes('duplex')) return '🔲'
  if (key.includes('ap310')) return '✨'
  return '📄'
}

const flatSizeSingle = computed(() => {
  if (!result.value?.flat_size) return null
  const fs = result.value.flat_size
  if (fs.bawah) return null // kotak tutup terpisah
  return fs
})

const flatSizeSeparate = computed(() => {
  if (!result.value?.flat_size) return null
  const fs = result.value.flat_size
  return fs.bawah ? fs : null
})
</script>

<template>
  <div class="space-y-5 p-4 sm:p-6">

    <!-- ── Header ── -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">📦 Kalkulator Kemasan Box Custom</h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Hitung estimasi harga cetak kemasan box custom full color Dooren'z</p>
      </div>
    </div>

    <!-- ── Info Banner ── -->
    <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
      <span class="mt-0.5 text-lg">⚠️</span>
      <p class="text-sm text-amber-700 dark:text-amber-300">
        <strong>Catatan:</strong> Harga yang tampil merupakan <strong>harga estimasi saja (tidak mengikat)</strong>
        dan dapat berubah sewaktu-waktu mengikuti harga bahan baku di pasar.
        Admin akan melakukan <strong>pengecekan ketersediaan stok</strong> terlebih dahulu sebelum pesanan diproses.
      </p>
    </div>

    <!-- ── Main Grid ── -->
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">

      <!-- ═══════════ KIRI: Form ═══════════ -->
      <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-dark-700 dark:bg-dark-900 lg:col-span-5">

        <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 20h16a2 2 0 002-2V8a2 2 0 00-2-2h-5l-2-3H9L7 6H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
          Parameter Perhitungan
        </h2>

        <!-- 1. Tipe Produk -->
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">
            📦 Jenis Kemasan <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-3 gap-1.5">
            <button
              v-for="(name, key) in productTypes"
              :key="key"
              @click="form.type = key"
              class="flex flex-col items-center gap-1 rounded-lg border px-2 py-2 text-center text-xs font-medium transition-all"
              :class="form.type === key
                ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm dark:bg-primary-900/30 dark:text-primary-300'
                : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-primary-300 hover:bg-primary-50/50 dark:border-dark-600 dark:bg-dark-800 dark:text-gray-400'"
            >
              <span class="text-lg">{{ productIcon(key) }}</span>
              <span class="leading-tight">{{ name }}</span>
            </button>
          </div>
        </div>

        <!-- 2. Dimensi -->
        <div v-if="form.type" class="space-y-2">
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">
            📏 Ukuran / Dimensi <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-2 gap-2">
            <div v-for="field in currentFields" :key="field.key">
              <label class="mb-0.5 block text-[11px] text-gray-500 dark:text-gray-400">{{ field.label }}</label>
              <input
                v-model="form.dimensions[field.key]"
                type="number"
                step="0.1"
                min="0.1"
                :placeholder="field.placeholder"
                class="input input-sm w-full text-sm"
              />
            </div>
          </div>
        </div>

        <!-- 3. Jumlah Cetak -->
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">
            🔢 Jumlah Cetak (pcs) <span class="text-red-500">*</span>
          </label>
          <input
            v-model.number="form.qty"
            type="number"
            min="1"
            step="100"
            class="input w-full"
            placeholder="Min. 500 pcs"
          />
          <p class="mt-1 text-[11px] text-gray-400">Minimal order: 500 pcs (offset) atau 50 pcs (digital)</p>
        </div>

        <!-- 4. Bahan Kertas -->
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">
            📄 Bahan Kertas <span class="text-red-500">*</span>
          </label>
          <div class="space-y-1.5">
            <button
              v-for="(name, key) in bahanOptions"
              :key="key"
              @click="form.material = key"
              class="flex w-full items-center gap-3 rounded-lg border px-3 py-2 text-left text-sm transition-all"
              :class="form.material === key
                ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-primary-300 dark:border-dark-600 dark:bg-dark-800 dark:text-gray-400'"
            >
              <span>{{ materialIcon(key) }}</span>
              <span class="flex-1 font-medium">{{ name }}</span>
              <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                :class="key.includes('_dig') ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-200 text-gray-600 dark:bg-dark-700 dark:text-gray-400'"
              >
                {{ key.includes('_dig') ? 'Digital' : 'Offset' }}
              </span>
            </button>
          </div>
        </div>

        <!-- 5. Laminasi -->
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">
            ✨ Laminasi
          </label>
          <div class="grid grid-cols-3 gap-1.5">
            <button
              v-for="(name, key) in laminasiOptions"
              :key="key"
              @click="form.laminasi = key"
              class="rounded-lg border px-2 py-2 text-xs font-medium transition-all"
              :class="form.laminasi === key
                ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-primary-300 dark:border-dark-600 dark:bg-dark-800 dark:text-gray-400'"
            >
              {{ name }}
            </button>
          </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
          ⚠️ {{ error }}
        </div>

        <!-- Hitung Button -->
        <button
          @click="calculate"
          :disabled="!canCalculate || loading"
          class="btn btn-primary flex w-full items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          <svg v-if="loading" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          <span>{{ loading ? 'Sedang Menghitung...' : '🧮 Hitung Estimasi Harga' }}</span>
        </button>

        <p v-if="!canCalculate && !loading" class="text-center text-[11px] text-gray-400">
          Lengkapi semua field yang wajib diisi terlebih dahulu
        </p>
      </div>

      <!-- ═══════════ KANAN: Hasil ═══════════ -->
      <div class="lg:col-span-7 space-y-4">

        <!-- Placeholder -->
        <div
          v-if="!result && !loading"
          class="flex min-h-[500px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 text-center dark:border-dark-700 dark:bg-dark-900"
        >
          <div class="mb-4 text-6xl">📦</div>
          <p class="text-base font-semibold text-gray-600 dark:text-gray-300">Belum ada hasil perhitungan</p>
          <p class="mt-1 text-sm text-gray-400">Isi form di kiri, lalu klik<br><strong>"🧮 Hitung Estimasi Harga"</strong></p>
        </div>

        <!-- Loading -->
        <div
          v-if="loading"
          class="flex min-h-[500px] flex-col items-center justify-center rounded-xl border border-gray-200 bg-white dark:border-dark-700 dark:bg-dark-900"
        >
          <div class="mb-3 text-5xl animate-bounce">⚙️</div>
          <p class="text-base font-medium text-gray-600 dark:text-gray-300">Menghitung estimasi harga...</p>
        </div>

        <!-- Hasil Kalkulasi -->
        <template v-if="result && !loading">

          <!-- ── Kartu Harga Utama ── -->
          <div class="rounded-xl border border-primary-200 bg-gradient-to-br from-primary-50 to-blue-50 p-6 shadow-sm dark:border-primary-800 dark:from-primary-900/20 dark:to-blue-900/20">
            <div class="mb-4 flex items-center gap-3">
              <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600 text-2xl text-white shadow">
                {{ productIcon(form.type) }}
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Estimasi Harga Jual</p>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ formatRp(result.result?.harga_satuan) }} <span class="text-sm font-normal text-gray-500">/pcs</span>
                </h2>
                <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">Total: <strong>{{ formatRp(result.result?.total_jual) }}</strong> untuk {{ Number(form.qty).toLocaleString('id-ID') }} pcs</p>
              </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-3 gap-3">
              <div class="rounded-lg bg-white/60 p-3 text-center dark:bg-dark-800/60">
                <p class="text-lg font-bold text-gray-800 dark:text-white">{{ result.result?.items_per_plano }}</p>
                <p class="text-[11px] text-gray-500">Muat/Plano</p>
              </div>
              <div class="rounded-lg bg-white/60 p-3 text-center dark:bg-dark-800/60">
                <p class="text-lg font-bold text-gray-800 dark:text-white">{{ Number(result.result?.total_plano).toLocaleString('id-ID') }}</p>
                <p class="text-[11px] text-gray-500">Total Plano</p>
              </div>
              <div class="rounded-lg bg-white/60 p-3 text-center dark:bg-dark-800/60">
                <p class="text-sm font-bold text-gray-800 dark:text-white leading-tight">{{ result.result?.plano_size }}</p>
                <p class="text-[11px] text-gray-500">Ukuran Plano</p>
              </div>
            </div>
          </div>

          <!-- ── Detail Spesifikasi ── -->
          <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-dark-700 dark:bg-dark-900">
            <h3 class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-300">📋 Detail Spesifikasi</h3>
            <dl class="space-y-2 text-sm">
              <div class="flex justify-between border-b border-gray-100 pb-2 dark:border-dark-700">
                <dt class="text-gray-500">Jenis Kemasan</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ productTypes[form.type] }}</dd>
              </div>
              <div class="flex justify-between border-b border-gray-100 pb-2 dark:border-dark-700">
                <dt class="text-gray-500">Bahan Kertas</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ bahanOptions[form.material] }}</dd>
              </div>
              <div class="flex justify-between border-b border-gray-100 pb-2 dark:border-dark-700">
                <dt class="text-gray-500">Laminasi</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ laminasiOptions[form.laminasi] }}</dd>
              </div>
              <div class="flex justify-between border-b border-gray-100 pb-2 dark:border-dark-700">
                <dt class="text-gray-500">Jumlah Cetak</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ Number(form.qty).toLocaleString('id-ID') }} pcs</dd>
              </div>
            </dl>
          </div>

          <!-- ── Bentangan Plano (Flat Size Visualization) ── -->
          <div v-if="flatSizeSingle" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-dark-700 dark:bg-dark-900">
            <h3 class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-300">📐 Ukuran Bentangan Plano (Termasuk Bleed 0.75cm)</h3>
            <div class="flex items-center gap-6">
              <!-- Visual box -->
              <div class="flex-shrink-0">
                <div
                  class="border-2 border-dashed border-primary-400 bg-primary-50 dark:bg-primary-900/20 rounded flex items-center justify-center text-primary-700 dark:text-primary-300 text-xs font-semibold"
                  :style="{
                    width: Math.min(160, Math.max(60, (flatSizeSingle.w / Math.max(flatSizeSingle.w, flatSizeSingle.h)) * 160)) + 'px',
                    height: Math.min(160, Math.max(60, (flatSizeSingle.h / Math.max(flatSizeSingle.w, flatSizeSingle.h)) * 160)) + 'px',
                  }"
                >
                  <div class="text-center">
                    <div>W: {{ Number(flatSizeSingle.w).toFixed(1) }} cm</div>
                    <div>H: {{ Number(flatSizeSingle.h).toFixed(1) }} cm</div>
                  </div>
                </div>
              </div>
              <div class="space-y-2 text-sm">
                <div class="flex gap-6">
                  <div>
                    <p class="text-xs text-gray-500">Lebar (W)</p>
                    <p class="text-xl font-bold text-gray-800 dark:text-white">{{ Number(flatSizeSingle.w).toFixed(2) }} <span class="text-sm font-normal">cm</span></p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Tinggi (H)</p>
                    <p class="text-xl font-bold text-gray-800 dark:text-white">{{ Number(flatSizeSingle.h).toFixed(2) }} <span class="text-sm font-normal">cm</span></p>
                  </div>
                </div>
                <p class="text-xs text-gray-400">Ukuran di atas sudah termasuk bleed 0.75 cm dari setiap sisi.</p>
              </div>
            </div>
          </div>

          <!-- ── Kotak Tutup Terpisah ── -->
          <div v-if="flatSizeSeparate" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-dark-700 dark:bg-dark-900">
            <h3 class="mb-3 text-sm font-bold text-gray-700 dark:text-gray-300">📐 Ukuran Bentangan (Bawah & Atas)</h3>
            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                <p class="mb-2 text-xs font-bold text-blue-700 dark:text-blue-300">Bagian Bawah</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">W: <strong>{{ Number(flatSizeSeparate.bawah.w).toFixed(2) }} cm</strong></p>
                <p class="text-sm text-gray-700 dark:text-gray-300">H: <strong>{{ Number(flatSizeSeparate.bawah.h).toFixed(2) }} cm</strong></p>
              </div>
              <div class="rounded-lg border border-purple-200 bg-purple-50 p-3 dark:border-purple-800 dark:bg-purple-900/20">
                <p class="mb-2 text-xs font-bold text-purple-700 dark:text-purple-300">Bagian Atas (Tutup)</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">W: <strong>{{ Number(flatSizeSeparate.atas.w).toFixed(2) }} cm</strong></p>
                <p class="text-sm text-gray-700 dark:text-gray-300">H: <strong>{{ Number(flatSizeSeparate.atas.h).toFixed(2) }} cm</strong></p>
              </div>
            </div>
          </div>

          <!-- ── Disclaimer ── -->
          <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
            <p class="text-xs text-amber-700 dark:text-amber-300">
              ⚠️ <strong>Catatan Penting:</strong> Harga di atas merupakan <strong>harga estimasi saja (tidak mengikat)</strong>
              dan dapat berubah sewaktu-waktu mengikuti harga bahan baku di pasar.
              Admin kami akan melakukan <strong>pengecekan ketersediaan stok</strong> terlebih dahulu sebelum pesanan Anda diproses.
            </p>
          </div>

        </template>
      </div>
    </div>
  </div>
</template>
