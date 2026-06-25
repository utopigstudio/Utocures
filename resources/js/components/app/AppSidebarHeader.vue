<script setup lang="ts">
import { ref } from 'vue';
import { NavNotifications, NavUser, NewYearFireworks } from '@/components/blocks';
import { Link } from '@inertiajs/vue3';
import { Home, PanelLeft } from 'lucide-vue-next';
import { useSidebar } from '@/components/ui/sidebar/utils';
import { SIDEBAR_WIDTH, SIDEBAR_WIDTH_ICON } from '@/components/ui/sidebar/utils';
import { computed } from 'vue';
import { useMediaQuery } from '@vueuse/core';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const { toggleSidebar, open } = useSidebar();

const isEmployee = Boolean(page.props.auth.employee);
const isDesktop = useMediaQuery('(min-width: 768px)');
const leftPosition = computed(() => {
  if (!isDesktop.value || isEmployee) return '0';
  return open.value ? SIDEBAR_WIDTH : SIDEBAR_WIDTH_ICON;
});

const currentDate = new Date().toLocaleDateString('es-ES', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric'
})

const isNewYear = computed(() => {
  const now = new Date();
  return now.getMonth() === 11 && now.getDate() === 31;
});

const dateEl = ref(null);
</script>

<template>
  <header class="fixed top-0 right-0 z-9 flex h-13 sm:h-20 shrink-0 justify-between gap-2 border-b border-sidebar-border/70 px-4 sm:px-6 transition-[width,height,left] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-8 shadow-sm bg-white after:content-[''] after:fixed after:top-20 after:left-0 after:right-0 after:h-0 after:group-has-data-[collapsible=icon]/sidebar-wrapper:top-12 after:z-40" :style="{ left: leftPosition }">
    <div v-if="!isEmployee" class="flex items-center gap-3">
      <button 
        @click="toggleSidebar"
        class="flex items-center gap-2 transition-colors hover:opacity-80 cursor-pointer"
      >
        <div class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center">
          <PanelLeft class="h-4 w-4 text-gray-500" />
        </div>
        <span class="hidden lg:block text-sm text-gray-500">{{ $t('layout.minimize') }}</span>
      </button>
    </div>
    <div v-else class="flex items-center">
      <Link :href="route('home')" class="flex items-center gap-2 text-blue-gray-500 m-0 hover:text-blue-gray-700 transition-colors">
        <Home class="size-4" />
        <span class="text-sm leading-5">Inicio</span>
      </Link>
    </div>
    <div class="relative inline-flex items-center justify-center gap-2 md:gap-6">
      <p v-if="!isEmployee" ref="dateEl" class="hidden md:block flex-shrink-0 leading-5 text-sm text-gray-800">{{ currentDate }}</p>
      <NewYearFireworks
        v-if="isNewYear && dateEl"
        :target="dateEl"
        class="hidden md:block absolute inset-0 z-10 pointer-events-none top-5"
      />
      <NavNotifications v-if="!isEmployee" />
      <NavUser />
    </div>
  </header>
</template>
