<script setup>
import { ref } from 'vue'
import UserLayout from '@/Layouts/User/UserLayout.vue'

defineOptions({ layout: UserLayout })
const props = defineProps(['groups', 'campaigns'])

const form = ref({
  group_id: '',
  campaign_id: '',
  message: 'Halo {name}! 🎉 Dapatkan PROMO GRATIS hari ini! Klik di sini untuk info lebih lanjut. Stok terbatas! Segera hubungi kami sekarang!',
  delay_min: 8,
  delay_max: 15,
  batch_size_min: 20,
  batch_size_max: 30,
  batch_pause_min: 5,
  batch_pause_max: 10,
  daily_limit: 150,
  spam_filter: true,
})

const loading   = ref(false)
const result    = ref(null)
const error     = ref(null)
const activeTab = ref('summary')

const statusColor = (status) => {
  if (status === 'akan_kirim') return 'text-green-600 dark:text-green-400'
  if (status === 'skip_sent')  return 'text-blue-500 dark:text-blue-400'
  if (status === 'skip_limit') return 'text-red-500 dark:text-red-400'
  return 'text-gray-500'
}

const statusIcon = (status) => {
  if (status === 'akan_kirim') return '✅'
  if (status === 'skip_sent')  return '⏭️'
  if (status === 'skip_limit') return '🚫'
  return '❓'
}

