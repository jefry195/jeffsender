<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import UserLayout from '@/Layouts/User/UserLayout.vue'

defineOptions({ layout: UserLayout })

const props = defineProps(['productTypes', 'bahanOptions', 'laminasiOptions', 'isDoorenz'])

// ─── Form State ───────────────────────────────────────────────
const form = ref({
  type: '',
  qty: 1000,
  material: '',
  laminasi: 'none',
  warna: 'full',
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
  customFlat: [
    { key: 'p', label: 'Panjang Jadi (cm)', placeholder: '21' },
    { key: 'l', label: 'Lebar Jadi (cm)', placeholder: '15' },
  ],
}

const currentFields = computed(() => dimensionFields[form.value.type] || [])

// ─── Presets per product type ─────────────────────────────────
const presets = {
  // Lunch Box
  lunchBox: [
    { label: 'XS  – 10×10 / T:4.5',     values: { p_atas: 10,   l_atas: 10,   p_bawah: 8.5, l_bawah: 8.5, t: 4.5, tutup: 2.5 } },
    { label: 'S   – 13×10 / T:4.5',     values: { p_atas: 13,   l_atas: 10,   p_bawah: 11,  l_bawah: 8,   t: 4.5, tutup: 2.5 } },
    { label: 'M   – 17.5×10.5 / T:5',   values: { p_atas: 17.5, l_atas: 10.5, p_bawah: 16,  l_bawah: 9,   t: 5,   tutup: 2.5 } },
    { label: 'L   – 20×12 / T:5',       values: { p_atas: 20,   l_atas: 12,   p_bawah: 18,  l_bawah: 11,  t: 5,   tutup: 2.5 } },
    { label: 'Corndog – 17×6 / T:5',    values: { p_atas: 17,   l_atas: 6,    p_bawah: 15,  l_bawah: 4,   t: 5,   tutup: 4   } },
  ],
  // Rice Box
  riceBox: [
    { label: 'S  – 9.5×8 / T:8',        values: { p_atas: 9.5, l_atas: 8,   p_bawah: 8, l_bawah: 6.5, t: 8,    tutup: 3   } },
    { label: 'M  – 9.5×8 / T:10',       values: { p_atas: 9.5, l_atas: 8,   p_bawah: 8, l_bawah: 6.5, t: 10,   tutup: 3   } },
    { label: 'L  – 11×9.5 / T:11.5',    values: { p_atas: 11,  l_atas: 9.5, p_bawah: 9, l_bawah: 8,   t: 11.5, tutup: 3.5 } },
  ],
  // Dine In
  dineIn: [
    { label: 'XS – 12×8 / T:2.5',       values: { p: 12, l: 8,    t: 2.5  } },
    { label: 'S  – 11×9 / T:3.5',       values: { p: 11, l: 9,    t: 3.5  } },
    { label: 'M  – 16×9 / T:4',         values: { p: 16, l: 9,    t: 4    } },
    { label: 'L  – 18×10.5 / T:3.25',   values: { p: 18, l: 10.5, t: 3.25 } },
    { label: 'XL – 18×15.5 / T:5',      values: { p: 18, l: 15.5, t: 5    } },
  ],
  // Kotak Tutup Terpisah
  kotakTutupTerpisah: [
    { label: 'S  15×15',   values: { p_bawah: 15, l_bawah: 15, t_bawah: 5,   p_atas: 15.4, l_atas: 15.4, t_atas: 3   } },
    { label: 'M  20×20',   values: { p_bawah: 20, l_bawah: 20, t_bawah: 7,   p_atas: 20.4, l_atas: 20.4, t_atas: 3   } },
    { label: 'L  25×25',   values: { p_bawah: 25, l_bawah: 25, t_bawah: 8,   p_atas: 25.4, l_atas: 25.4, t_atas: 3.5 } },
    { label: 'Kue 23×23',  values: { p_bawah: 23, l_bawah: 23, t_bawah: 7.5, p_atas: 18,   l_atas: 18,   t_atas: 3   } },
  ],
  // Kotak Sambung – Nama Pelanggan Asli
  kotakSambung: [
    { label: 'Pisang Adina / Ayam Setia', values: { p: 16,   l: 10,   t: 5,   tutup: 5   } },
    { label: 'Dapoer TJ',                 values: { p: 18,   l: 16,   t: 7,   tutup: 7   } },
    { label: 'Martabak Leo',              values: { p: 18,   l: 12,   t: 5,   tutup: 2.5 } },
    { label: 'Warung Kediri',             values: { p: 18,   l: 18,   t: 5.5, tutup: 5.5 } },
    { label: 'Pawon Rasa',                values: { p: 19.5, l: 19.5, t: 6,   tutup: 2.5 } },
    { label: 'Ayam Geprek Dapur Chef',    values: { p: 14,   l: 10.5, t: 6.5, tutup: 3.3 } },
    { label: 'Demi Donat Kecil',          values: { p: 18,   l: 10,   t: 8,   tutup: 8   } },
    { label: 'Nats Time',                 values: { p: 16,   l: 12,   t: 7,   tutup: 7   } },
    { label: 'Demi Donat Besar',          values: { p: 29,   l: 20,   t: 6,   tutup: 6   } },
    { label: 'Balok Lumer',               values: { p: 13.5, l: 13.5, t: 4,   tutup: 4   } },
    { label: 'Zaki Donat',                values: { p: 27,   l: 18.5, t: 4.5, tutup: 4.5 } },
  ],
  // Straight Tuck End
  straightTuckEnd: [
    { label: 'Bumbu S – 8×4 / T:10',    values: { p: 8,  l: 4, t: 10, lem: 1.5 } },
    { label: 'Bumbu M – 10×6 / T:14',   values: { p: 10, l: 6, t: 14, lem: 1.5 } },
    { label: 'S – 10×5 / T:15',         values: { p: 10, l: 5, t: 15, lem: 1.5 } },
    { label: 'M – 12×7 / T:18',         values: { p: 12, l: 7, t: 18, lem: 1.5 } },
    { label: 'L – 15×9 / T:22',         values: { p: 15, l: 9, t: 22, lem: 1.5 } },
  ],
  // Kebab
  kebab: [
    { label: 'Mini     – 20×7',          values: { p: 20, l: 7,  lem: 1.5 } },
    { label: 'Standard – 26×9',          values: { p: 26, l: 9,  lem: 1.5 } },
    { label: 'Large    – 30×10',         values: { p: 30, l: 10, lem: 1.5 } },
  ],
  // Kotak Mug
  kotakMug: [
    { label: 'Mug S  – 8×11 / T:10',    values: { p: 8,  l: 11, t: 10, lem: 1.3, kunci_bawah: 8 } },
    { label: 'Mug M  – 9×12 / T:11',    values: { p: 9,  l: 12, t: 11, lem: 1.3, kunci_bawah: 8 } },
    { label: 'Termos – 10×14 / T:12',   values: { p: 10, l: 14, t: 12, lem: 1.3, kunci_bawah: 9 } },
  ],
  // Burger
  burger: [
    { label: 'Slider – 8×8 / T:4',      values: { p: 8,  l: 8,  t_bawah: 3, t_krkn: 4, t_tutup: 4 } },
    { label: 'S      – 10×10 / T:4',    values: { p: 10, l: 10, t_bawah: 4, t_krkn: 5, t_tutup: 5 } },
    { label: 'M      – 12×12 / T:5',    values: { p: 12, l: 12, t_bawah: 5, t_krkn: 6, t_tutup: 6 } },
    { label: 'L      – 13×13 / T:5',    values: { p: 13, l: 13, t_bawah: 5, t_krkn: 7, t_tutup: 7 } },
  ],
  customFlat: [
    { label: 'A3+ (48.3×32.9 cm)', values: { p: 48.3, l: 32.9 } },
    { label: 'A4 (29.7×21 cm)', values: { p: 29.7, l: 21 } },
    { label: 'A5 (21×14.85 cm)', values: { p: 21, l: 14.85 } },
    { label: 'F4 / Folio (33×21.6 cm)', values: { p: 33, l: 21.6 } },
    { label: 'Kartu Nama (9×5.5 cm)', values: { p: 9, l: 5.5 } },
    { label: 'Flyer A5 (21×14.85 cm)', values: { p: 21, l: 14.85 } },
    { label: 'Nota 1/4 Folio (21×10 cm)', values: { p: 21, l: 10 } },
  ],
}

const currentPresets = computed(() => presets[form.value.type] || [])

const applyPreset = (preset) => {
  form.value.dimensions = { ...preset.values }
}

// Reset dimensions when type changes
watch(() => form.value.type, () => {
  form.value.dimensions = {}
})

// Watch material to apply Kraft rules (no laminasi, limit warna to 1 or 2)
watch(() => form.value.material, (newMaterial) => {
  if (newMaterial === 'kraft290_off') {
    form.value.laminasi = 'none'
    if (form.value.warna === 'full') {
      form.value.warna = '1'
    }
  }
})

// Watch minQty to adjust quantity dynamically if it is below minimum
const minQty = computed(() => {
  const isDig = form.value.material?.includes('_dig') || form.value.material?.startsWith('dtf_')
  return isDig ? 50 : 1000
})

watch(minQty, (newMin) => {
  if (form.value.qty < newMin) {
    form.value.qty = newMin
  }
})

// ─── Material Category Tabs ───
const activeCategory = ref('offset')

const filteredBahanOptions = computed(() => {
  const options = {}
  for (const [key, name] of Object.entries(props.bahanOptions || {})) {
    const isDig = key.includes('_dig') || key.startsWith('dtf_')
    if (activeCategory.value === 'digital' && isDig) {
      options[key] = name
    } else if (activeCategory.value === 'offset' && !isDig) {
      options[key] = name
    }
  }
  return options
})

const selectCategory = (cat) => {
  activeCategory.value = cat
  const firstKey = Object.keys(props.bahanOptions || {}).find(key => {
    const isDig = key.includes('_dig') || key.startsWith('dtf_')
    return cat === 'digital' ? isDig : !isDig
  })
  if (firstKey) {
    form.value.material = firstKey
  }
}

watch(() => form.value.material, (newMat) => {
  if (newMat) {
    activeCategory.value = newMat.includes('_dig') || newMat.startsWith('dtf_') ? 'digital' : 'offset';
  }
}, { immediate: true })

watch(() => props.bahanOptions, (options) => {
  if (options && !form.value.material) {
    const firstKey = Object.keys(options).find(key => !key.includes('_dig') && !key.startsWith('dtf_'))
    if (firstKey) {
      form.value.material = firstKey
    }
  }
}, { immediate: true })

// ─── Calculation ───────────────────────────────────────────────
const loading = ref(false)
const result  = ref(null)
const error   = ref(null)

const isDigitalAllowed = computed(() => {
  if (!form.value.type) return true
  
  const v = form.value.dimensions || {}
  let w = 0
  let h = 0
  const bleed = 0.1
  
  switch (form.value.type) {
    case 'lunchBox':
    case 'riceBox':
      const p_atas = parseFloat(v.p_atas || 0)
      const l_atas = parseFloat(v.l_atas || 0)
      const p_bawah = parseFloat(v.p_bawah || 0)
      const l_bawah = parseFloat(v.l_bawah || 0)
      const t = parseFloat(v.t || 0)
      const tutup = parseFloat(v.tutup || 0)
      w = l_atas + l_bawah + (2 * t) + tutup
      h = p_bawah + (2 * t)
      break
      
    case 'dineIn':
      const p_d = parseFloat(v.p || 0)
      const l_d = parseFloat(v.l || 0)
      const t_d = parseFloat(v.t || 0)
      w = p_d + (2 * t_d)
      h = l_d + (2 * t_d)
      break
      
    case 'kotakTutupTerpisah':
      const p_b = parseFloat(v.p_bawah || 0)
      const l_b = parseFloat(v.l_bawah || 0)
      const t_b = parseFloat(v.t_bawah || 0)
      const p_a = parseFloat(v.p_atas || 0)
      const l_a = parseFloat(v.l_atas || 0)
      const t_a = parseFloat(v.t_atas || 0)
      
      const wB = p_b + (2 * t_b) + 2 * bleed
      const hB = l_b + (2 * t_b) + 2 * bleed
      const wA = p_a + (2 * t_a) + 2 * bleed
      const hA = l_a + (2 * t_a) + 2 * bleed
      
      const fits = (wPart, hPart) => {
        return (wPart <= 47 && hPart <= 32) || (wPart <= 32 && hPart <= 47)
      }
      return fits(wB, hB) && fits(wA, hA)
      
    case 'kotakSambung':
      const p_s = parseFloat(v.p || 0)
      const l_s = parseFloat(v.l || 0)
      const t_s = parseFloat(v.t || 0)
      const tutup_s = parseFloat(v.tutup || 0)
      w = (l_s * 2) + (t_s * 2) + tutup_s
      h = (t_s * 2) + p_s
      break
      
    case 'straightTuckEnd':
      const p_ste = parseFloat(v.p || 0)
      const l_ste = parseFloat(v.l || 0)
      const t_ste = parseFloat(v.t || 0)
      const lem_ste = parseFloat(v.lem || 1.5)
      w = (p_ste * 2) + (l_ste * 2) + lem_ste
      h = t_ste + l_ste
      break
      
    case 'kebab':
      const p_k = parseFloat(v.p || 0)
      const l_k = parseFloat(v.l || 0)
      const lem_k = parseFloat(v.lem || 1.5)
      w = p_k
      h = (l_k * 2) + lem_k
      break
      
    case 'kotakMug':
      const p_m = parseFloat(v.p || 0)
      const l_m = parseFloat(v.l || 0)
      const t_m = parseFloat(v.t || 0)
      const lem_m = parseFloat(v.lem || 1.3)
      const kunci_m = parseFloat(v.kunci_bawah || 8)
      w = (2 * (p_m + l_m)) + lem_m
      h = t_m + l_m + kunci_m
      break
      
    case 'burger':
      const p_bu = parseFloat(v.p || 0)
      const l_bu = parseFloat(v.l || 0)
      const tb_bu = parseFloat(v.t_bawah || 4)
      const tk_bu = parseFloat(v.t_krkn || 5)
      const tt_bu = parseFloat(v.t_tutup || 5)
      w = (l_bu * 2) + (tb_bu * 3) + tt_bu
      h = (tk_bu * 2) + p_bu
      break
      
    case 'customFlat':
      w = parseFloat(v.p || 0)
      h = parseFloat(v.l || 0)
      break
  }
  
  w += 2 * bleed
  h += 2 * bleed
  
  return (w <= 47 && h <= 32) || (w <= 32 && h <= 47)
})

watch(isDigitalAllowed, (allowed) => {
  if (!allowed && activeCategory.value === 'digital') {
    selectCategory('offset')
  }
})

const canCalculate = computed(() => {
  if (!form.value.type || !form.value.material || !form.value.qty) return false
  if (form.value.qty < minQty.value) return false
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
      warna:      form.value.warna,
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
    kotakSambung: '📫', straightTuckEnd: '📮', kebab: '🌯', kotakMug: '☕', burger: '🍔', customFlat: '📄',
  }
  return icons[type] || '📦'
}

const materialIcon = (key) => {
  if (key.includes('kraft')) return '🟫'
  if (key.includes('ivory')) return '⬜'
  if (key.includes('duplex')) return '🔲'
  if (key.includes('ap')) return '✨'
  if (key.includes('chromo')) return '🏷️'
  if (key.includes('vinyl')) return '💦'
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

const showPreviewModal = ref(false)
</script>

<template>
  <div class="space-y-5 p-4 sm:p-6">

    <!-- ── Header ── -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">📦 Kalkulator Kemasan Box Custom</h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Hitung estimasi harga cetak kemasan box custom full color Dooren'z</p>
      </div>
      <div v-if="isDoorenz">
        <button
          type="button"
          @click="showPreviewModal = true"
          class="flex items-center gap-2 rounded-xl bg-indigo-50 border border-indigo-200 dark:bg-indigo-900/30 dark:border-indigo-800 px-4 py-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-all shadow-sm"
        >
          📐 Lihat Cara Hitung
        </button>
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

          <!-- Preset chips -->
          <div v-if="currentPresets.length" class="space-y-1.5">
            <p class="text-[11px] text-gray-400 dark:text-gray-500">💡 Pilih ukuran referensi (opsional):</p>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="preset in currentPresets"
                :key="preset.label"
                type="button"
                @click="applyPreset(preset)"
                class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-600 transition-all hover:bg-indigo-100 hover:border-indigo-400 dark:border-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400 dark:hover:bg-indigo-900/40"
              >
                {{ preset.label }}
              </button>
            </div>
          </div>

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
            :min="minQty"
            step="100"
            class="input w-full"
            :placeholder="'Min. ' + minQty + ' pcs'"
          />
          <p class="mt-1 text-[11px] text-gray-400">Minimal order: 1000 pcs (offset) atau 50 pcs (digital)</p>
        </div>

        <!-- 4. Bahan Kertas -->
        <div>
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">
            📄 Bahan Kertas <span class="text-red-500">*</span>
          </label>
          
          <!-- Category Selector Tabs -->
          <div v-if="isDoorenz">
            <div class="mb-2 flex gap-2">
              <button
                type="button"
                @click="selectCategory('offset')"
                class="flex-1 rounded-lg border py-2 text-center text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                :class="activeCategory === 'offset'
                  ? 'border-primary-500 bg-primary-600 text-white shadow-sm'
                  : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100 dark:border-dark-700 dark:bg-dark-800 dark:text-gray-400 dark:hover:bg-dark-700'"
              >
                🏭 Offset
              </button>
              <button
                v-if="isDigitalAllowed"
                type="button"
                @click="selectCategory('digital')"
                class="flex-1 rounded-lg border py-2 text-center text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                :class="activeCategory === 'digital'
                  ? 'border-primary-500 bg-primary-600 text-white shadow-sm'
                  : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100 dark:border-dark-700 dark:bg-dark-800 dark:text-gray-400 dark:hover:bg-dark-700'"
              >
                ⚡ Digital Printing
              </button>
            </div>
            <p v-if="!isDigitalAllowed" class="mb-3 text-[11px] font-semibold text-amber-600 dark:text-amber-400 flex items-center gap-1">
              ⚠️ Ukuran box terlalu besar untuk kertas cetak digital A3+ (Maks. 32x47 cm).
            </p>
          </div>

          <!-- Filtered Materials List -->
          <div class="space-y-1.5 max-h-96 overflow-y-auto pr-1">
            <button
              v-for="(name, key) in filteredBahanOptions"
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
          <div v-if="form.material === 'kraft290_off'" class="rounded-lg bg-gray-100 p-2.5 text-xs text-gray-500 dark:bg-dark-800 dark:text-gray-400 border border-gray-200 dark:border-dark-700">
            💡 Bahan Kraft sudah dilapisi laminasi PE (anti air/minyak) bawaan.
          </div>
          <div v-else class="grid grid-cols-3 gap-1.5">
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

        <!-- 6. Warna Cetak -->
        <div v-if="isDoorenz">
          <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">
            🎨 Warna Cetak <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-3 gap-1.5" v-if="form.material === 'kraft290_off'">
            <button
              v-for="w in ['1', '2']"
              :key="w"
              @click="form.warna = w"
              class="rounded-lg border px-2 py-2 text-xs font-medium transition-all"
              :class="form.warna === w
                ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-primary-300 dark:border-dark-600 dark:bg-dark-800 dark:text-gray-400'"
            >
              {{ w }} Warna
            </button>
          </div>
          <div class="grid grid-cols-2 gap-1.5" v-else>
            <button
              v-for="w in ['1', '2', '3', 'full']"
              :key="w"
              @click="form.warna = w"
              class="rounded-lg border px-2 py-2 text-xs font-medium transition-all"
              :class="form.warna === w
                ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-primary-300 dark:border-dark-600 dark:bg-dark-800 dark:text-gray-400'"
            >
              {{ w === 'full' ? 'Full Color (4 Warna)' : w + ' Warna' }}
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

          <!-- ── Alert Penawaran Sablon (Qty < 1000) ── -->
          <div v-if="isDoorenz && result.result?.is_sablon" class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300">
            <div class="flex gap-2">
              <span class="text-lg">💡</span>
              <div>
                <p class="font-bold text-xs uppercase tracking-wide">Penawaran Sablon 1 Warna</p>
                <p class="mt-1 text-xs leading-relaxed">
                  Cetak custom full color minimal <strong>1.000 pcs</strong>. Karena jumlah cetak di bawah minimal, estimasi di atas otomatis dialihkan menggunakan <strong>Sablon Kemasan 1 Warna</strong> (Tanpa Laminasi).
                </p>
              </div>
            </div>
          </div>

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
                <dd class="font-semibold text-gray-800 dark:text-gray-200">
                  {{ isDoorenz && result.result?.is_sablon ? 'Tanpa Laminasi (Otomatis Sablon)' : laminasiOptions[form.laminasi] }}
                </dd>
              </div>
              <div v-if="isDoorenz" class="flex justify-between border-b border-gray-100 pb-2 dark:border-dark-700">
                <dt class="text-gray-500">Warna Cetak</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-200">
                  {{ form.warna === 'full' ? 'Full Color (4 Warna)' : form.warna + ' Warna' }}
                </dd>
              </div>
              <div v-if="isDoorenz" class="flex justify-between border-b border-gray-100 pb-2 dark:border-dark-700">
                <dt class="text-gray-500">Metode Cetak</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-200">
                  <span :class="result.result?.is_sablon ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-gray-800 dark:text-gray-200'">
                    {{ result.result?.print_method }}
                  </span>
                </dd>
              </div>
              <div class="flex justify-between border-b border-gray-100 pb-2 dark:border-dark-700">
                <dt class="text-gray-500">Jumlah Cetak</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ Number(form.qty).toLocaleString('id-ID') }} pcs</dd>
              </div>
              <div class="flex justify-between border-b border-gray-100 pb-2 dark:border-dark-700" v-if="isDoorenz && form.material && !form.material.includes('_dig')">
                <dt class="text-gray-500">Pembagian Plano</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-200">Bagi {{ result.result?.items_per_plano }}</dd>
              </div>
              <div class="flex justify-between border-b border-gray-100 pb-2 dark:border-dark-700" v-if="isDoorenz && form.material && !form.material.includes('_dig')">
                <dt class="text-gray-500">Ukuran Kertas Cetak</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-200">Bagi {{ result.result?.print_division }}</dd>
              </div>
              <div class="flex justify-between border-b border-gray-100 pb-2 dark:border-dark-700" v-if="isDoorenz && form.material && !form.material.includes('_dig')">
                <dt class="text-gray-500">Tata Letak (Layout)</dt>
                <dd class="font-semibold text-gray-800 dark:text-gray-200">
                  <span v-if="result.result?.is_combined" class="text-blue-600 dark:text-blue-400 font-bold">
                    Gabung Cetak ({{ result.result?.items_per_sheet }} up per lembar)
                  </span>
                  <span v-else class="text-gray-600 dark:text-gray-400">
                    Standar (1 up per lembar)
                  </span>
                </dd>
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

    <!-- ── Modal Panduan Ukuran & Cara Hitung ── -->
    <div
      v-if="showPreviewModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 transition-opacity duration-300"
      @click.self="showPreviewModal = false"
    >
      <div class="relative w-full max-w-4xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-dark-700 dark:bg-dark-900 transition-all transform duration-300">
        
        <!-- Header Modal -->
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-dark-700">
          <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">📐 Panduan Ukuran Bentangan & Cara Hitung Box Custom</h3>
          <button
            type="button"
            @click="showPreviewModal = false"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl font-bold p-1"
          >
            &times;
          </button>
        </div>

        <!-- Body Modal -->
        <div class="max-h-[75vh] overflow-y-auto p-6 space-y-4">
          <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
            Berikut adalah penjelasan visual bagaimana dimensi box custom (Panjang, Lebar, Tinggi, dan Tutup) diratakan menjadi satu lembaran cetak datar (Bentangan plano) sebelum dicetak dan dilipat:
          </p>
          
          <div class="flex justify-center bg-gray-50 dark:bg-dark-950 p-4 rounded-xl border border-gray-150 dark:border-dark-800">
            <img
              src="/images/box_calculation_preview.png"
              alt="Panduan Perhitungan Box Custom"
              class="max-w-full h-auto rounded-lg shadow-sm"
            />
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600 dark:text-gray-400">
            <div class="bg-gray-50 dark:bg-dark-800 p-3 rounded-lg">
              <p class="font-bold text-gray-800 dark:text-gray-200 mb-1">💡 Cara Membaca Rumus Bentangan:</p>
              <ul class="list-disc list-inside space-y-1">
                <li><b>Lebar (W Jadi)</b>: Lebar bentangan dari kiri ke kanan saat pola kertas diletakkan datar.</li>
                <li><b>Tinggi (H Jadi)</b>: Tinggi bentangan dari atas ke bawah saat pola kertas diletakkan datar.</li>
                <li><b>Bleed (+0.75 cm)</b>: Area aman potong di setiap sisi untuk mencegah kertas terpotong meleset saat produksi.</li>
              </ul>
            </div>
            <div class="bg-gray-50 dark:bg-dark-800 p-3 rounded-lg">
              <p class="font-bold text-gray-800 dark:text-gray-200 mb-1">📏 Contoh Dimensi Bentangan (Kotak Sambung):</p>
              <ul class="list-disc list-inside space-y-1">
                <li>Lebar Bentangan (W) = (Lebar × 2) + (Tinggi × 2) + Tutup</li>
                <li>Tinggi Bentangan (H) = (Tinggi × 2) + Panjang</li>
                <li><i>Contoh: Box 14x10.5x6.5 cm, Tutup 3.5 cm memiliki bentangan plano W: 37.5 cm dan H: 27 cm.</i></li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Footer Modal -->
        <div class="flex justify-end border-t border-gray-200 px-6 py-3.5 dark:border-dark-700 bg-gray-50 dark:bg-dark-900/50">
          <button
            type="button"
            @click="showPreviewModal = false"
            class="rounded-xl border border-gray-300 bg-white dark:border-dark-750 dark:bg-dark-800 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-dark-750/50 shadow-sm"
          >
            Tutup Panduan
          </button>
        </div>

      </div>
    </div>

  </div>
</template>
