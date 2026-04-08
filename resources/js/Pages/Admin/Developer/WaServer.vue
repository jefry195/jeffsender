<script setup>
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue'
import SpinnerBtn from '@/Components/Dashboard/SpinnerBtn.vue'
import { useForm } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })
const props = defineProps([
  'id',
  'PORT',
  'HOST',
  'DATABASE_URL',

])

const form = useForm({
  PORT: props.PORT,
  HOST: props.HOST,
  DATABASE_URL: props.DATABASE_URL,

 
})

function update() {
  form.put(route('admin.developer-settings.update', props.id))
}
</script>
<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-12">
      <div class="lg:col-span-5">
        <strong>{{ trans('Whatsapp Server Settings') }}</strong>
        <p>{{ trans('Edit Whatsapp Server settings') }}</p>
      </div>
      <div class="lg:col-span-7">
        <form @submit.prevent="update">
          <div class="card">
            <div class="card-body">
              <div class="mb-2">
                <label class="label">{{ trans('PORT') }}</label>
                <input type="text" v-model="form.PORT" class="input" required />
              </div>
              <div class="mb-2">
                <label class="label">{{ trans('HOST') }}</label>
                <input type="text" v-model="form.HOST" class="input" required />
              </div>
             

             
              <div class="mb-2">
                <label class="label">{{ trans('DATABASE URL') }}</label>
                <input type="text" disabled :value="form.DATABASE_URL" required class="input" />
              </div>
             

              <div class="mt-3">
                <SpinnerBtn :processing="form.processing" :btn-text="trans('Save Changes')" />
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