const runSimulation = async () => {
  if (!form.value.group_id) { error.value = 'Pilih Group terlebih dahulu!'; return }
  if (!form.value.message.trim()) { error.value = 'Masukkan pesan terlebih dahulu!'; return }

  loading.value = true
  error.value   = null
  result.value  = null

  try {
    const res = await axios.post(route('user.whatsapp-web.simulation.run'), form.value)
    result.value  = res.data
    activeTab.value = 'summary'
  } catch (e) {
    error.value = e.response?.data?.message || 'Terjadi kesalahan saat simulasi.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="space-y-4 p-4 sm:p-6">

    <!-- Header Manual (tanpa PageHeader komponen) -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">🧪 Simulasi Campaign</h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Test semua fitur Anti-Ban tanpa kirim pesan ke WhatsApp</p>
      </div>
      <a
        :href="route('user.whatsapp-web.campaigns.index')"
        class="btn btn-secondary inline-flex items-center gap-2 self-start sm:self-auto"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Kembali ke Kampanye
      </a>
    </div>

    <!-- Banner info -->
    <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/20">
      <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
      </svg>
      <p class="text-sm text-blue-700 dark:text-blue-300">
        <strong>Mode Simulasi</strong> — Tidak ada pesan yang dikirim ke WhatsApp. Ini hanya kalkulasi untuk preview fitur Anti-Ban Anda.
      </p>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

      <!-- ══════ KIRI: Form ══════ -->
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-dark-700 dark:bg-dark-900 lg:col-span-4 space-y-4">
        <h2 class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wide">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          Konfigurasi
        </h2>

        <!-- Group -->
        <div>
          <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">👥 Group Kontak <span class="text-red-500">*</span></label>
          <select v-model="form.group_id" class="select">
            <option value="">-- Pilih Group --</option>
            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
          </select>
        </div>

        <!-- Campaign untuk resume test -->
        <div>
          <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">📋 Campaign (opsional — test resume)</label>
          <select v-model="form.campaign_id" class="select">
            <option value="">-- Tidak ada (campaign baru) --</option>
            <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }} · {{ c.status }}</option>
          </select>
          <p class="mt-1 text-[11px] text-gray-400">Sistem cek siapa yang sudah terkirim di campaign ini</p>
        </div>

        <!-- Pesan -->
        <div>
          <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-400">💬 Teks Pesan (test filter spam)</label>
          <textarea
            v-model="form.message"
            class="textarea text-sm"
            rows="4"
            placeholder="Ketik pesan dengan kata-kata berisiko..."
          ></textarea>
          <p class="mt-1 text-[11px] text-gray-400">💡 Coba: "Gratis", "Klik di sini", "Hadiah", "Promo", "Buruan"</p>
        </div>

        <!-- Anti-Ban Box -->
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 dark:border-amber-700 dark:bg-amber-900/20 space-y-3">
          <p class="text-xs font-bold text-amber-700 dark:text-amber-400">🛡️ Anti-Ban Settings</p>

          <div class="grid grid-cols-2 gap-x-4 gap-y-3">
            <div>
              <label class="mb-1 block text-[11px] font-semibold text-gray-600 dark:text-gray-400">⏱️ Delay (detik)</label>
              <div class="flex items-center gap-1">
                <input type="number" v-model.number="form.delay_min" min="1" max="60" class="input w-14 text-center text-xs py-1" />
                <span class="text-gray-400 text-xs">–</span>
                <input type="number" v-model.number="form.delay_max" min="1" max="60" class="input w-14 text-center text-xs py-1" />
              </div>
            </div>

            <div>
              <label class="mb-1 block text-[11px] font-semibold text-gray-600 dark:text-gray-400">📊 Limit Harian</label>
              <input type="number" v-model.number="form.daily_limit" min="1" max="1000" class="input w-20 text-center text-xs py-1" />
            </div>

            <div>
              <label class="mb-1 block text-[11px] font-semibold text-gray-600 dark:text-gray-400">📦 Batch Size</label>
              <div class="flex items-center gap-1">
                <input type="number" v-model.number="form.batch_size_min" min="1" max="500" class="input w-14 text-center text-xs py-1" />
                <span class="text-gray-400 text-xs">–</span>
                <input type="number" v-model.number="form.batch_size_max" min="1" max="500" class="input w-14 text-center text-xs py-1" />
              </div>
            </div>

            <div>
              <label class="mb-1 block text-[11px] font-semibold text-gray-600 dark:text-gray-400">☕ Istirahat (mnt)</label>
              <div class="flex items-center gap-1">
                <input type="number" v-model.number="form.batch_pause_min" min="1" max="60" class="input w-14 text-center text-xs py-1" />
                <span class="text-gray-400 text-xs">–</span>
                <input type="number" v-model.number="form.batch_pause_max" min="1" max="60" class="input w-14 text-center text-xs py-1" />
              </div>
            </div>
          </div>

          <label class="toggle mt-1">
            <input class="toggle-input peer sr-only" v-model="form.spam_filter" type="checkbox" />
            <div class="toggle-body"></div>
            <span class="label text-xs font-semibold text-amber-700 dark:text-amber-400">🚫 Filter Kata Spam Otomatis</span>
          </label>
        </div>

        <!-- Error -->
        <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
          ⚠️ {{ error }}
        </div>

        <!-- Tombol Run -->
        <button
          @click="runSimulation"
          :disabled="loading"
          class="btn btn-primary w-full flex items-center justify-center gap-2"
        >
          <svg v-if="loading" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          {{ loading ? 'Sedang Menghitung...' : '🚀 Jalankan Simulasi' }}
        </button>
      </div>

      <!-- ══════ KANAN: Hasil ══════ -->
      <div class="lg:col-span-8 space-y-4">

        <!-- Placeholder -->
        <div
          v-if="!result && !loading"
          class="flex h-full min-h-[400px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 text-center dark:border-dark-700 dark:bg-dark-900"
        >
          <div class="mb-3 text-5xl">🧪</div>
          <p class="text-base font-semibold text-gray-600 dark:text-gray-300">Belum ada hasil</p>
          <p class="mt-1 text-sm text-gray-400">Pilih group & isi form di kiri, lalu klik<br><strong>"🚀 Jalankan Simulasi"</strong></p>
        </div>

        <!-- Loading -->
        <div
          v-if="loading"
          class="flex min-h-[400px] flex-col items-center justify-center rounded-xl border border-gray-200 bg-white dark:border-dark-700 dark:bg-dark-900"
        >
          <div class="mb-3 text-5xl animate-bounce">⚙️</div>
          <p class="text-base font-medium text-gray-600 dark:text-gray-300">Menghitung simulasi...</p>
        </div>

        <!-- Hasil -->
        <template v-if="result">

          <!-- Tabs -->
          <div class="flex overflow-x-auto rounded-xl border border-gray-200 bg-white p-1 dark:border-dark-700 dark:bg-dark-900">
            <button
              v-for="(label, tab) in { summary: '📊 Ringkasan', spam: '🚫 Filter Spam', batch: '📦 Batch', contacts: '👥 Kontak' }"
              :key="tab"
              @click="activeTab = tab"
              class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold transition-all"
              :class="activeTab === tab
                ? 'bg-primary-600 text-white shadow'
                : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-dark-800'"
            >
              {{ label }}
            </button>
          </div>

          <!-- ── TAB RINGKASAN ── -->
          <div v-if="activeTab === 'summary'" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-dark-700 dark:bg-dark-900 space-y-4">
            <h3 class="font-bold text-gray-800 dark:text-gray-100">📊 Ringkasan Simulasi</h3>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <div class="rounded-lg bg-gray-50 p-3 text-center dark:bg-dark-800">
                <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ result.summary.total_customers }}</p>
                <p class="text-xs text-gray-500 mt-1">Total Kontak</p>
              </div>
              <div class="rounded-lg bg-green-50 p-3 text-center dark:bg-green-900/20">
                <p class="text-2xl font-bold text-green-600">{{ result.summary.will_send }}</p>
                <p class="text-xs text-gray-500 mt-1">Akan Dikirim ✅</p>
              </div>
              <div class="rounded-lg bg-blue-50 p-3 text-center dark:bg-blue-900/20">
                <p class="text-2xl font-bold text-blue-600">{{ result.summary.already_sent }}</p>
                <p class="text-xs text-gray-500 mt-1">Di-skip (Resume) ⏭️</p>
              </div>
              <div class="rounded-lg bg-red-50 p-3 text-center dark:bg-red-900/20">
                <p class="text-2xl font-bold text-red-500">{{ result.summary.skipped_limit }}</p>
                <p class="text-xs text-gray-500 mt-1">Tertahan Limit 🚫</p>
              </div>
              <div class="rounded-lg bg-amber-50 p-3 text-center dark:bg-amber-900/20">
                <p class="text-2xl font-bold text-amber-600">{{ result.summary.total_batches }}</p>
                <p class="text-xs text-gray-500 mt-1">Jumlah Batch 📦</p>
              </div>
              <div class="rounded-lg bg-purple-50 p-3 text-center dark:bg-purple-900/20">
                <p class="text-base font-bold text-purple-600 leading-tight mt-1">{{ result.summary.est_time }}</p>
                <p class="text-xs text-gray-500 mt-1">Estimasi Durasi ⏳</p>
              </div>
            </div>

            <!-- Kuota bar -->
            <div>
              <div class="mb-1 flex justify-between text-xs text-gray-500">
                <span>Kuota harian terpakai hari ini</span>
                <span class="font-semibold">{{ result.summary.sent_today }} / {{ result.summary.daily_limit }}</span>
              </div>
              <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-dark-700">
                <div
                  class="h-3 rounded-full transition-all"
                  :class="(result.summary.sent_today / result.summary.daily_limit) > 0.8 ? 'bg-red-500' : 'bg-green-500'"
                  :style="`width:${Math.min(100, (result.summary.sent_today / result.summary.daily_limit) * 100)}%`"
                />
              </div>
              <p class="mt-1 text-xs text-gray-400">Sisa kuota: <strong>{{ result.summary.remaining_quota }}</strong> pesan</p>
            </div>

            <!-- Kesimpulan -->
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20 text-sm space-y-1">
              <p class="font-semibold text-green-700 dark:text-green-300">✅ Kesimpulan</p>
              <p class="text-gray-600 dark:text-gray-300 text-xs">• Dari <strong>{{ result.summary.total_customers }}</strong> kontak → <strong>{{ result.summary.will_send }}</strong> akan dikirim</p>
              <p v-if="result.summary.already_sent > 0" class="text-gray-600 dark:text-gray-300 text-xs">• <strong>{{ result.summary.already_sent }}</strong> di-skip (resume) ✅</p>
              <p v-if="result.summary.skipped_limit > 0" class="text-gray-600 dark:text-gray-300 text-xs">• <strong>{{ result.summary.skipped_limit }}</strong> tertahan daily limit → lanjut besok</p>
              <p class="text-gray-600 dark:text-gray-300 text-xs">• Estimasi selesai: <strong>{{ result.summary.est_time }}</strong></p>
            </div>
          </div>

          <!-- ── TAB SPAM FILTER ── -->
          <div v-if="activeTab === 'spam'" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-dark-700 dark:bg-dark-900 space-y-4">
            <h3 class="font-bold text-gray-800 dark:text-gray-100">🚫 Hasil Filter Spam</h3>

            <div v-if="!result.spam_filter.enabled" class="rounded-lg bg-gray-100 p-4 text-center text-sm text-gray-500 dark:bg-dark-800">
              Filter spam dinonaktifkan. Aktifkan toggle di form kiri, lalu simulasi ulang.
            </div>

            <template v-else>
              <!-- Kata terdeteksi -->
              <div>
                <p class="mb-2 text-xs font-semibold text-gray-600 dark:text-gray-400">⚠️ Kata Berisiko yang Terdeteksi:</p>
                <div v-if="result.spam_filter.detected_words.length" class="flex flex-wrap gap-2">
                  <span
                    v-for="word in result.spam_filter.detected_words"
                    :key="word"
                    class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400"
                  >🚨 {{ word }}</span>
                </div>
                <p v-else class="text-sm font-semibold text-green-600 dark:text-green-400">✅ Tidak ada kata spam — pesan aman!</p>
              </div>

              <!-- Before / After -->
              <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                  <p class="mb-1 text-xs font-semibold text-red-600">❌ Sebelum Filter</p>
                  <div class="min-h-24 rounded-lg border border-red-200 bg-red-50 p-3 text-sm whitespace-pre-wrap dark:border-red-800 dark:bg-red-900/20 dark:text-gray-300">{{ result.spam_filter.original }}</div>
                </div>
                <div>
                  <p class="mb-1 text-xs font-semibold text-green-600">✅ Sesudah Filter</p>
                  <div class="min-h-24 rounded-lg border border-green-200 bg-green-50 p-3 text-sm whitespace-pre-wrap dark:border-green-800 dark:bg-green-900/20 dark:text-gray-300">{{ result.spam_filter.filtered }}</div>
                </div>
              </div>

              <div
                class="rounded-lg p-3 text-xs font-medium"
                :class="result.spam_filter.changed
                  ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300'
                  : 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300'"
              >
                {{ result.spam_filter.changed
                  ? `⚡ ${result.spam_filter.detected_words.length} kata diganti otomatis untuk kurangi risiko ban.`
                  : '✅ Pesan sudah aman — tidak ada perubahan diperlukan!' }}
              </div>
            </template>
          </div>

          <!-- ── TAB BATCH ── -->
          <div v-if="activeTab === 'batch'" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-dark-700 dark:bg-dark-900 space-y-3">
            <h3 class="font-bold text-gray-800 dark:text-gray-100">📦 Rencana Batch Pengiriman</h3>
            <p class="text-xs text-gray-500">Sistem akan kirim per batch, lalu istirahat di antaranya.</p>

            <div class="space-y-2">
              <div
                v-for="batch in result.batches"
                :key="batch.batch"
                class="flex items-center gap-3 rounded-lg border border-gray-100 p-3 dark:border-dark-700"
              >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">
                  {{ batch.batch }}
                </div>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Batch {{ batch.batch }}</p>
                  <p class="text-xs text-gray-400">{{ batch.count }} nomor</p>
                </div>
                <span
                  class="rounded-full px-2 py-1 text-xs font-semibold"
                  :class="batch.pause_after > 0
                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                    : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
                >
                  {{ batch.pause_after > 0 ? `☕ Istirahat ${batch.pause_label}` : '✅ Selesai' }}
                </span>
              </div>

              <p v-if="!result.batches.length" class="py-8 text-center text-sm text-gray-400">Tidak ada kontak yang akan dikirim</p>
            </div>
          </div>

          <!-- ── TAB KONTAK ── -->
          <div v-if="activeTab === 'contacts'" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-dark-700 dark:bg-dark-900 space-y-3">
            <h3 class="font-bold text-gray-800 dark:text-gray-100">👥 Status Per Kontak <span class="text-xs font-normal text-gray-400">(maks. 50)</span></h3>

            <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-dark-700">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-dark-800">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">#</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Nama</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Nomor</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500">Batch</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500">Delay</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-700">
                  <tr v-for="c in result.customers" :key="c.no" class="hover:bg-gray-50 dark:hover:bg-dark-800">
                    <td class="px-3 py-2 text-xs text-gray-500">{{ c.no }}</td>
                    <td class="px-3 py-2 text-xs font-medium text-gray-800 dark:text-gray-200">{{ c.name }}</td>
                    <td class="px-3 py-2 font-mono text-xs text-gray-500">{{ c.phone }}</td>
                    <td class="px-3 py-2 text-center text-xs">{{ c.skipped ? '-' : c.batch }}</td>
                    <td class="px-3 py-2 text-center text-xs">{{ c.skipped ? '-' : c.delay + 's' }}</td>
                    <td class="px-3 py-2">
                      <span :class="statusColor(c.status)" class="text-xs font-semibold">
                        {{ statusIcon(c.status) }}
                        {{ c.status === 'akan_kirim' ? 'Akan Kirim' : c.status === 'skip_sent' ? 'Skip (resume)' : 'Skip (limit)' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </template>
      </div>
    </div>
  </div>
</template>
