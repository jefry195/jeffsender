<script setup>
import { defineAsyncComponent, onMounted } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import UserLayout from '@/Layouts/User/UserLayout.vue'
import NotificationRing from '@/Components/Chats/NotificationRing.vue'
import OverviewSkeleton from '@/Components/Dashboard/Skeleton/OverviewCard.vue'
import ChartSkeleton from '@/Components/Dashboard/Skeleton/Chart.vue'
import { useUserDashboardStore } from '@/Store/userDashboardStore'
import { storeToRefs } from 'pinia'
defineOptions({ layout: UserLayout })
const props = defineProps([])
const OverviewGrid = defineAsyncComponent(() => import('@/Components/Dashboard/OverviewGrid.vue'))
const dashboardStore = useUserDashboardStore()
const { analytics, campaignChart, messageChart, dateFilterQueries, filterForm, loading } =
  storeToRefs(dashboardStore)
onMounted(() => {
  dashboardStore.fetchAnalytics({
    type: 'overviews',
    filter: 'year',
    platform: 'whatsapp'
  })
  dashboardStore.fetchAnalytics({
    type: 'messages',
    filter: 'year',
    platform: 'whatsapp'
  })
  dashboardStore.fetchAnalytics({
    type: 'campaigns',
    filter: 'year',
    platform: 'whatsapp'
  })
})
</script>

<template>
  <OverviewSkeleton v-if="analytics.overviews.loading" :skeleton-count="8" />
  <OverviewGrid v-else :items="analytics.overviews.data" />

  <NotificationRing />

  <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    <ChartSkeleton v-if="analytics.messages.loading" />

    <div class="card card-body rounded-2xl" v-else>
      <div class="flex flex-wrap justify-between gap-2">
        <h6>{{ trans('Message Statistics') }}</h6>
        <select
          v-model="filterForm.messages"
          @change="
            dashboardStore.fetchAnalytics({
              type: 'messages',
              filter: filterForm.messages,
              platform: 'whatsapp'
            })
          "
          class="select select-xl w-full capitalize md:w-40"
        >
          <option value="" selected>{{ trans('Filter By') }}</option>
          <option
            :value="item"
            v-for="item in dateFilterQueries"
            :key="item"
            :selected="filterForm.messages === item"
          >
            {{ item }}
          </option>
        </select>
      </div>
      <VueApexCharts
        v-if="$el"
        height="350"
        :options="messageChart.chartOptions"
        :series="messageChart.series"
      />
    </div>

    <ChartSkeleton v-if="analytics.campaigns.loading" />
    <div class="card card-body rounded-2xl" v-else>
      <div class="flex flex-wrap justify-between gap-2">
        <h6>{{ trans('Campaign Statistic') }}</h6>
        <select
          v-model="filterForm.campaigns"
          @change="
            dashboardStore.fetchAnalytics({
              type: 'campaigns',
              filter: filterForm.campaigns,
              platform: 'whatsapp'
            })
          "
          class="select select-xl w-full capitalize md:w-40"
        >
          <option value="" selected>{{ trans('Filter By') }}</option>
          <option
            :value="item"
            v-for="item in dateFilterQueries"
            :key="item"
            :selected="filterForm.sales === item"
          >
            {{ item }}
          </option>
        </select>
      </div>
      <VueApexCharts
        v-if="$el"
        height="350"
        :options="campaignChart.chartOptions"
        :series="campaignChart.series"
      />
    </div>
  </div>
</template>
