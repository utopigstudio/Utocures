<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { InputText } from '@/components/ui/input';
import type { Client } from '@/types';
import { Form } from '@inertiajs/vue3';
import { KeyRound, Mail, Power, RotateCcw, Save } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  client: Client;
}

const props = defineProps<Props>();
const user = computed(() => props.client.user);
const createdAt = computed(() => {
  if (!user.value?.created_at) return '—';

  return new Date(user.value.created_at).toLocaleDateString('es-ES');
});
</script>

<template>
  <Card class="mb-6">
    <CardHeader>
      <CardTitle help-article="client-access" :help-label="$t('help.topic', { topic: $t('clients.portal_access') })" class="flex items-center gap-2 text-xl">
        <KeyRound class="h-5 w-5 text-blue-600" />
        {{ $t('clients.portal_access') }}
      </CardTitle>
      <CardDescription>{{ $t('clients.portal_access_description') }}</CardDescription>
    </CardHeader>

    <CardContent>
      <div class="mb-6 flex flex-wrap items-center gap-3">
        <span class="text-sm font-medium text-blue-gray-600">{{ $t('clients.access_status') }}:</span>
        <span
          class="rounded-full px-3 py-1 text-xs font-semibold"
          :class="user?.is_active ? 'bg-green-100 text-green-700' : user ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'"
        >
          {{ user?.is_active ? $t('clients.access_active') : user ? $t('clients.access_inactive_status') : $t('clients.access_missing') }}
        </span>
        <span v-if="user" class="text-sm text-blue-gray-500"> {{ $t('clients.access_created_at') }}: {{ createdAt }} </span>
      </div>

      <Form
        v-if="!user"
        method="post"
        :action="route('clients.access.store', props.client.id)"
        class="max-w-2xl space-y-4"
        v-slot="{ errors, processing }"
      >
        <InputText
          name="email"
          type="email"
          autocomplete="email"
          :label="$t('clients.access_email')"
          :default-value="props.client.email"
          :required="true"
          :error="errors.email"
        />
        <Button type="submit" :disabled="processing">
          <Mail class="size-4" />
          {{ $t('clients.create_access_and_invite') }}
        </Button>
      </Form>

      <template v-else>
        <Form method="put" :action="route('clients.access.update', props.client.id)" class="max-w-2xl space-y-4" v-slot="{ errors, processing }">
          <InputText
            name="email"
            type="email"
            autocomplete="email"
            :label="$t('clients.access_email')"
            :default-value="user.email"
            :required="true"
            :error="errors.email"
          />
          <Button type="submit" :disabled="processing">
            <Save class="size-4" />
            {{ $t('clients.save_access') }}
          </Button>
        </Form>

        <div class="mt-8 border-t border-gray-200 pt-6">
          <p class="mb-4 text-sm font-medium text-blue-gray-700">{{ $t('clients.access_actions') }}</p>
          <div class="flex flex-wrap gap-3">
            <Form
              v-if="!user.invitation_accepted_at"
              method="post"
              :action="route('clients.access.invitation', props.client.id)"
              v-slot="{ processing }"
            >
              <Button type="submit" variant="outline" :disabled="processing || !user.is_active">
                <Mail class="size-4" />
                {{ $t('clients.resend_invitation') }}
              </Button>
            </Form>

            <Form method="post" :action="route('clients.access.password-reset', props.client.id)" v-slot="{ processing }">
              <Button type="submit" variant="outline" :disabled="processing || !user.is_active">
                <RotateCcw class="size-4" />
                {{ $t('clients.reset_password') }}
              </Button>
            </Form>

            <Form v-if="user.is_active" method="delete" :action="route('clients.access.destroy', props.client.id)" v-slot="{ processing }">
              <Button type="submit" variant="destructive" :disabled="processing">
                <Power class="size-4" />
                {{ $t('clients.disable_access') }}
              </Button>
            </Form>

            <Form v-else method="patch" :action="route('clients.access.activate', props.client.id)" v-slot="{ processing }">
              <Button type="submit" :disabled="processing">
                <Power class="size-4" />
                {{ $t('clients.enable_access') }}
              </Button>
            </Form>
          </div>
        </div>
      </template>
    </CardContent>
  </Card>
</template>
