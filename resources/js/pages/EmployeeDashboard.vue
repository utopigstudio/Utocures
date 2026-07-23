<script setup lang="ts">
import { ref, computed } from 'vue'
import { AppLayoutEmployee } from '@/layouts';
import { usePage, Link, router } from '@inertiajs/vue3';
import { ListWorks, TimerCounter, NewYearFireworks } from '@/components/blocks';
import { Calendar, CalendarClock, Megaphone } from 'lucide-vue-next'
import type { User, Work } from '@/types';

const page = usePage();
const user = page.props.auth.user as User;

interface Props {
  works?: Work[],
  active_work?: Work,
}

const props = defineProps<Props>()

const currentDate = new Date().toLocaleDateString('es-ES', {
  weekday: 'long',
  month: 'long',
  day: 'numeric'
})

const reloadActiveWork = () => {
  router.reload({ only: ['active_work'] });
}

const isNewYear = computed(() => {
  const now = new Date();
  return now.getMonth() === 11 && now.getDate() === 31;
});

const dateEl = ref(null);
</script>

<template>
  <AppLayoutEmployee>
    <div class="flex h-full flex-1 flex-col gap-8 rounded-xl p-4 overflow-x-auto">

      <div class="text-center text-gray-800">
        <h2 class="text-lg leading-7 font-bold">{{ $t('layout.welcome') }}, {{ user.name }}</h2>
        <p ref="dateEl" class="relative text-sm leading-5 font-normal mt-2">{{ currentDate }}</p>
        <NewYearFireworks
          v-if="isNewYear && dateEl"
          :target="dateEl"
          class="absolute inset-0 z-10 pointer-events-none top-25"
        />
      </div>

      <div class="gap-5 flex justify-center">
        <Link :href="route('employee.schedule')" class="flex gap-0 w-[9.218rem] h-16 px-[0.8125rem] py-2 flex-col justify-center items-center flex-shrink-0 bg-blue-800 hover:bg-blue-700 text-white rounded-lg">
          <Calendar class="size-5 mb-1" />
          <span class="text-sm leading-5 font-semibold">{{ $t('employees.schedule') }}</span>
        </Link>
        <Link :href="route('employee.hours-worked')" class="flex gap-0 w-[9.218rem] h-16 px-[0.8125rem] py-2 flex-col justify-center items-center flex-shrink-0 bg-blue-800 hover:bg-blue-700 text-white rounded-lg">
          <CalendarClock class="size-5 mb-1" />
          <span class="text-sm leading-5 font-semibold">{{ $t('employees.hours_worked') }}</span>
        </Link>
      </div>

      <div class="flex justify-center">
        <Link :href="route('employee.announcements')" class="flex w-full h-12 py-2 flex-row gap-5 justify-center items-center border-2 border-blue-800 hover:border-blue-700 text-blue-800 rounded-lg">
          <Megaphone class="size-5 mb-1" /><span class="text-sm leading-5 font-semibold">{{ $t('announcements.board_button') }}</span>
        </Link>
      </div>

      <div class="container mx-auto">
        <div class="flex items-center justify-between">
          <p class="text-base leading-6 font-semibold">{{ $t('employees.current_plan') }}</p>
        </div>
        <TimerCounter v-if="props.active_work" :work="props.active_work" @recording-finished="reloadActiveWork" size="small" />
        <ListWorks :works="props.works"/>
      </div>
    </div>
  </AppLayoutEmployee>
</template>
