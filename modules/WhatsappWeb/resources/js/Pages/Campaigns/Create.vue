<script setup>
import { computed } from 'vue'

import InputField from '@/Components/Forms/InputField.vue'
import ShortCodes from '@/Components/Forms/ShortCodes.vue'
import { useForm } from '@inertiajs/vue3'
import TextareaField from '@/Components/Forms/TextareaField.vue'
import SpinnerBtn from '@/Components/Dashboard/SpinnerBtn.vue'

import UserLayout from '@/Layouts/User/UserLayout.vue'
import RangeSlider from '@/Components/RangeSlider.vue'
import TemplatePreview from '@whatsappWeb/Pages/Templates/Partials/TemplatePreview.vue'

defineOptions({ layout: UserLayout })
const props = defineProps(['platforms', 'templates', 'groups', 'time_zone_list'])

const form = useForm({
  module: 'whatsapp-web',
  title: '',
  platform_id: '',
  group_id: '',
  template_id: '',
  message_type: 'text',
  message_template: '',
  is_scheduled: false,
  schedule_at: null,
  timezone: 'Asia/Makassar',
  delay_between: [30, 60],
  // Anti-ban settings
  delay_min: 30,
  delay_max: 60,
  batch_size_min: 10,
  batch_size_max: 15,
  batch_pause_min: 15,
  batch_pause_max: 30,
  daily_limit: 50,
  spam_filter: true,
})

const submitForm = () => {
  form.post(route('user.whatsapp-web.campaigns.store'))
}

const selectedTemplate = computed(() => {
  return props.templates.find((template) => template.id == form.template_id)
})
</script>

