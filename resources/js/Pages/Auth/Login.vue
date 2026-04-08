<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/BlankLayout.vue'
import { ref } from 'vue'
import LeftBanner from './Partials/LeftBanner.vue'
import RightSide from './Partials/RightSide.vue'
import TextInput from './Partials/TextInput.vue'
import PrimaryButton from './Partials/PrimaryButton.vue'

defineOptions({ layout: AuthLayout })

defineProps(['authPages', 'googleClient', 'facebookClient'])

const form = useForm({
  email: '',
  password: '',
  remember: false
})

const showPassword = ref(false)

const submit = () => {
  form.post(route('login'), {
    preserveScroll: true,
    onSuccess: () => notify.success(trans('Login Success')),
    onFinish: () => form.reset('password')
  })
}
</script>

<template>
  <Head :title="trans('Login')" />

  <div class="grid min-h-svh lg:grid-cols-2">
    <!-- Left Banner -->
    <LeftBanner :data="authPages?.login" />

    <!-- Right Form Section -->
    <RightSide>
      <form @submit.prevent="submit" class="flex flex-col gap-8">
        <!-- Header -->
        <div class="flex flex-col gap-2">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-200">
            {{ trans('Sign In') }}
          </h1>
          <p class="text-balance text-sm">
            {{ trans('Enter your email below to login to your account') }}
          </p>
        </div>

        <div class="grid gap-6">
          <!-- Email Field -->
          <TextInput
            id="email"
            type="email"
            v-model="form.email"
            :label="trans('Your Email')"
            placeholder="m@example.com"
            required
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
            <!-- Label Suffix: Forgot Password Link -->
            <template #label-suffix>
              <Link
                :href="route('password.request')"
                class="text-sm underline-offset-4 hover:underline"
              >
                {{ trans('Forgot password ?') }}
              </Link>
            </template>

            <!-- Input Suffix: Toggle Visibility -->
            <template #suffix>
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="mt-1 text-gray-500 hover:text-gray-900 dark:hover:text-gray-100"
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

          <!-- Remember Me -->
          <div class="flex items-center space-x-2">
            <input
              type="checkbox"
              id="remember"
              v-model="form.remember"
              class="h-4 w-4 rounded border-gray-300 text-neutral-900 focus:ring-neutral-900"
            />
            <label
              for="remember"
              class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
            >
              {{ trans('Remember me') }}
            </label>
          </div>

          <!-- Submit Button -->
          <PrimaryButton type="submit" class="w-full" :disabled="form.processing">
            <Icon v-if="form.processing" icon="svg-spinners:180-ring" class="mr-2 h-4 w-4" />
            {{ trans('Sign In') }}
          </PrimaryButton>

          <!-- Divider -->
          <div v-if="googleClient || facebookClient" class="relative">
            <div class="absolute inset-0 flex items-center">
              <span class="w-full border-t border-gray-200 dark:border-gray-800"></span>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="bg-white px-2 text-gray-500 dark:bg-gray-950 dark:text-gray-200">
                {{ trans('Or continue with') }}
              </span>
            </div>
          </div>

          <!-- OAuth Buttons -->
          <div v-if="googleClient || facebookClient" class="grid gap-3">
            <PrimaryButton v-if="googleClient" variant="outline" class="w-full" as-child>
              <a
                :href="route('oauth.login', 'google')"
                class="flex w-full items-center justify-center gap-2"
              >
                <Icon icon="devicon:google" />
                {{ trans('Login with') }} {{ trans('Google') }}
              </a>
            </PrimaryButton>

            <PrimaryButton v-if="facebookClient" variant="outline" class="w-full" as-child>
              <a
                :href="route('oauth.login', 'facebook')"
                class="flex w-full items-center justify-center gap-2"
              >
                <Icon icon="logos:facebook" />
                {{ trans('Login with') }} {{ trans('Facebook') }}
              </a>
            </PrimaryButton>
          </div>
        </div>

        <!-- Register Link -->
        <div class="text-center text-sm">
          {{ trans("Don't have account ?") }}
          <Link :href="route('register')" class="font-semibold underline underline-offset-4">
            {{ trans('Register') }}
          </Link>
        </div>
      </form>
    </RightSide>
  </div>
</template>
