<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { BellOff, BellRing, LoaderCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const page = usePage<AppPageProps>();
const { t } = useI18n();
const isBusy = ref(false);
const isSubscribed = ref(false);
const permission = ref<NotificationPermission>('default');
const errorMessage = ref('');
const browserSupported = ref(false);

const isAvailable = computed(() => browserSupported.value && page.props.webPush.enabled && Boolean(page.props.webPush.publicKey));
const isDenied = computed(() => permission.value === 'denied');
const buttonLabel = computed(() => {
  if (!isAvailable.value) return t('messages.push_unsupported');
  if (isDenied.value) return t('messages.push_denied');
  if (isSubscribed.value) return t('messages.push_enabled');

  return t('messages.push_enable');
});

function csrfToken(): string {
  return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

function applicationServerKey(value: string): Uint8Array<ArrayBuffer> {
  const padding = '='.repeat((4 - (value.length % 4)) % 4);
  const base64 = `${value}${padding}`.replace(/-/g, '+').replace(/_/g, '/');
  const bytes = window.atob(base64);

  return Uint8Array.from(bytes, (character) => character.charCodeAt(0));
}

async function saveSubscription(subscription: PushSubscription): Promise<void> {
  const subscriptionData = subscription.toJSON();
  const response = await fetch(route('push-subscriptions.store'), {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: JSON.stringify({
      endpoint: subscription.endpoint,
      keys: subscriptionData.keys,
      content_encoding: 'aes128gcm',
    }),
  });

  if (!response.ok) {
    throw new Error(`Unable to save push subscription (${response.status})`);
  }
}

async function removeSubscription(subscription: PushSubscription): Promise<void> {
  const response = await fetch(route('push-subscriptions.destroy'), {
    method: 'DELETE',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: JSON.stringify({ endpoint: subscription.endpoint }),
  });

  if (!response.ok) {
    throw new Error(`Unable to delete push subscription (${response.status})`);
  }
}

async function enableNotifications(): Promise<void> {
  if (!isAvailable.value || !page.props.webPush.publicKey) return;

  isBusy.value = true;
  errorMessage.value = '';

  try {
    permission.value = await Notification.requestPermission();

    if (permission.value !== 'granted') return;

    const registration = await navigator.serviceWorker.ready;
    let subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
      subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey(page.props.webPush.publicKey),
      });
    }

    await saveSubscription(subscription);
    isSubscribed.value = true;
  } catch {
    errorMessage.value = t('messages.push_error');
  } finally {
    isBusy.value = false;
  }
}

async function disableNotifications(): Promise<void> {
  isBusy.value = true;
  errorMessage.value = '';

  try {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (subscription) {
      await removeSubscription(subscription);
      await subscription.unsubscribe();
    }

    isSubscribed.value = false;
  } catch {
    errorMessage.value = t('messages.push_error');
  } finally {
    isBusy.value = false;
  }
}

async function toggleNotifications(): Promise<void> {
  if (isSubscribed.value) {
    await disableNotifications();
    return;
  }

  await enableNotifications();
}

onMounted(async () => {
  browserSupported.value = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

  if (!isAvailable.value) return;

  permission.value = Notification.permission;

  try {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (subscription) {
      isSubscribed.value = true;
      await saveSubscription(subscription);
    }
  } catch {
    errorMessage.value = t('messages.push_error');
  }
});
</script>

<template>
  <div class="flex flex-col items-start gap-1 sm:items-end">
    <Button
      type="button"
      variant="outline"
      size="sm"
      :disabled="isBusy || !isAvailable || isDenied"
      :aria-pressed="isSubscribed"
      @click="toggleNotifications"
    >
      <LoaderCircle v-if="isBusy" class="size-4 animate-spin" />
      <BellRing v-else-if="isSubscribed" class="size-4 text-blue-600" />
      <BellOff v-else class="size-4" />
      {{ buttonLabel }}
    </Button>
    <p v-if="errorMessage" class="max-w-72 text-xs text-red-600">{{ errorMessage }}</p>
  </div>
</template>
