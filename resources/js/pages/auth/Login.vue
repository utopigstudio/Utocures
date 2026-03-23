<script setup lang="ts">
import { AuthLayout } from '@/layouts';
import { TextLink } from '@/components/ui/basic';
import { Button } from '@/components/ui/button';
import { InputText, InputCheckbox } from '@/components/ui/input';
import { Form } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
  canResetPassword: boolean;
}>();
</script>

<template>
  <AuthLayout :title="$t('auth.login.title')" :description="$t('auth.login.subtitle')">

    <Form method="post" :action="route('login')" :reset-on-success="['password']" v-slot="{ errors, processing }" class="flex flex-col gap-6">
      <div class="grid gap-6">
        <div class="grid gap-2">
          <InputText
            type="email"
            name="email"
            :label="$t('auth.login.email_label')"
            required
            autofocus
            :tabindex="1"
            autocomplete="email"
            :error="errors.email"
            class="mb-0"
          />
        </div>

        <div class="grid gap-2">
          <InputText
            id="password"
            type="password"
            name="password"
            :label="$t('auth.login.password_label')"
            required
            :tabindex="2"
            autocomplete="current-password"
            :error="errors.password"
            class="mb-0"
          />
        </div>

        <div class="flex flex-col md:flex-row-reverse md:items-center md:justify-between gap-6 md:gap-3">
          <div>
            <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm leading-5 font-semibold text-blue-600 no-underline" :tabindex="5">
              {{ $t('auth.login.forgot') }} 
            </TextLink>
          </div>
          
          <div class="flex items-center">
            <InputCheckbox
              name="remember"
              :tabindex="3"
              :label="$t('auth.login.remember')"
              class="flex items-center space-x-2 mb-0"
              :error="errors.remember"
            />
          </div>
        </div>

        <Button type="submit" class="w-full mt-0" :tabindex="4" :disabled="processing">
          <LoaderCircle v-if="processing" class="w-4 h-4 animate-spin" />
          {{ $t('auth.login.button') }}
        </Button>
      </div>
    </Form>
  </AuthLayout>
</template>
