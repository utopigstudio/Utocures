<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage, usePoll } from '@inertiajs/vue3';
import { Check, LoaderCircle } from 'lucide-vue-next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { AdminNotificationItem, AdminNotificationsPayload, AppPageProps } from '@/types';
import { useI18n } from 'vue-i18n';

const page = usePage<AppPageProps>();
const processingId = ref<string | null>(null);
const markingAllAsRead = ref(false);
const activeTab = ref<'unread' | 'read'>('unread');
const { t } = useI18n();

usePoll(30000, {
  only: ['adminNotifications'],
});

const adminNotifications = computed<AdminNotificationsPayload>(() => {
  return page.props.adminNotifications ?? {
    count: 0,
    items: [],
    readCount: 0,
    readItems: [],
  };
});

const unreadCount = computed(() => adminNotifications.value.count ?? 0);
const notifications = computed(() => adminNotifications.value.items ?? []);
const readCount = computed(() => adminNotifications.value.readCount ?? 0);
const readNotifications = computed(() => adminNotifications.value.readItems ?? []);
const visibleNotifications = computed(() => activeTab.value === 'unread'
  ? notifications.value
  : readNotifications.value);

const formatCreatedAt = (value: string): string => {
  return new Date(value).toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const markAsRead = (notificationId: string, redirectTo?: string | null): void => {
  processingId.value = notificationId;

  router.patch(route('admin-notifications.update', notificationId), {}, {
    only: ['adminNotifications'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      if (redirectTo) {
        router.visit(redirectTo);
      }
    },
    onFinish: () => {
      processingId.value = null;
    },
  });
};

const markAllAsRead = (): void => {
  markingAllAsRead.value = true;

  router.patch(route('admin-notifications.mark-all-read'), {}, {
    only: ['adminNotifications'],
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      markingAllAsRead.value = false;
    },
  });
};

const hasExcerpt = (notification: AdminNotificationItem): boolean => {
  return Boolean(notification.data?.excerpt);
};

const unreadLabel = computed(() => {
  if (unreadCount.value === 0) {
    return t('notifications.all_caught_up');
  }

  return unreadCount.value === 1
    ? `${unreadCount.value} pendiente`
    : `${unreadCount.value} pendientes`;
});

