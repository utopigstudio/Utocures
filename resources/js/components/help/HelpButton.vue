<script setup lang="ts">
import HelpSteps from '@/components/help/HelpSteps.vue';
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
import { helpArticleFor } from '@/lib/help';
import type { AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { CircleHelp, ExternalLink, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
  defineProps<{
    article?: string;
    section?: 'steps' | 'effects' | 'questions';
    label?: string;
    compact?: boolean;
    linkOnly?: boolean;
  }>(),
  { compact: true },
);

interface Guide {
  title: string;
  description: string;
  steps: string[];
  tasks: { title: string; steps: string[]; result: string }[];
  effects: string[];
  questions: { question: string; answer: string; guide?: string }[];
}

const page = usePage<AppPageProps>();
const articleId = computed(() => props.article ?? helpArticleFor(page.component, page.props.auth.role));
const fullUrl = computed(() => route('help.index', { article: articleId.value }) + (props.section ? `#${props.section}` : ''));
const open = ref(false);
const guide = ref<Guide | null>(null);
const loading = ref(false);
const failed = ref(false);
const attempt = ref(0);
const activeSection = ref<'steps' | 'effects' | 'questions'>('steps');
const sections = computed(() =>
  (['steps', 'effects', 'questions'] as const).filter((section) => (section === 'steps' ? guide.value?.tasks.length : guide.value?.[section].length)),
);
watch(sections, (available) => {
  if (available.length && !available.includes(activeSection.value)) activeSection.value = available[0];
});
watch(open, (isOpen) => {
  if (isOpen) activeSection.value = props.section ?? 'steps';
});

watch([open, articleId, () => page.props.locale, attempt], async ([isOpen, article], _, onCleanup) => {
  if (!isOpen || !article) return;
  const controller = new AbortController();
  onCleanup(() => controller.abort());
  guide.value = null;
  failed.value = false;
  loading.value = true;
  try {
    const response = await fetch(route('help.content', { article }), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
      signal: controller.signal,
    });
    if (!response.ok) throw new Error('Help unavailable');
    const result = await response.json();
    if (!controller.signal.aborted) guide.value = result;
  } catch {
    if (!controller.signal.aborted) failed.value = true;
  } finally {
    if (!controller.signal.aborted) loading.value = false;
  }
});
</script>

<template>
  <a
    v-if="linkOnly"
    :href="route('help.index')"
    class="inline-flex shrink-0 items-center gap-2 rounded-lg p-2 align-middle text-sm font-semibold text-blue-700 hover:bg-blue-50"
    :aria-label="label || $t('help.title')"
    :title="label || $t('help.title')"
  >
    <CircleHelp class="size-5" aria-hidden="true" />
    <span v-if="!compact" class="hidden sm:inline">{{ label || $t('help.short_title') }}</span>
  </a>
  <Dialog v-else-if="articleId" v-model:open="open">
    <DialogTrigger as-child>
      <button
        type="button"
        :aria-label="label || $t('help.context')"
        :title="label || $t('help.context')"
        class="inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 rounded-lg p-2 align-middle text-sm font-semibold text-blue-700 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
        @click.stop
      >
        <CircleHelp class="size-5" aria-hidden="true" />
        <span v-if="!compact" class="hidden sm:inline">{{ $t('help.short_title') }}</span>
      </button>
    </DialogTrigger>
    <DialogContent class="max-h-[85dvh] overflow-y-auto sm:max-w-3xl" :show-close-button="false">
      <DialogHeader>
        <div class="flex items-start justify-between gap-4">
          <DialogTitle class="text-xl leading-7">{{ guide?.title || label || $t('help.title') }}</DialogTitle>
          <DialogClose as-child>
            <button type="button" :aria-label="$t('help.close')" class="shrink-0 cursor-pointer rounded-lg p-2 text-gray-600 hover:bg-gray-100">
              <X class="size-5" aria-hidden="true" />
            </button>
          </DialogClose>
        </div>
        <DialogDescription class="leading-6">{{ guide?.description || $t('help.keep_work') }}</DialogDescription>
      </DialogHeader>
      <p v-if="loading" role="status" class="py-6 text-sm text-gray-600">{{ $t('help.loading') }}</p>
      <div v-else-if="failed" role="alert" class="rounded-lg bg-amber-50 p-4 text-sm leading-6">
        <p>{{ $t('help.load_error') }}</p>
        <button type="button" class="mt-2 cursor-pointer font-semibold text-blue-700 underline" @click="attempt++">{{ $t('help.retry') }}</button>
      </div>
      <div v-else-if="guide" class="space-y-6 text-base leading-7 text-gray-700">
        <nav :aria-label="$t('help.contents')" class="flex flex-wrap gap-2">
          <button
            v-for="item in sections"
            :key="item"
            type="button"
            :aria-pressed="activeSection === item"
            class="cursor-pointer rounded-lg border px-3 py-2 text-sm font-semibold"
            :class="activeSection === item ? 'border-blue-700 bg-blue-700 text-white' : 'border-gray-200 text-blue-700'"
            @click="activeSection = item"
          >
            {{ $t(`help.${item}`) }}
          </button>
        </nav>
        <section v-if="activeSection === 'steps'">
          <h3 class="mb-3 font-bold text-gray-900">{{ $t('help.steps') }}</h3>
          <HelpSteps :steps="guide.steps" :tasks="guide.tasks" />
        </section>
        <section v-if="activeSection === 'effects'" class="rounded-xl bg-blue-50 p-4">
          <h3 class="mb-3 font-bold text-gray-900">{{ $t('help.effects') }}</h3>
          <ul class="list-disc space-y-3 pl-5">
            <li v-for="effect in guide.effects" :key="effect">{{ effect }}</li>
          </ul>
        </section>
        <section v-if="activeSection === 'questions'">
          <h3 class="mb-3 font-bold text-gray-900">{{ $t('help.questions') }}</h3>
          <details v-for="item in guide.questions" :key="item.question" class="mb-3 rounded-lg border border-gray-200 p-3">
            <summary class="cursor-pointer font-semibold">{{ item.question }}</summary>
            <p class="mt-2">{{ item.answer }}</p>
            <a
              v-if="item.guide"
              :href="route('help.index', { article: item.guide })"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-3 inline-flex items-center gap-2 font-semibold text-blue-700 underline"
              >{{ $t('help.full_guide') }} <ExternalLink class="size-4" aria-hidden="true"
            /></a>
          </details>
        </section>
      </div>
      <DialogFooter class="sticky -bottom-6 border-t bg-white py-4">
        <a
          :href="fullUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50"
        >
          {{ $t('help.full_guide') }} <ExternalLink class="size-4 shrink-0" aria-hidden="true" />
        </a>
        <DialogClose as-child>
          <button type="button" class="cursor-pointer rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
            {{ $t('help.close') }}
          </button>
        </DialogClose>
      </DialogFooter>
    </DialogContent>
  </Dialog>
  <a
    v-else
    :href="route('help.index')"
    class="inline-flex items-center gap-2 rounded-lg p-2 text-sm font-semibold text-blue-700"
    :aria-label="$t('help.title')"
  >
    <CircleHelp class="size-5" aria-hidden="true" />
    <span v-if="!compact" class="hidden sm:inline">{{ $t('help.short_title') }}</span>
  </a>
</template>
