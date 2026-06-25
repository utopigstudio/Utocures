<script setup lang="ts">
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { AppLayoutEmployee } from '@/layouts'
import { ChevronLeft, Calendar } from 'lucide-vue-next'
import { WeeklyDayCalendar } from '@/components/ui/calendar'
import { darken } from '@/lib/utils'
import { useI18n } from 'vue-i18n'
import type { Work } from '@/types'

interface Props {
  assigned_hours: Work[]
  selected_date: string
}

const props = defineProps<Props>()
const { t } = useI18n()

function parseLocalDate(value: string) {
  const [year, month, day] = value.split('-').map(Number)

  return new Date(year, month - 1, day)
}

function getWeekStart(date: Date) {
  const weekDate = new Date(date)
  const day = weekDate.getDay() === 0 ? 7 : weekDate.getDay()
  weekDate.setDate(weekDate.getDate() - (day - 1))

  return weekDate
}

const selectedDay = ref(parseLocalDate(props.selected_date))

watch(
  () => props.selected_date,
  (value) => {
    selectedDay.value = parseLocalDate(value)
  }
)

function onChangeWeek(newDate: Date) {
  const monday = getWeekStart(newDate)
  selectedDay.value = monday
  loadEventsForDay(monday)
}

function loadEventsForDay(day: Date) {
  const year = day.getFullYear()
  const month = `${day.getMonth() + 1}`.padStart(2, '0')
  const date = `${day.getDate()}`.padStart(2, '0')

  router.get(
    route('employee.schedule'),
    { filter_date: `${year}-${month}-${date}` },
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
      <div class="mb-4 grid w-full grid-cols-2 rounded-xl border border-gray-200 bg-white p-1 shadow-sm sm:inline-flex sm:w-auto">
        <Link
          :href="route('employee.schedule')"
          class="rounded-lg px-3 py-2 text-center text-sm font-medium transition sm:px-4"
          :class="'bg-blue-600 text-white shadow-sm'"
        >
          {{ t('employees.schedule_week') }}
        </Link>
        <Link
          :href="route('employee.schedule-month', { filter_date: props.selected_date })"
          class="rounded-lg px-3 py-2 text-center text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 sm:px-4"
        >
          {{ t('employees.schedule_month') }}
        </Link>
      </div>

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
