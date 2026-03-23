<script setup lang="ts">
import type { BreadcrumbItem } from '@/types';
import Breadcrumbs from '@/components/ui/breadcrumb/Breadcrumbs.vue';
import { UsersRound, ArrowLeft } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

withDefaults(
  defineProps<{
    title?: string;
    breadcrumbs?: BreadcrumbItem[];
    icon?: any;
  }>(),
  {
    breadcrumbs: () => [],
  },
);

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
          <div class="flex items-center gap-2">
            <div class="flex items-center justify-center w-12 h-12 bg-blue-50 rounded-full">
              <component v-if="icon" :is="icon" class="text-blue-700 w-7 h-7"/>
              <UsersRound v-else class="text-blue-700 w-7 h-7"/>
            </div>
            <h1 class="text-2xl font-bold">{{ title }}</h1>
          </div>
        </template>
        <div v-else @click="goBack" class="flex cursor-pointer items-center gap-2">
          <ArrowLeft class="text-gray-500 w-6 h-6"/>
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
      <div class="flex-1">
        <section class="space-y-6">
          <slot />
        </section>
      </div>
    </div>
  </div>
</template>
