<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-vue-next';
import type { TabItem, BreadcrumbItem } from '@/types';
import { Link, usePage, router } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/ui/breadcrumb/Breadcrumbs.vue';

withDefaults(
  defineProps<{
    title?: string;
    tabs: Array<TabItem>;
    breadcrumbs?: BreadcrumbItem[];
    backTo?: string;
  }>(),
  {
    breadcrumbs: () => [],
  },
);

const page = usePage();
const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';

function getPathnameFromUrl(url: string): string {
  try {
    return new URL(url).pathname;
  } catch {
    return url;
  }
}

function goBack() {
  if (window.history.length > 1) {
    window.history.back()
  } else {
    router.visit(route('dashboard'))
  }
}
</script>

<template>
  <div class="md:p-8 bg-gray-50 h-full">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center pt-3 pb-6 md:pb-12 px-4 md:px-0">
      <div>
        <template v-if="title">
          <h1 class="text-2xl font-bold">{{ title }}</h1>
        </template>
        <template v-else-if="backTo">
          <Link :href="route(backTo)" class="flex items-center gap-2 justify-start p-0 h-auto cursor-pointer">
            <ArrowLeft class="text-gray-500"/>
            <span class="text-gray-500 text-lg leading-7 font-normal">{{ $t('layout.back') }}</span>
          </Link>
        </template>
        <div v-else @click="goBack">
          <ArrowLeft class="text-gray-500"/>
          <span class="text-gray-500 text-lg leading-7 font-normal">{{ $t('layout.back') }}</span>
        </div>
      </div>
      <div>
        <template v-if="breadcrumbs && breadcrumbs.length > 0">
          <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </template>
      </div>
    </div>
    <div class="flex flex-col">
      <aside class="w-full">
        <nav class="flex flex-row">
          <Button
            v-for="item in tabs"
            :key="item.href"
            variant="ghost"
            :class="[
              'justify-start text-sm leading-5 font-medium rounded-none md:rounded-xl mr-1 md:mr-0',
              currentPath === getPathnameFromUrl(item.href) 
                ? 'bg-blue-gray-600 text-white hover:bg-blue-gray-600 hover:text-white' 
                : 'text-gray-600 bg-white md:bg-transparent'
            ]"
            as-child
          >
            <Link :href="item.href">
              {{ item.title }}
            </Link>
          </Button>
        </nav>
      </aside>

      <div class="flex flex-col lg:flex-row mt-6">
        <div class="flex-1">
          <section class="space-y-6">
            <slot />
          </section>
        </div>
      </div>
    </div>
  </div>
</template>
