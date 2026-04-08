<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/BlankLayout.vue'
import LeftBanner from './Partials/LeftBanner.vue'
import RightSide from './Partials/RightSide.vue'
import TextInput from './Partials/TextInput.vue'
import PrimaryButton from './Partials/PrimaryButton.vue'

defineOptions({ layout: AuthLayout })

defineProps({
  status: {
    type: String
  },
  authPages: Object
})

const form = useForm({
  email: ''
})

const submit = () => {
  form.post(route('password.email'))
}
</script>

<template>
  <Head :title="trans('Forgot Password')" />

  <div class="grid min-h-svh lg:grid-cols-2">
    <!-- Left Banner -->
    <LeftBanner :data="authPages?.login" />

    <!-- Right Form Section -->
    <RightSide>
      <form @submit.prevent="submit" class="flex flex-col gap-8">
        <!-- Header -->
        <div class="flex flex-col gap-2">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-200">
            {{ trans('Forget Password') }}
          </h1>
          <p class="text-balance text-sm">
            {{ trans('Enter your email to receive a password reset link') }}
          </p>
        </div>

        <div class="grid gap-6">
          <!-- Info Alert (Replaced Shadcn Alert with Tailwind) -->
          <div
            v-if="!status"
            class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-950 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-50"
          >
            {{
              trans(
                'Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.'
              )
            }}
          </div>

          <!-- Success Alert -->
          <div
            v-else
            class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100"
          >
            <Icon icon="lucide:check-circle" class="h-4 w-4 shrink-0" />
            <span>{{ status }}</span>
          </div>

          <!-- Email Field -->
          <TextInput
            id="email"
            type="email"
            v-model="form.email"
            :label="trans('Your Email')"
            :placeholder="trans('enter your email here')"
            autofocus
            autocomplete="email"
            required
            :error="form.errors.email"
          />

          <!-- Submit Button -->
          <PrimaryButton v-if="!status" type="submit" class="w-full" :disabled="form.processing">
            <Icon v-if="form.processing" icon="svg-spinners:180-ring" class="mr-2 h-4 w-4" />
            {{ trans('Email Password Reset Link') }}
          </PrimaryButton>
        </div>

        <!-- Login Link -->
        <div class="text-center text-sm">
          {{ trans('Have an account?') }}
          <Link :href="route('login')" class="font-semibold underline underline-offset-4">
            {{ trans('Sign In') }}
          </Link>
        </div>
      </form>
    </RightSide>
  </div>
</template>
