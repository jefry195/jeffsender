<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/BlankLayout.vue'
import notify from '@/Composables/toastComposable'
import { ref } from 'vue'
import LeftBanner from './Partials/LeftBanner.vue'
import RightSide from './Partials/RightSide.vue'
import TextInput from './Partials/TextInput.vue'
import PrimaryButton from './Partials/PrimaryButton.vue'

defineOptions({ layout: AuthLayout })

const props = defineProps({
  email: {
    type: String,
    required: true
  },
  token: {
    type: String,
    required: true
  },
  authPages: {
    type: Object
  }
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: ''
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const submit = () => {
  form.post(route('password.store'), {
    preserveScroll: true,
    onSuccess: () => notify.success(trans('Password reset has been successful')),
    onFinish: () => form.reset('password', 'password_confirmation')
  })
}
</script>

<template>
  <Head :title="trans('Reset Password')" />

  <div class="grid min-h-svh lg:grid-cols-2">
    <!-- Left Banner -->
    <LeftBanner :data="authPages?.login" />

    <!-- Right Form Section -->
    <RightSide>
      <form @submit.prevent="submit" class="flex flex-col gap-8">
        <!-- Header -->
        <div class="flex flex-col gap-2">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-200">
            {{ trans('Reset Password') }}
          </h1>
          <p class="text-balance text-sm">
            {{ trans('Enter your new password below') }}
          </p>
        </div>

        <div class="grid gap-6">
          <!-- Email Field -->
          <TextInput
            id="email"
            type="email"
            v-model="form.email"
            :label="trans('Your Email')"
            readonly
            class="opacity-75"
            :error="form.errors.email"
          />

          <!-- Password Field -->
          <TextInput
            id="password"
            :type="showPassword ? 'text' : 'password'"
            v-model="form.password"
            :label="trans('Password')"
            required
            :error="form.errors.password"
          >
            <template #suffix>
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="text-gray-500 hover:text-gray-900 dark:hover:text-gray-100"
              >
                <Icon
                  :icon="
                    showPassword
                      ? 'material-symbols:visibility-rounded'
                      : 'material-symbols:visibility-off-rounded'
                  "
                  class="h-5 w-5"
                />
              </button>
            </template>
          </TextInput>

          <!-- Password Confirmation Field -->
          <TextInput
            id="password_confirmation"
            :type="showPasswordConfirmation ? 'text' : 'password'"
            v-model="form.password_confirmation"
            :label="trans('Password Confirmation')"
            required
            :error="form.errors.password_confirmation"
          >
            <template #suffix>
              <button
                type="button"
                @click="showPasswordConfirmation = !showPasswordConfirmation"
                class="text-gray-500 hover:text-gray-900 dark:hover:text-gray-100"
              >
                <Icon
                  :icon="
                    showPasswordConfirmation
                      ? 'material-symbols:visibility-rounded'
                      : 'material-symbols:visibility-off-rounded'
                  "
                  class="h-5 w-5"
                />
              </button>
            </template>
          </TextInput>

          <!-- Submit Button -->
          <PrimaryButton type="submit" class="w-full" :disabled="form.processing">
            <Icon v-if="form.processing" icon="svg-spinners:180-ring" class="mr-2 h-4 w-4" />
            {{ trans('Reset Password') }}
          </PrimaryButton>
        </div>
      </form>
    </RightSide>
  </div>
</template>
