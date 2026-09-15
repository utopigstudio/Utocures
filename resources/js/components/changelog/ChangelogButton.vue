<script setup lang="ts">
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import type { AppPageProps, ChangelogEntry } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { ExternalLink, Megaphone, X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const AUTO_SHOWN_KEY = 'changelog:auto-shown-version';

const page = usePage<AppPageProps>();
const changelog = computed(() => page.props.changelog);
const open = ref(false);
const entries = ref<ChangelogEntry[]>([]);
const loading = ref(false);
const failed = ref(false);
const attempt = ref(0);

async function loadEntries(): Promise<void> {
  loading.value = true;
  failed.value = false;
  try {
    const response = await fetch(route('changelog.content'), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!response.ok) throw new Error('Changelog unavailable');
    const result = await response.json();
    entries.value = result.entries ?? [];
  } catch {
    failed.value = true;
  } finally {
    loading.value = false;
  }
}

function markSeen(): void {
  const version = changelog.value?.latestVersion;
  if (!version || !changelog.value?.hasUnseen) return;
  router.patch(
    route('changelog.seen'),
    { version },
    { preserveState: true, preserveScroll: true, only: ['changelog'] },
  );
}

watch([open, attempt], ([isOpen]) => {
  if (isOpen) loadEntries();
});

watch(open, (isOpen, wasOpen) => {
  if (!isOpen && wasOpen) markSeen();
});

onMounted(() => {
  if (!changelog.value?.hasUnseen || !changelog.value.latestVersion) return;
  try {
    if (sessionStorage.getItem(AUTO_SHOWN_KEY) === changelog.value.latestVersion) return;
    sessionStorage.setItem(AUTO_SHOWN_KEY, changelog.value.latestVersion);
  } catch {
    // Ignore storage errors (private mode, blocked cookies, etc.).
  }
  open.value = true;
});
</script>

<template>
  <Dialog v-model:open="open">
    <DialogTrigger as-child>
      <button
        type="button"
        :aria-label="$t('changelog.short_title')"
        :title="$t('changelog.short_title')"
        class="relative inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 rounded-lg p-2 align-middle text-sm font-semibold text-blue-700 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
        @click.stop
      >
        <Megaphone class="size-5" aria-hidden="true" />
        <span v-if="changelog?.hasUnseen" class="absolute top-1 right-1 size-2 rounded-full bg-rose-600" aria-hidden="true" />
      </button>
    </DialogTrigger>
    <DialogContent class="max-h-[85dvh] overflow-y-auto sm:max-w-2xl" :show-close-button="false">
      <DialogHeader>
        <div class="flex items-start justify-between gap-4">
          <DialogTitle class="text-xl leading-7">{{ $t('changelog.title') }}</DialogTitle>
          <DialogClose as-child>
            <button type="button" :aria-label="$t('changelog.close')" class="shrink-0 cursor-pointer rounded-lg p-2 text-gray-600 hover:bg-gray-100">
              <X class="size-5" aria-hidden="true" />
            </button>
          </DialogClose>
        </div>
        <DialogDescription class="leading-6">{{ $t('changelog.description') }}</DialogDescription>
      </DialogHeader>
      <p v-if="loading" role="status" class="py-6 text-sm text-gray-600">{{ $t('changelog.loading') }}</p>
      <div v-else-if="failed" role="alert" class="rounded-lg bg-amber-50 p-4 text-sm leading-6">
        <p>{{ $t('changelog.load_error') }}</p>
        <button type="button" class="mt-2 cursor-pointer font-semibold text-blue-700 underline" @click="attempt++">{{ $t('changelog.retry') }}</button>
      </div>
      <p v-else-if="!entries.length" class="py-6 text-sm text-gray-600">{{ $t('changelog.empty') }}</p>
      <div v-else class="space-y-6 text-base leading-7 text-gray-700">
        <section v-for="entry in entries" :key="entry.version" class="rounded-xl border border-gray-200 p-4">
          <div class="mb-3 flex items-baseline justify-between gap-2">
            <h3 class="font-bold text-gray-900">{{ entry.version }}</h3>
            <span class="text-sm text-gray-500">{{ entry.date }}</span>
          </div>
          <ul class="space-y-2">
            <li v-for="(item, index) in entry.items" :key="index" class="flex items-start gap-2">
              <span
                class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="{
                  'bg-emerald-100 text-emerald-800': item.type === 'added',
                  'bg-blue-100 text-blue-800': item.type === 'changed',
                  'bg-amber-100 text-amber-800': item.type === 'fixed',
                }"
              >{{ $t(`changelog.type_${item.type}`) }}</span>
              <span>
                {{ item.text }}
                <a
                  v-if="item.guide"
                  :href="route('help.index', { article: item.guide })"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="ml-1 inline-flex items-center gap-1 font-semibold text-blue-700 underline whitespace-nowrap"
                  >{{ $t('changelog.view_guide') }} <ExternalLink class="size-3.5" aria-hidden="true"
                /></a>
              </span>
            </li>
          </ul>
        </section>
      </div>
      <DialogFooter class="sticky -bottom-6 border-t bg-white py-4">
        <DialogClose as-child>
          <button type="button" class="cursor-pointer rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
            {{ $t('changelog.close') }}
          </button>
        </DialogClose>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