const emptyLabel = computed(() => activeTab.value === 'unread'
  ? t('notifications.empty')
  : t('notifications.empty_read'));
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <button
        type="button"
        class="relative flex h-10 w-10 items-center justify-center cursor-pointer"
      >
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex flex-shrink-0 cursor-pointer">
            <circle cx="16" cy="16" r="15.75" fill="#EFF4FB" stroke="#E2E8F0" stroke-width="0.5"/>
            <g clip-path="url(#clip0_678_3164)">
            <path d="M22.7765 21.5854L22.2471 20.7648C22.1412 20.606 22.0883 20.4471 22.0883 20.2618V14.756C22.0883 13.1942 21.4265 11.7383 20.2088 10.653C19.2294 9.77949 17.9588 9.22361 16.6088 9.11773V8.58832C16.6088 8.27067 16.3441 7.97949 16 7.97949C15.6824 7.97949 15.3912 8.2442 15.3912 8.58832V9.09126C15.3383 9.09126 15.2853 9.09126 15.2324 9.11773C12.1618 9.46185 9.85884 11.9236 9.85884 14.8618V20.2618C9.83237 20.5265 9.77943 20.6589 9.72649 20.7383L9.22355 21.5854C9.06473 21.8501 9.06473 22.1677 9.22355 22.4324C9.38237 22.6707 9.64708 22.8295 9.93825 22.8295H15.4177V23.4118C15.4177 23.7295 15.6824 24.0207 16.0265 24.0207C16.3441 24.0207 16.6353 23.756 16.6353 23.4118V22.8295H22.0883C22.3794 22.8295 22.6441 22.6707 22.803 22.4324C22.9618 22.1677 22.9618 21.8501 22.7765 21.5854ZM10.5735 21.6383L10.7588 21.3207C10.9177 21.056 10.9971 20.7383 11.05 20.3677V14.8618C11.05 12.5324 12.903 10.5736 15.3647 10.3089C16.8735 10.1501 18.3559 10.6001 19.4412 11.553C20.3941 12.4001 20.9236 13.5383 20.9236 14.756V20.2618C20.9236 20.6589 21.0294 21.0295 21.2677 21.4001L21.4265 21.6383H10.5735Z" fill="#5D6F88"/>
            </g>
            <circle v-if="unreadCount > 0" cx="26.3529" cy="4.70588" r="3.70588" fill="#E11D48" stroke="white" stroke-width="2"/>
            <defs>
            <clipPath id="clip0_678_3164">
            <rect width="16.9412" height="16.9412" fill="white" transform="translate(7.52942 7.5293)"/>
            </clipPath>
            </defs>
        </svg>
      </button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" :side-offset="8" class="w-[24rem] p-0">
      <div class="border-b border-gray-100 px-4 py-3">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-sm font-semibold text-gray-900">{{ $t('notifications.title') }}</p>
            <p class="text-xs text-gray-500">
              {{ unreadLabel }}
            </p>
          </div>
          <button
            v-if="activeTab === 'unread' && unreadCount > 0"
            type="button"
            class="text-xs font-medium text-sky-700 transition-colors hover:text-sky-900 cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="markingAllAsRead"
            @click.prevent="markAllAsRead"
          >
            {{ $t('notifications.mark_all_as_read') }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 border-b border-gray-100 bg-gray-50/80 px-2 py-2">
        <button
          type="button"
          class="rounded-md px-3 py-2 text-sm font-medium transition-colors cursor-pointer"
          :class="activeTab === 'unread' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'unread'"
        >
          {{ $t('notifications.unread_tab') }}
          <span class="ml-1 text-xs " :class="activeTab === 'unread' ? 'bg-blue-600 text-white' : 'text-gray-400'">({{ unreadCount }})</span>
        </button>
        <button
          type="button"
          class="rounded-md px-3 py-2 text-sm font-medium transition-colors cursor-pointer"
          :class="activeTab === 'read' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'read'"
        >
          {{ $t('notifications.read_tab') }}
          <span class="ml-1 text-xs " :class="activeTab === 'read' ? 'bg-blue-600 text-white' : 'text-gray-400'">({{ readCount }})</span>
        </button>
      </div>

      <div v-if="visibleNotifications.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">
        {{ emptyLabel }}
      </div>

      <div v-else class="max-h-[28rem] overflow-y-auto">
        <div
          v-for="notification in visibleNotifications"
          :key="notification.id"
          class="border-b border-gray-100 px-4 py-3 last:border-b-0"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1 space-y-1">
              <p class="text-sm font-medium text-gray-900">
                {{ notification.title }}
              </p>
              <p class="text-sm text-gray-600">
                {{ notification.message }}
              </p>
              <p v-if="hasExcerpt(notification)" class="text-xs text-gray-500">
                {{ notification.data.excerpt }}
              </p>
              <p class="text-xs text-gray-400">
                {{ formatCreatedAt(notification.created_at) }}
              </p>
              <p v-if="activeTab === 'read' && notification.read_at" class="text-xs text-gray-400">
                {{ $t('notifications.read_on') }}: {{ formatCreatedAt(notification.read_at) }}
              </p>
            </div>

            <button
              v-if="activeTab === 'unread'"
              type="button"
              class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-900 cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="processingId === notification.id"
              @click.prevent="markAsRead(notification.id)"
            >
              <LoaderCircle v-if="processingId === notification.id" class="size-4 animate-spin" />
              <Check v-else class="size-4" />
            </button>
          </div>

          <div class="mt-3 flex items-center justify-between gap-3">
            <button
              v-if="notification.action_url"
              type="button"
              class="text-xs font-medium text-sky-700 transition-colors hover:text-sky-900"
              :disabled="processingId === notification.id"
              @click.prevent="markAsRead(notification.id, notification.action_url)"
            >
              {{ $t('notifications.view_detail') }}
            </button>

            <button
              v-if="activeTab === 'unread'"
              type="button"
              class="text-xs text-gray-500 transition-colors hover:text-gray-700 cursor-pointer"
              :disabled="processingId === notification.id"
              @click.prevent="markAsRead(notification.id)"
            >
              {{ $t('notifications.mark_as_read') }}
            </button>
          </div>
        </div>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
