<script setup lang="ts">
import PushNotificationToggle from '@/components/app/PushNotificationToggle.vue';
import { Button } from '@/components/ui/button';
import { AppLayout, AppLayoutClient } from '@/layouts';
import type { AppPageProps } from '@/types';
import { Link, router, useForm, usePage, usePoll } from '@inertiajs/vue3';
import { ArrowLeft, CheckCheck, MessageCircle, Search, Send, UserRound } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface MessageClient {
  id: string;
  name: string;
  email: string;
}

interface ConversationSummary extends MessageClient {
  has_access: boolean;
  unread_count: number;
  last_message: {
    body: string;
    sender_role: 'admin' | 'client';
    created_at: string;
  } | null;
}

interface MessageItem {
  id: string;
  body: string;
  sender_role: 'admin' | 'client';
  sender_name: string | null;
  is_mine: boolean;
  is_from_current_side: boolean;
  read_at: string | null;
  created_at: string;
}

interface Props {
  conversations: ConversationSummary[];
  selectedClient: MessageClient | null;
  messages: MessageItem[];
}

const props = defineProps<Props>();
const page = usePage<AppPageProps>();
const { locale, t } = useI18n();
const isAdmin = computed(() => page.props.auth.role === 'admin');
const layout = computed(() => (isAdmin.value ? AppLayout : AppLayoutClient));
const search = ref('');
const messageList = ref<HTMLElement | null>(null);
const isMarkingRead = ref(false);
const form = useForm({ body: '' });

const filteredConversations = computed(() => {
  const term = search.value.trim().toLocaleLowerCase(locale.value);

  if (!term) return props.conversations;

  return props.conversations.filter((conversation) => `${conversation.name} ${conversation.email}`.toLocaleLowerCase(locale.value).includes(term));
});

const incomingUnreadCount = computed(() => props.messages.filter((message) => !message.is_from_current_side && !message.read_at).length);

const pollProps = isAdmin.value ? ['conversations', 'messages', 'unreadMessagesCount'] : ['messages', 'unreadMessagesCount'];

usePoll(15000, {
  only: pollProps,
});

function submit(): void {
  const body = form.body.trim();

  if (!body || !props.selectedClient) {
    form.setError('body', t('messages.validation_required'));
    return;
  }

  form.body = body;
  const target = isAdmin.value ? route('messages.store', props.selectedClient.id) : route('client.messages.store');

  form.post(target, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      nextTick(scrollToBottom);
    },
  });
}

function markAsRead(): void {
  if (!props.selectedClient || incomingUnreadCount.value === 0 || isMarkingRead.value) return;

  isMarkingRead.value = true;
  const target = isAdmin.value ? route('messages.read', props.selectedClient.id) : route('client.messages.read');

  router.patch(
    target,
    {},
    {
      only: ['conversations', 'messages', 'unreadMessagesCount'],
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        isMarkingRead.value = false;
      },
    },
  );
}

function scrollToBottom(): void {
  if (messageList.value) {
    messageList.value.scrollTop = messageList.value.scrollHeight;
  }
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value));
}

function excerpt(value: string): string {
  return value.length > 70 ? `${value.slice(0, 70)}…` : value;
}

function handleServiceWorkerMessage(event: MessageEvent<{ type?: string }>): void {
  if (event.data?.type !== 'utocures:new-message') return;

  event.ports[0]?.postMessage({ handled: true });
  router.reload({
    only: pollProps,
  });
}

watch(
  () => props.messages.map((message) => `${message.id}:${message.read_at ?? ''}`).join('|'),
  () => {
    nextTick(scrollToBottom);
    markAsRead();
  },
);

onMounted(() => {
  navigator.serviceWorker?.addEventListener('message', handleServiceWorkerMessage);
  nextTick(scrollToBottom);
  markAsRead();
});

onBeforeUnmount(() => {
  navigator.serviceWorker?.removeEventListener('message', handleServiceWorkerMessage);
});
</script>

