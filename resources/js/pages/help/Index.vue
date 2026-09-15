<script setup lang="ts">
import HelpSteps from '@/components/help/HelpSteps.vue';
import { AppLayout, AppLayoutClient, AppLayoutEmployee, LayoutBasic } from '@/layouts';
import type { AppPageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, BookOpen, CircleHelp } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

interface Article {
  id: string;
  title: string;
  category: string;
  description: string;
  roles: string[];
  href: string | null;
  steps: string[];
  tasks: { title: string; steps: string[]; result: string }[];
  effects: string[];
  questions: { question: string; answer: string; guide?: string }[];
  related: string[];
}

const props = defineProps<{ articles: Article[]; selectedArticle: string | null }>();
const page = usePage<AppPageProps>();
const layout = computed(() =>
  page.props.auth.role === 'admin' ? AppLayout : page.props.auth.role === 'employee' ? AppLayoutEmployee : AppLayoutClient,
);
const current = computed(() => props.articles.find((article) => article.id === props.selectedArticle));
const groups = computed(() =>
  [...new Set(props.articles.map((article) => article.category))].map((category) => ({
    category,
    articles: props.articles.filter((article) => article.category === category),
  })),
);
const related = computed(() => props.articles.filter((article) => current.value?.related.includes(article.id)));
const articleUrl = (id: string) => route('help.index', { article: id });

onMounted(() => {
  const section = window.location.hash.slice(1);
  if (['steps', 'effects', 'questions'].includes(section)) {
    document.getElementById(section)?.scrollIntoView();
  }
});
</script>

<template>
  <Head :title="current ? `${current.title} · ${$t('help.title')}` : $t('help.title')" />
  <component :is="layout">
    <LayoutBasic :title="$t('help.title')" :icon="CircleHelp">
      <main class="mx-auto w-full max-w-6xl space-y-6 px-4 pb-8 md:px-0">
        <template v-if="current">
          <Link
            :href="route('help.index')"
            class="inline-flex items-center gap-2 rounded text-sm font-semibold text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4"
            preserve-state
          >
            <ArrowLeft class="size-4" aria-hidden="true" /> {{ $t('help.back') }}
          </Link>
          <article :key="current.id" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
            <p class="text-sm font-semibold text-blue-700">{{ current.category }}</p>
            <h2 class="mt-3 text-2xl font-bold text-gray-900 sm:text-3xl">{{ current.title }}</h2>
            <p class="mt-4 max-w-3xl leading-7 text-gray-600">{{ current.description }}</p>
            <Link
              v-if="current.href"
              :href="current.href"
              class="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white hover:bg-blue-800"
            >
              {{ $t('help.go') }} <ArrowRight class="size-4" aria-hidden="true" />
            </Link>
            <nav :aria-label="$t('help.contents')" class="my-8 flex flex-wrap gap-x-5 gap-y-3 border-y border-gray-100 py-4 text-sm text-blue-700">
              <a v-if="current.tasks.length" href="#steps" class="underline underline-offset-4">{{ $t('help.steps') }}</a>
              <a v-if="current.effects.length" href="#effects" class="underline underline-offset-4">{{ $t('help.effects') }}</a>
              <a href="#questions" class="underline underline-offset-4">{{ $t('help.questions') }}</a>
            </nav>
            <div class="max-w-3xl space-y-10 leading-7 text-gray-700">
              <section v-if="current.tasks.length" id="steps" class="scroll-mt-28">
                <h3 class="mb-4 text-xl font-bold text-gray-900">{{ $t('help.steps') }}</h3>
                <HelpSteps :steps="current.steps" :tasks="current.tasks" />
              </section>
              <section v-if="current.effects.length" id="effects" class="scroll-mt-28 rounded-xl border border-blue-100 bg-blue-50 p-5">
                <h3 class="mb-4 text-xl font-bold text-gray-900">{{ $t('help.effects') }}</h3>
                <ul class="list-disc space-y-3 pl-5">
                  <li v-for="effect in current.effects" :key="effect">{{ effect }}</li>
                </ul>
              </section>
              <section id="questions" class="scroll-mt-28">
                <h3 class="mb-4 text-xl font-bold text-gray-900">{{ $t('help.questions') }}</h3>
                <details v-for="item in current.questions" :key="item.question" class="mb-3 rounded-xl border border-gray-200 p-4">
                  <summary class="cursor-pointer font-semibold text-gray-900">{{ item.question }}</summary>
                  <p class="mt-3">{{ item.answer }}</p>
                  <Link
                    v-if="item.guide && articles.some((article) => article.id === item.guide)"
                    :href="articleUrl(item.guide)"
                    class="mt-3 inline-block font-semibold text-blue-700 underline"
                    >{{ $t('help.read') }}</Link
                  >
                </details>
              </section>
            </div>
          </article>
          <section v-if="related.length">
            <h2 class="mb-3 text-lg font-bold">{{ $t('help.related') }}</h2>
            <div class="flex flex-wrap gap-3">
              <Link
                v-for="article in related"
                :key="article.id"
                :href="articleUrl(article.id)"
                class="rounded-lg border border-gray-200 bg-white px-4 py-3 text-blue-700 hover:bg-blue-50"
                >{{ article.title }}</Link
              >
            </div>
          </section>
        </template>
        <template v-else>
          <p class="text-gray-600">{{ $t('help.choose_section') }}</p>
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <section v-for="group in groups" :key="group.category" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
              <BookOpen class="mb-3 size-5 text-blue-700" aria-hidden="true" />
              <h2 class="mb-4 font-bold text-gray-900">{{ group.category }}</h2>
              <div class="space-y-4">
                <Link
                  v-for="article in group.articles"
                  :key="article.id"
                  :href="articleUrl(article.id)"
                  class="block rounded-lg border border-gray-100 p-3 hover:border-blue-300 hover:bg-blue-50"
                >
                  <h3 class="font-semibold text-blue-700">{{ article.title }}</h3>
                  <p class="mt-2 text-sm leading-6 text-gray-600">{{ article.description }}</p>
                  <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-700"
                    >{{ $t('help.read') }} <ArrowRight class="size-4" aria-hidden="true"
                  /></span>
                </Link>
              </div>
            </section>
          </div>
        </template>
      </main>
    </LayoutBasic>
  </component>
</template>
