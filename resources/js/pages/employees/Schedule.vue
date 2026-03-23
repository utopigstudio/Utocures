<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { AppLayoutEmployee } from '@/layouts';
import { ChevronLeft, Calendar } from 'lucide-vue-next';
import { WeeklyDayCalendar } from '@/components/ui/calendar';
import { darken } from '@/lib/utils';
import { useI18n } from 'vue-i18n';
import type { Work } from '@/types';

interface Props {
  assigned_hours: Work[]
}

defineProps<Props>()
const { t } = useI18n()

const selectedDay = ref(new Date())

function getWeekStart(date: Date) {
  const d = new Date(date)
  const day = d.getDay() === 0 ? 7 : d.getDay()
  d.setDate(d.getDate() - (day - 1))
  return d
}

function onChangeWeek(newDate: Date) {
  const monday = getWeekStart(newDate)
  selectedDay.value = monday
  loadEventsForDay(monday)
}

async function loadEventsForDay(day: Date) {
  const date = day.toISOString().substring(0, 10)

  router.get(
    route('employee.schedule'),
    { filter_date: date },
    { preserveState: true, replace: true }
  )
}
</script>


<template>
  <AppLayoutEmployee>
    <div class="container mx-auto flex items-center gap-2 px-4 pt-0 pb-4 bg-gray-50">
      <Link :href="route('home')" class="flex items-center justify-center text-gray-600 hover:text-gray-900 transition-colors">
        <ChevronLeft class="size-6" />
        <Calendar class="size-6 text-blue-600" />
        <h1 class="text-xl leading-7 font-bold text-gray-800">{{ t('employees.schedule') }}</h1>
      </Link>
    </div>

    <div class="container mx-auto p-4">
      <WeeklyDayCalendar
        v-model="selectedDay"
        @select-day="loadEventsForDay"
        @change-week="onChangeWeek"
      />


      <ul role="list" class="mt-3 flex flex-col gap-5">
        <li v-for="work in assigned_hours" :key="work.id" class="flex rounded-md shadow-xs">
          <div class="flex w-full items-center">
            <div class="flex h-full w-16 shrink-0 items-center justify-center rounded-l-md font-medium text-white" :style="{ backgroundColor: work.service.color }">
              <span
                v-html="work.service.icon"
                class="[&_svg]:w-8 [&_svg]:h-8 [&_path]:![stroke:var(--icon-color)]"
              :style="{ '--icon-color': darken(work.service.color, 25) }"
              ></span>
            </div>
            <div class="flex flex-1 items-center justify-between truncate rounded-r-md border-t border-r border-b border-gray-200 bg-white">
              <div class="flex-1 truncate px-4 py-2 text-sm">
                <div class="font-medium text-gray-900 hover:text-gray-600">{{ work.client.name }}</div>
                <p class="text-gray-500">{{ work.time_start }} - {{ work.time_end }}</p>
                <p class="text-gray-500">{{ work.service.name }}</p>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </AppLayoutEmployee>
</template>
