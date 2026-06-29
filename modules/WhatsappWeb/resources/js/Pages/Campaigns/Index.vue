<script setup>
import Paginate from '@/Components/Dashboard/Paginate.vue'
import NotificationRing from '@/Components/Chats/NotificationRing.vue'
import NoDataFound from '@/Components/NoDataFound.vue'
import sharedComposable from '@/Composables/sharedComposable'

import UserLayout from '@/Layouts/User/UserLayout.vue'
import moment from 'moment'
import momentTimezone from 'moment-timezone'
import FilterDropdown from '@/Components/Dashboard/FilterDropdown.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: UserLayout })
const props = defineProps(['campaigns', 'systemTimezone'])
const { deleteRow, badgeClass } = sharedComposable()

const onStatusChange = (campaign, newStatus) => {
  router.patch(route('user.whatsapp-web.campaigns.update-status', campaign.id), {
    status: newStatus
  }, {
    preserveScroll: true
  })
}
const filterOptions = [
  {
    label: 'Name',
    value: 'name'
  },
  {
    label: 'Message Type',
    value: 'message_type',
    options: [
      {
        label: 'Text',
        value: 'text'
      },
      {
        label: 'template',
        value: 'template'
      }
    ]
  },
  {
    label: 'Status',
    value: 'status',
    options: [
      { label: 'Send',           value: 'send' },
      { label: 'Draft',          value: 'draft' },
      { label: 'Pending',        value: 'pending' },
      { label: 'Scheduled',      value: 'scheduled' },
      { label: 'Paused (Dijeda)',value: 'paused' }
    ]
  }
]
</script>

<template>
  <NotificationRing module="whatsapp-web" />
  <FilterDropdown :options="filterOptions" />
  <div class="table-responsive mt-4 w-full">
    <table class="table">
      <thead>
        <tr>
          <th>
            {{ trans('Title') }}
          </th>
          <th>{{ trans('Platform') }}</th>
          <th>{{ trans('Group') }}</th>
          <th>{{ trans('Message Type') }}</th>
          <th>{{ trans('Template') }}</th>
          <th class="!text-center">{{ trans('Status') }}</th>
          <th class="!text-right">
            {{ trans('Action') }}
          </th>
        </tr>
      </thead>
      <tbody v-if="campaigns.data.length" class="tbody">
        <tr v-for="(campaign, index) in campaigns.data" :key="index">
          <td>
            <Link :href="route('user.whatsapp-web.campaigns.show', campaign)">
              {{ campaign.name }}
            </Link>
          </td>
          <td>
            {{ campaign.platform?.name ?? trans('N/A') }}
          </td>
          <td>
            {{ campaign.group?.name ?? trans('N/A') }}
          </td>
          <td>
            <span class="uppercase"> {{ campaign.message_type }}</span>
          </td>
          <td>
            <span :class="badgeClass(campaign.status)" v-if="campaign.template_id">
              {{ campaign.template?.name ?? trans('N/A') }}
            </span>
            <span v-else class="uppercase">N/A</span>
          </td>
          <td class="!text-center">
            <div class="flex flex-col items-center justify-center gap-1">
              <select
                :value="campaign.status"
                @change="onStatusChange(campaign, $event.target.value)"
                class="badge capitalize border-0 cursor-pointer focus:ring-0 focus:outline-none py-1 px-3 pr-8 rounded text-xs appearance-none relative text-center"
                :class="{
                  'badge-info bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400': campaign.status === 'draft',
                  'badge-secondary bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-400': campaign.status === 'pending',
                  'badge-primary bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400': campaign.status === 'scheduled',
                  'badge-success bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400': campaign.status === 'send',
                  'badge-warning bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400': campaign.status === 'paused',
                  'badge-danger bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400': campaign.status === 'failed',
                }"
                style="background-image: url(&quot;data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E&quot;); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em; padding-right: 2rem;"
              >
                <option value="draft" class="text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800">{{ trans('Draft') }}</option>
                <option value="pending" class="text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800">{{ trans('Pending (Run)') }}</option>
                <option value="scheduled" class="text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800" :disabled="!campaign.schedule_at">{{ trans('Scheduled') }}</option>
                <option value="send" class="text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800">{{ trans('Send (Completed)') }}</option>
                <option value="failed" class="text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800">{{ trans('Failed') }}</option>
                <option value="paused" class="text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800">{{ trans('⏸ Paused (Dijeda)') }}</option>
              </select>

              <div
                class="font-base flex flex-col items-center text-[11px]"
                v-if="campaign.status == 'scheduled'"
              >
                <div class="flex items-center">
                  <Icon class="mr-1 mt-px h-3 w-3 text-slate-400" icon="fe:clock" />

                  {{
                    campaign.schedule_at != null
                      ? momentTimezone
                          .tz(campaign.schedule_at, systemTimezone)
                          .tz(campaign.timezone)
                          .format('DD MMM YYYY hh:mm A')
                      : 'N/A'
                  }}
                </div>
                <span
                  v-if="campaign.schedule_at"
                  class="-mt-1 italic text-gray-600 dark:text-gray-500"
                >
                  ({{
                    momentTimezone
                      .tz(campaign.schedule_at, systemTimezone)
                      .tz(campaign.timezone)
                      .fromNow()
                  }})
                </span>
              </div>
            </div>
          </td>
          <td>
            <div class="flex justify-end">
              <div class="dropdown" data-placement="bottom-start">
                <div class="dropdown-toggle">
                  <Icon class="h-5 text-3xl text-slate-400" icon="bx:dots-vertical-rounded" />
                </div>
                <div class="dropdown-content w-56">
                  <ul class="dropdown-list">
                    <li class="dropdown-list-item" v-if="campaign.status == 'scheduled'">
                      <a
                        class="dropdown-link"
                        :href="route('user.whatsapp-web.campaigns.edit', campaign.id)"
                      >
                        <Icon class="h-5 text-3xl text-slate-400" icon="bx:send" />
                        {{ trans('Send Now') }}
                      </a>
                    </li>

                    <!-- Tombol Lanjutkan untuk campaign yang dijeda -->
                    <li class="dropdown-list-item" v-if="campaign.status == 'paused'">
                      <a
                        class="dropdown-link text-amber-600"
                        :href="route('user.whatsapp-web.campaigns.resume', campaign.id)"
                      >
                        <Icon class="h-5 text-3xl" icon="bx:play" />
                        {{ trans('Lanjutkan') }}
                      </a>
                    </li>
                    <li class="dropdown-list-item" v-if="['pending', 'scheduled', 'send'].includes(campaign.status)">
                      <a
                        class="dropdown-link text-rose-600"
                        :href="route('user.whatsapp-web.campaigns.pause', campaign.id)"
                      >
                        <Icon class="h-5 text-3xl" icon="bx:pause" />
                        {{ trans('Jeda (Pause)') }}
                      </a>
                    </li>
                    <li class="dropdown-list-item" v-else-if="campaign.status == 'send'">
                      <Link
                        class="dropdown-link"
                        :href="route('user.whatsapp-web.campaigns.show', campaign.id)"
                      >
                        <Icon class="h-5 text-3xl text-slate-400" icon="bx:list-ul" />
                        {{ trans('Logs') }}
                      </Link>
                    </li>
                    <li class="dropdown-list-item">
                      <button
                        class="dropdown-link delete-confirm"
                        href="#"
                        @click="
                          deleteRow(route('user.whatsapp-web.campaigns.destroy', campaign.id))
                        "
                      >
                        <Icon class="h-5 text-3xl text-slate-400" icon="bx:trash" />
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
  </div>
  <Paginate :links="campaigns.links" />
</template>
