<script setup lang="ts">
import { ref } from 'vue';
import { AuthLayout } from '@/layouts';
import { TextLink } from '@/components/ui/basic';
import { Button } from '@/components/ui/button';
import { InputText } from '@/components/ui/input';
import { Form } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
  status?: string;
}>();

const action = ref(route('password.email'))
</script>

<template>
  <AuthLayout :title="$t('auth.forgot.title')" class-card-title="text-3xl md:text-3xl" class-card-content="pt-0 pb-0" class-logo-header="md:mb-19">

    <div class="space-y-6">
      <Form method="post" :action="action" v-slot="{ errors, processing }">
        <div class="grid gap-2">
          <InputText
            type="email"
            name="email"
            autocomplete="off"
            :autofocus="true"
            placeholder="email@example.com"
            :label="$t('auth.forgot.email_label')"
            :required="true"
            :error="errors.email"
          />
        </div>

        <div class="my-6 flex items-center justify-start">
          <Button class="w-full" type="submit" :disabled="processing" @click="action = route('password.email')">
            <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
            {{ $t('auth.forgot.button') }}
          </Button>
        </div>

        <div class="my-6 flex items-center justify-start">
          <Button variant="outline" type="submit" class="w-full" :disabled="processing" @click="action = route('password.magiclink')">
            <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
            {{ $t('auth.forgot.button_magic_link') }}
          </Button>
          {{ status }}
        </div>
      </Form>

      <div class="space-x-1 text-center text-sm">
        <span>{{ $t('auth.forgot.return') }}</span>
        <TextLink :href="route('login')" class="leading-5 font-semibold text-blue-600 no-underline">{{ $t('auth.forgot.return_link') }}</TextLink>
      </div>
    </div>
  </AuthLayout>
</template>
