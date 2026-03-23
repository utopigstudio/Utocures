<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { InputText } from '@/components/ui/input';
import { AuthLayout } from '@/layouts';
import { Form } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
  token: string;
  email: string;
}

const props = defineProps<Props>()
const inputEmail = ref(props.email);
</script>

<template>
  <AuthLayout :title="$t('auth.reset.title')" :description="$page.props.token_error ? $t($page.props.token_error as string) : $t('auth.reset.subtitle')">
    <Form
      v-if="!$page.props.token_error"
      method="post"
      :action="route('password.store')"
      :transform="(data) => ({ ...data, token, email })"
      :reset-on-success="['password', 'password_confirmation']"
      v-slot="{ errors, processing }"
    >
      <div class="grid gap-6">
        <div class="grid gap-2">
          <InputText
            :label="$t('auth.reset.email_label')"
            type="email"
            name="email"
            autocomplete="email"
            v-model="inputEmail"
            class="block w-full mt-1"
            :error="errors.email"
            readonly
          />
        </div>

        <div class="grid gap-2">
          <InputText
            :label="$t('auth.reset.password_label')"
            type="password"
            name="password"
            autocomplete="new-password"
            class="block w-full mt-1"
            autofocus
            placeholder="Password"
            :error="errors.password"
          />
        </div>

        <div class="grid gap-2">
          <InputText
            :label="$t('auth.reset.password_confirmation_label')"
            type="password"
            name="password_confirmation"
            autocomplete="new-password"
            class="block w-full mt-1"
            placeholder="Confirm password"
            :error="errors.password_confirmation"
          />
        </div>

        <Button type="submit" class="w-full mt-4" :disabled="processing">
          <LoaderCircle v-if="processing" class="w-4 h-4 animate-spin" />
          {{ $t('auth.reset.button') }}
        </Button>
      </div>
    </Form>
  </AuthLayout>
</template>
