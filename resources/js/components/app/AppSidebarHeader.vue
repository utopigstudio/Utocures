<script setup lang="ts">
import { computed, ref } from 'vue';
import { NavNotifications, NavUser, NewYearFireworks } from '@/components/blocks';
import { Link, usePage, usePoll } from '@inertiajs/vue3';
import { Home, PanelLeft } from 'lucide-vue-next';
import HelpButton from '@/components/help/HelpButton.vue';
import ChangelogButton from '@/components/changelog/ChangelogButton.vue';
import { SIDEBAR_WIDTH, SIDEBAR_WIDTH_ICON, useSidebar } from '@/components/ui/sidebar/utils';
import type { AppPageProps } from '@/types';
import { useMediaQuery } from '@vueuse/core';

interface Props {
  hasSidebar?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  hasSidebar: true,
});

const page = usePage<AppPageProps>();
const { toggleSidebar, open } = useSidebar();

const isAdmin = page.props.auth.role === 'admin';
const isClient = page.props.auth.role === 'client';
const messagesUrl = computed(() => (isAdmin ? route('messages.index') : route('client.messages.index')));
const unreadMessagesCount = computed(() => page.props.unreadMessagesCount ?? 0);

usePoll(
  15000,
  { only: ['unreadMessagesCount'] },
  { autoStart: (isAdmin || isClient) && page.component !== 'messages/Index' },
);

const isDesktop = useMediaQuery('(min-width: 768px)');
const leftPosition = computed(() => {
  if (!isDesktop.value || !props.hasSidebar) return '0';
  return open.value ? SIDEBAR_WIDTH : SIDEBAR_WIDTH_ICON;
});

const currentDate = new Date().toLocaleDateString('es-ES', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric',
});

const isNewYear = computed(() => {
  const now = new Date();
  return now.getMonth() === 11 && now.getDate() === 31;
});

const dateEl = ref(null);
</script>

<template>
  <header class="fixed top-0 right-0 z-9 flex h-13 sm:h-20 shrink-0 justify-between gap-2 border-b border-sidebar-border/70 px-4 sm:px-6 transition-[width,height,left] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-8 shadow-sm bg-white after:content-[''] after:fixed after:top-20 after:left-0 after:right-0 after:h-0 after:group-has-data-[collapsible=icon]/sidebar-wrapper:top-12 after:z-40" :style="{ left: leftPosition }">
    <div v-if="props.hasSidebar" class="flex items-center gap-3">
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
      <p v-if="props.hasSidebar" ref="dateEl" class="hidden md:block flex-shrink-0 leading-5 text-sm text-gray-800">{{ currentDate }}</p>
      <NewYearFireworks
        v-if="isNewYear && dateEl"
        :target="dateEl"
        class="hidden md:block absolute inset-0 z-10 pointer-events-none top-5"
      />
      <Link
        v-if="isAdmin || isClient"
        :href="messagesUrl"
        class="relative flex h-10 w-10 items-center justify-center cursor-pointer"
        :aria-label="$t('messages.open_messages')"
      >
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex flex-shrink-0 cursor-pointer">
            <circle cx="16" cy="16" r="15.75" fill="#EFF4FB" stroke="#E2E8F0" stroke-width="0.5"/>
            <g transform="translate(16 16) scale(0.8) translate(-12 -12)" stroke="#5D6F88" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" >
            <path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719" />
            <path d="M8 12h.01" />
            <path d="M12 12h.01" />
            <path d="M16 12h.01" />
            </g>
            <circle v-if="unreadMessagesCount > 0" cx="26.3529" cy="4.70588" r="3.70588" fill="#E11D48" stroke="white" stroke-width="2"/>
        </svg>
      </Link>
      <NavNotifications v-if="isAdmin" />
      <ChangelogButton />
      <HelpButton :compact="false" link-only />
      <NavUser />
    </div>
  </header>
</template>
