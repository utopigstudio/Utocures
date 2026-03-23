<script setup lang="ts">
import { ref } from 'vue';
import { NavUser, NewYearFireworks } from '@/components/blocks';
import { Link } from '@inertiajs/vue3';
import { Home, PanelLeft } from 'lucide-vue-next';
import { useSidebar } from '@/components/ui/sidebar/utils';
import { SIDEBAR_WIDTH, SIDEBAR_WIDTH_ICON } from '@/components/ui/sidebar/utils';
import { computed } from 'vue';
import { useMediaQuery } from '@vueuse/core';
import { usePage } from '@inertiajs/vue3';
import type { Employee } from '@/types';

const page = usePage();
const { toggleSidebar, open } = useSidebar();

const employee = page.props.auth.employee as Employee;
const isDesktop = useMediaQuery('(min-width: 768px)');
const leftPosition = computed(() => {
  if (!isDesktop.value || employee) return '0';
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
    <div v-if="!employee" class="flex items-center gap-3">
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
      <p v-if="!employee" ref="dateEl" class="hidden md:block flex-shrink-0 leading-5 text-sm text-gray-800">{{ currentDate }}</p>
      <NewYearFireworks
        v-if="isNewYear && dateEl"
        :target="dateEl"
        class="hidden md:block absolute inset-0 z-10 pointer-events-none top-5"
      />
      <!-- <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex flex-shrink-0 cursor-pointer">
        <circle cx="16" cy="16" r="15.75" fill="#EFF4FB" stroke="#E2E8F0" stroke-width="0.5"/>
        <g clip-path="url(#clip0_678_3164)">
        <path d="M22.7765 21.5854L22.2471 20.7648C22.1412 20.606 22.0883 20.4471 22.0883 20.2618V14.756C22.0883 13.1942 21.4265 11.7383 20.2088 10.653C19.2294 9.77949 17.9588 9.22361 16.6088 9.11773V8.58832C16.6088 8.27067 16.3441 7.97949 16 7.97949C15.6824 7.97949 15.3912 8.2442 15.3912 8.58832V9.09126C15.3383 9.09126 15.2853 9.09126 15.2324 9.11773C12.1618 9.46185 9.85884 11.9236 9.85884 14.8618V20.2618C9.83237 20.5265 9.77943 20.6589 9.72649 20.7383L9.22355 21.5854C9.06473 21.8501 9.06473 22.1677 9.22355 22.4324C9.38237 22.6707 9.64708 22.8295 9.93825 22.8295H15.4177V23.4118C15.4177 23.7295 15.6824 24.0207 16.0265 24.0207C16.3441 24.0207 16.6353 23.756 16.6353 23.4118V22.8295H22.0883C22.3794 22.8295 22.6441 22.6707 22.803 22.4324C22.9618 22.1677 22.9618 21.8501 22.7765 21.5854ZM10.5735 21.6383L10.7588 21.3207C10.9177 21.056 10.9971 20.7383 11.05 20.3677V14.8618C11.05 12.5324 12.903 10.5736 15.3647 10.3089C16.8735 10.1501 18.3559 10.6001 19.4412 11.553C20.3941 12.4001 20.9236 13.5383 20.9236 14.756V20.2618C20.9236 20.6589 21.0294 21.0295 21.2677 21.4001L21.4265 21.6383H10.5735Z" fill="#5D6F88"/>
        </g>
        <circle cx="26.3529" cy="4.70588" r="3.70588" fill="#E11D48" stroke="white" stroke-width="2"/>
        <defs>
        <clipPath id="clip0_678_3164">
        <rect width="16.9412" height="16.9412" fill="white" transform="translate(7.52942 7.5293)"/>
        </clipPath>
        </defs>
      </svg> -->
      <NavUser />
    </div>
  </header>
</template>
