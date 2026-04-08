<script setup>
import { computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/BlankLayout.vue'
import LeftBanner from './Partials/LeftBanner.vue'
import RightSide from './Partials/RightSide.vue'
import PrimaryButton from './Partials/PrimaryButton.vue'

defineOptions({ layout: AuthLayout })

const props = defineProps({
  status: {
    type: String
  },
  authPages: Object
})

const form = useForm({})

const submit = () => {
  form.post(route('verification.send'))
}

const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
  <Head :title="trans('Email Verification')" />

  <div class="grid min-h-svh lg:grid-cols-2">
    <!-- Left Banner -->
    <LeftBanner :data="authPages?.login" />

    <!-- Right Content Section -->
    <RightSide>
      <div class="flex flex-col gap-8">
        <!-- Header -->
        <div class="flex flex-col gap-2">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-200">
            {{ trans('Email verification') }}
          </h1>
        </div>

        <div class="grid gap-6">
          <!-- Info Alert -->
          <div
            v-if="!verificationLinkSent"
            class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100"
          >
            <Icon icon="lucide:info" class="mt-0.5 h-4 w-4 shrink-0" />
            <p>
              {{
                trans(
                  "Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another."
                )
              }}
            </p>
          </div>

          <!-- Success Alert -->
          <div
            v-else
            class="flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-100"
          >
            <Icon icon="lucide:check-circle" class="mt-0.5 h-4 w-4 shrink-0" />
            <p>
              {{
                trans(
                  'A new verification link has been sent to the email address you provided during registration.'
                )
              }}
            </p>
          </div>

          <!-- Resend Button -->
          <form v-if="!verificationLinkSent" @submit.prevent="submit">
            <PrimaryButton type="submit" class="w-full" :disabled="form.processing">
              <Icon v-if="form.processing" icon="svg-spinners:180-ring" class="mr-2 h-4 w-4" />
              {{ trans('Resend Verification Email') }}
            </PrimaryButton>
          </form>

          <!-- Logout Button -->
          <PrimaryButton variant="outline" class="w-full" as-child>
            <Link
              :href="route('logout')"
              method="post"
              as="button"
              class="flex w-full items-center justify-center"
            >
              {{ trans('Logout') }}
            </Link>
          </PrimaryButton>
        </div>
      </div>
    </RightSide>
  </div>
</template>