<template>
  <component :is="layout">
    <main class="h-full min-h-[calc(100vh-5rem)] bg-gray-50 p-4 md:p-8">
      <div class="mx-auto flex w-full flex-col gap-5">
        <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <div class="flex items-center gap-3">
              <div class="flex size-11 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                <MessageCircle class="size-6" />
              </div>
              <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $t('messages.title') }}</h1>
                <p class="text-sm text-slate-500">
                  {{ isAdmin ? $t('messages.subtitle_admin') : $t('messages.subtitle_client') }}
                </p>
              </div>
            </div>
          </div>
          <PushNotificationToggle />
        </header>

        <div
          class="grid min-h-[36rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
          :class="isAdmin ? 'lg:grid-cols-[20rem_minmax(0,1fr)]' : 'mx-auto w-full max-w-4xl'"
        >
          <aside v-if="isAdmin" class="border-b border-slate-200 lg:block lg:border-r lg:border-b-0" :class="selectedClient ? 'hidden' : 'block'">
            <div class="border-b border-slate-200 p-4">
              <h2 class="font-semibold text-slate-900">{{ $t('messages.clients') }}</h2>
              <div class="relative mt-3">
                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                <input
                  v-model="search"
                  type="search"
                  :placeholder="$t('messages.search_clients')"
                  class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pr-3 pl-9 text-sm transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
              </div>
            </div>

            <nav v-if="filteredConversations.length" class="max-h-72 overflow-y-auto lg:max-h-[calc(36rem-6.75rem)]">
              <Link
                v-for="conversation in filteredConversations"
                :key="conversation.id"
                :href="route('messages.show', conversation.id)"
                class="flex gap-3 border-b border-slate-100 p-4 transition-colors hover:bg-slate-50"
                :class="selectedClient?.id === conversation.id ? 'bg-blue-50 hover:bg-blue-50' : ''"
              >
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-2">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ conversation.name }}</p>
                    <span
                      v-if="conversation.unread_count"
                      class="flex min-h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-bold text-white"
                    >
                      {{ conversation.unread_count > 99 ? '99+' : conversation.unread_count }}
                    </span>
                  </div>
                  <p v-if="conversation.last_message" class="mt-1 truncate text-xs text-slate-500">
                    {{ conversation.last_message.sender_role === 'admin' ? `${$t('messages.administration')}: ` : ''
                    }}{{ excerpt(conversation.last_message.body) }}
                  </p>
                  <p v-else class="mt-1 text-xs text-slate-400">{{ conversation.email }}</p>
                  <p v-if="!conversation.has_access" class="mt-1 text-[11px] font-medium text-amber-700">
                    {{ $t('messages.no_portal_access') }}
                  </p>
                </div>
              </Link>
            </nav>
            <p v-else class="p-6 text-center text-sm text-slate-500">
              {{ conversations.length ? $t('messages.no_search_results') : $t('messages.no_clients') }}
            </p>
          </aside>

          <section v-if="selectedClient" class="flex min-h-0 flex-col">
            <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
              <Link
                v-if="isAdmin"
                :href="route('messages.index')"
                class="flex size-7 shrink-0 items-center justify-center rounded-full text-slate-600 transition-colors hover:bg-slate-100 lg:hidden"
                :aria-label="$t('messages.back_to_clients')"
              >
                <ArrowLeft class="size-5" />
              </Link>
              <div class="min-w-0 flex-1">
                <h2 class="truncate font-semibold text-slate-900">
                  {{ isAdmin ? selectedClient.name : $t('messages.administration') }}
                </h2>
                <p class="truncate text-xs text-slate-500">
                  {{ isAdmin ? selectedClient.email : $t('messages.subtitle_client') }}
                </p>
              </div>
            </div>

            <div ref="messageList" class="flex h-[27rem] flex-col gap-3 overflow-y-auto bg-slate-50/70 p-4 sm:p-6" aria-live="polite">
              <div v-if="!messages.length" class="m-auto max-w-sm text-center">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                  <MessageCircle class="size-6" />
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-500">{{ $t('messages.no_messages') }}</p>
              </div>

              <article
                v-for="message in messages"
                :key="message.id"
                class="flex max-w-[85%] flex-col"
                :class="message.is_from_current_side ? 'items-end self-end' : 'items-start self-start'"
              >
                <p class="mb-1 px-1 text-[11px] font-medium text-slate-500">
                  {{
                    message.is_mine
                      ? $t('messages.you')
                      : message.sender_name || (message.sender_role === 'admin' ? $t('messages.administration') : selectedClient.name)
                  }}
                </p>
                <div
                  class="rounded-2xl px-4 py-2.5 text-sm leading-5 shadow-sm"
                  :class="
                    message.is_from_current_side
                      ? 'rounded-br-md bg-blue-600 text-white'
                      : 'rounded-bl-md border border-slate-200 bg-white text-slate-800'
                  "
                >
                  <p class="break-words whitespace-pre-wrap">{{ message.body }}</p>
                </div>
                <div class="mt-1 flex items-center gap-1 px-1 text-[10px] text-slate-400">
                  <span>{{ formatDate(message.created_at) }}</span>
                  <template v-if="message.is_from_current_side">
                    <CheckCheck v-if="message.read_at" class="size-3.5 text-blue-600" />
                    <span>{{ message.read_at ? $t('messages.read') : $t('messages.sent') }}</span>
                  </template>
                </div>
              </article>
            </div>

            <form class="border-t border-slate-200 bg-white p-4" @submit.prevent="submit">
              <div class="flex items-end gap-3">
                <div class="min-w-0 flex-1">
                  <textarea
                    v-model="form.body"
                    name="body"
                    rows="2"
                    maxlength="2000"
                    :placeholder="$t('messages.write_message')"
                    class="block max-h-36 min-h-12 w-full resize-y rounded-xl border border-slate-200 px-4 py-3 text-sm transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    :aria-invalid="Boolean(form.errors.body)"
                    @input="form.clearErrors('body')"
                  />
                  <p v-if="form.errors.body" class="mt-1 text-xs text-red-600">{{ form.errors.body }}</p>
                </div>
                <Button type="submit" class="h-12 shrink-0" :disabled="form.processing || !form.body.trim()">
                  <Send class="size-4" />
                  <span class="hidden sm:inline">{{ form.processing ? $t('messages.sending') : $t('messages.send') }}</span>
                </Button>
              </div>
            </form>
          </section>

          <section v-else class="hidden min-h-[36rem] items-center justify-center p-8 text-center lg:flex">
            <div>
              <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <MessageCircle class="size-7" />
              </div>
              <p class="mt-4 text-sm text-slate-500">{{ $t('messages.select_client') }}</p>
            </div>
          </section>
        </div>
      </div>
    </main>
  </component>
</template>