<template>
  <div class="mt-4 grid grid-cols-1 place-items-start gap-6 sm:grid-cols-12">
    <div class="card card-body sm:col-span-9">
      <form @submit.prevent="submitForm" class="space-y-4 rounded-lg">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
          {{ trans('Create Campaign') }}
        </h2>

        <div class="mb-5">
          <InputField
            v-model="form.title"
            label="Campaign Title"
            class="w-full"
            :placeholder="trans('Enter a descriptive campaign name')"
          />
        </div>

        <!-- Core Settings Grid -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <!-- Platform Selection -->
          <div>
            <label class="label mb-1 flex items-center">
              <span>{{ trans('Platform') }}</span>
              <a
                :href="route('user.whatsapp-web.platforms.index')"
                class="ml-2 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
              >
                <span class="inline-flex items-center">
                  <span>{{ trans('Add New') }}</span>
                  <Icon class="ml-1 h-3 w-3" icon="bx:plus" />
                </span>
              </a>
            </label>
            <select v-model="form.platform_id" class="select 0">
              <option value="">{{ trans('Select Platform') }}</option>
              <option v-for="platform in platforms" :key="platform.id" :value="platform.id">
                {{ platform.name }}
              </option>
            </select>
          </div>

          <!-- Group Selection -->
          <div>
            <label class="label mb-1 flex items-center">
              <span>{{ trans('Group') }}</span>
              <a
                :href="route('user.whatsapp-web.groups.index')"
                class="ml-2 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
              >
                <span class="inline-flex items-center">
                  <span>{{ trans('Add New') }}</span>
                  <Icon class="ml-1 h-3 w-3" icon="bx:plus" />
                </span>
              </a>
            </label>
            <select v-model="form.group_id" class="select">
              <option value="">{{ trans('Select Group') }}</option>
              <option v-for="group in groups" :key="group.id" :value="group.id">
                {{ group.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Message Configuration Card -->
        <div class="mt-6 rounded-md border border-gray-200 p-4 dark:border-gray-700">
          <p class="mb-3 text-lg font-medium text-gray-700 dark:text-gray-300">
            {{ trans('Message Configuration') }}
          </p>

          <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <!-- Message Type -->
            <div>
              <label class="label mb-1">{{ trans('Message Type') }}</label>
              <select v-model="form.message_type" class="select">
                <option value="text">{{ trans('Text') }}</option>
                <option value="template">{{ trans('Template') }}</option>
              </select>
            </div>

            <!-- Template Selection (conditional) -->
            <div v-if="form.message_type === 'template'">
              <label class="label mb-1">{{ trans('Template') }}</label>
              <select v-model="form.template_id" class="select">
                <option value="" disabled selected>{{ trans('Select Template') }}</option>
                <option v-for="template in templates" :key="template.id" :value="template.id">
                  {{ template.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Message Delay Slider -->
          <div class="col-span-full">
            <label for="device_rotation_duration" class="label mb-1">
              {{ trans('Message Delay (in seconds)') }}
            </label>
            <RangeSlider class="px-1" v-model="form.delay_between" :step="1" />
          </div>

          <template v-if="form.message_type === 'text'">
            <TextareaField
              v-model="form.message_template"
              label="Message Template"
              placeholder="Enter the campaign message template"
              :attrs="{ rows: 5 }"
            />
            <ShortCodes v-model="form.message_template" />
          </template>
        </div>

        <!-- Scheduling Options -->

        <div
          class="flex flex-col rounded-md border border-gray-200 p-4 dark:border-gray-700 md:flex-row md:items-center md:justify-between"
        >
          <div class="mb-4 flex items-center md:mb-0">
            <label for="toggle-checkbox_1" class="toggle">
              <input
                class="toggle-input peer sr-only"
                v-model="form.is_scheduled"
                id="toggle-checkbox_1"
                type="checkbox"
                checked=""
              />
              <div class="toggle-body"></div>
              <span class="label">{{ trans('Set Schedule') }}</span>
            </label>
          </div>

          <div
            v-if="form.is_scheduled"
            class="flex flex-col items-center space-y-3 md:flex-row md:space-x-3 md:space-y-0"
          >
            <select v-model="form.timezone" class="select select-md">
              <option disabled>{{ trans('Select Timezone') }}</option>
              <option v-for="timezone in time_zone_list" :key="timezone" :value="timezone">
                {{ timezone }}
              </option>
            </select>
            <input
              class="input input-md"
              v-model="form.schedule_at"
              type="datetime-local"
              :min="new Date()"
            />
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex justify-end">
          <button
            type="button"
            class="mr-3 rounded-md border border-gray-300 bg-white px-4 py-2 text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
          >
            {{ trans('Cancel') }}
          </button>
          <SpinnerBtn
            :btn-text="trans(form.is_scheduled ? 'Schedule Campaign' : 'Send Now')"
            type="submit"
            class="btn btn-primary"
            :processing="form.processing"
            :icon="form.is_scheduled ? 'fe:clock' : 'bx:send'"
          />
        </div>

        <!-- =================== ANTI-BAN SETTINGS =================== -->
        <div class="mt-4 rounded-lg border border-amber-400 bg-amber-50 p-4 dark:border-amber-600 dark:bg-amber-900/20">
          <p class="mb-3 flex items-center gap-2 text-sm font-bold text-amber-700 dark:text-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            🛡️ Anti-Ban Settings
          </p>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <!-- Delay per nomor -->
            <div>
              <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                ⏱️ Delay per Nomor (detik)
              </label>
              <div class="flex items-center gap-2">
                <div class="flex flex-col">
                  <span class="text-xs text-gray-400">Min</span>
                  <input type="number" v-model.number="form.delay_min" min="1" max="60" class="input w-20 text-center text-sm" />
                </div>
                <span class="mt-4 text-gray-400">–</span>
                <div class="flex flex-col">
                  <span class="text-xs text-gray-400">Max</span>
                  <input type="number" v-model.number="form.delay_max" min="1" max="60" class="input w-20 text-center text-sm" />
                </div>
                <span class="mt-4 text-xs text-gray-500">detik</span>
              </div>
            </div>

            <!-- Batch size -->
            <div>
              <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                📦 Jumlah Nomor per Batch
              </label>
              <div class="flex items-center gap-2">
                <div class="flex flex-col">
                  <span class="text-xs text-gray-400">Min</span>
                  <input type="number" v-model.number="form.batch_size_min" min="1" max="500" class="input w-20 text-center text-sm" />
                </div>
                <span class="mt-4 text-gray-400">–</span>
                <div class="flex flex-col">
                  <span class="text-xs text-gray-400">Max</span>
                  <input type="number" v-model.number="form.batch_size_max" min="1" max="500" class="input w-20 text-center text-sm" />
                </div>
                <span class="mt-4 text-xs text-gray-500">nomor</span>
              </div>
            </div>

            <!-- Batch pause -->
            <div>
              <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                ☕ Istirahat Antar Batch (menit)
              </label>
              <div class="flex items-center gap-2">
                <div class="flex flex-col">
                  <span class="text-xs text-gray-400">Min</span>
                  <input type="number" v-model.number="form.batch_pause_min" min="1" max="60" class="input w-20 text-center text-sm" />
                </div>
                <span class="mt-4 text-gray-400">–</span>
                <div class="flex flex-col">
                  <span class="text-xs text-gray-400">Max</span>
                  <input type="number" v-model.number="form.batch_pause_max" min="1" max="60" class="input w-20 text-center text-sm" />
                </div>
                <span class="mt-4 text-xs text-gray-500">menit</span>
              </div>
            </div>

            <!-- Daily limit -->
            <div>
              <label class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                📊 Limit Harian (pesan/hari/nomor WA)
              </label>
              <div class="flex items-center gap-2">
                <input type="number" v-model.number="form.daily_limit" min="1" max="1000" class="input w-28 text-center text-sm" />
                <span class="text-xs text-gray-500">pesan/hari</span>
              </div>
            </div>
          </div>

          <!-- Filter Spam -->
          <div class="mt-3">
            <label class="toggle">
              <input class="toggle-input peer sr-only" v-model="form.spam_filter" type="checkbox" />
              <div class="toggle-body"></div>
              <span class="label text-xs font-semibold">🚫 Filter Kata Spam Otomatis</span>
            </label>
            <p class="mt-1 text-xs text-gray-400">Ganti kata berisiko (gratis, hadiah, klik, dll) secara otomatis</p>
          </div>
        </div>
        <!-- =================== END ANTI-BAN =================== -->
      </form>
    </div>

    <div class="w-full sm:col-span-3">
      <div
        class="whatsapp-chat-body relative h-[35rem] rounded-xl border-2 border-dark-400 outline outline-4 outline-dark-500 dark:border-dark-800 dark:outline-dark-950"
      >
        <div
          class="absolute bottom-3 left-4 w-10/12 rounded-lg bg-white p-1 px-1 dark:bg-dark-700 xl:w-8/12"
        >
          <TemplatePreview
            v-if="form.message_type == 'template' && selectedTemplate"
            :template="selectedTemplate"
          />
          <p
            v-else-if="form.message_type == 'text'"
            class="rounded-lg bg-gray-100 p-2 text-xs leading-4 dark:bg-dark-800"
          >
            {{ form.message_template || trans('The message will appear here') }}
          </p>
          <p v-else class="rounded-lg bg-gray-100 p-2 text-[11px] leading-4 dark:bg-dark-800">
            {{ trans('The message will appear here') }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
