<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/BlankLayout.vue'
import notify from '@/Composables/toastComposable'
import { ref } from 'vue'
import LeftBanner from './Partials/LeftBanner.vue'
import RightSide from './Partials/RightSide.vue'
import TextInput from './Partials/TextInput.vue'
import PrimaryButton from './Partials/PrimaryButton.vue'

defineOptions({ layout: AuthLayout })

defineProps(['authPages', 'googleClient', 'facebookClient'])

const queryParams = new URLSearchParams(window.location.search)

const form = useForm({
  plan_id: queryParams.get('plan_id') ?? null,
  name: '',
  email: queryParams.get('email') ?? '',
  password: ''
})

const showPassword = ref(false)

const submit = () => {
  form.post(route('register'), {
    onSuccess: () => {
      form.reset()
      notify.success(trans('Registration successful'))
    },
    onFinish: () => form.reset('password')
  })
}
</script>

<template>
  <Head :title="trans('Register')" />

  <div class="grid min-h-svh lg:grid-cols-2">
    <!-- Left Banner -->
    <LeftBanner :data="authPages?.login" />

    <!-- Right Form Section -->
    <RightSide>
      <form @submit.prevent="submit" class="flex flex-col gap-8">
        <!-- Header -->
        <div class="flex flex-col gap-2">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-200">
            {{ trans('Register Account') }}
          </h1>
          <p class="text-balance text-sm">
            {{ trans('Enter your information to create an account') }}
          </p>
        </div>

        <div class="grid gap-6">
          <!-- Name Field -->
          <TextInput
            id="name"
            type="text"
            v-model="form.name"
            :label="trans('Full Name')"
            placeholder="John Doe"
            required
            :error="form.errors.name"
          />

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

          <!-- Submit Button -->
          <PrimaryButton type="submit" class="w-full" :disabled="form.processing">
            <Icon v-if="form.processing" icon="svg-spinners:180-ring" class="mr-2 h-4 w-4" />
            {{ trans('Register') }}
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
                {{ trans('Register with') }} {{ trans('Google') }}
              </a>
            </PrimaryButton>

            <PrimaryButton v-if="facebookClient" variant="outline" class="w-full" as-child>
              <a
                :href="route('oauth.login', 'facebook')"
                class="flex w-full items-center justify-center gap-2"
              >
                <Icon icon="logos:facebook" />
                {{ trans('Register with') }} {{ trans('Facebook') }}
              </a>
            </PrimaryButton>
          </div>
        </div>

        <!-- Login Link -->
        <div class="text-center text-sm">
          {{ trans('Already have an account ?') }}
          <Link :href="route('login')" class="font-semibold underline underline-offset-4">
            {{ trans('Sign In') }}
          </Link>
        </div>
      </form>
    </RightSide>
  </div>
</template>
