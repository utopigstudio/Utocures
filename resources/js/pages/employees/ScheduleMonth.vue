<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { AppLayoutEmployee } from '@/layouts'
import { ChevronLeft, Calendar, CalendarDays, Clock3 } from 'lucide-vue-next'
import { MonthlyDayCalendar } from '@/components/ui/calendar'
import { darken } from '@/lib/utils'
import { useI18n } from 'vue-i18n'
import type { Work } from '@/types'

interface Props {
  assigned_hours: Work[]
  selected_date: string
}

interface DaySummary {
  count: number
  labels: string[]
}

const props = defineProps<Props>()
const { t } = useI18n()

function parseLocalDate(value: string) {
  const [year, month, day] = value.split('-').map(Number)

  return new Date(year, month - 1, day)
}

function parseWorkDate(value: string) {
  if (value.includes('-')) {
    const [year, month, day] = value.split('-').map(Number)

    return new Date(year, month - 1, day)
  }

  const [day, month, year] = value.split('/').map(Number)

  return new Date(year, month - 1, day)
}

function formatDateForRequest(day: Date) {
  const year = day.getFullYear()
  const month = `${day.getMonth() + 1}`.padStart(2, '0')
  const date = `${day.getDate()}`.padStart(2, '0')

  return `${year}-${month}-${date}`
}

const selectedDay = ref(parseLocalDate(props.selected_date))

watch(
  () => props.selected_date,
  (value) => {
    selectedDay.value = parseLocalDate(value)
  }
)

const groupedWorks = computed<Record<string, Work[]>>(() => {
  return props.assigned_hours.reduce<Record<string, Work[]>>((carry, work) => {
    const normalizedDate = formatDateForRequest(parseWorkDate(work.date))

    carry[normalizedDate] ??= []
    carry[normalizedDate].push(work)

    return carry
  }, {})
})

const eventsByDate = computed<Record<string, DaySummary>>(() => {
  return Object.entries(groupedWorks.value).reduce<Record<string, DaySummary>>((carry, [date, works]) => {
    carry[date] = {
      count: works.length,
      labels: [...new Set(works.map((work) => work.client.name))],
    }

    return carry
  }, {})
})

const selectedDayKey = computed(() => formatDateForRequest(selectedDay.value))
const selectedDayWorks = computed(() => groupedWorks.value[selectedDayKey.value] ?? [])
const monthServices = computed(() => props.assigned_hours.length)
const selectedDayLabel = computed(() => selectedDay.value.toLocaleDateString('es-ES', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
}))

function goToMonth(day: Date) {
  router.get(
    route('employee.schedule-month'),
    { filter_date: formatDateForRequest(day) },
    { preserveState: true, replace: true }
  )
}

function handleSelectDay(day: Date) {
  const changedMonth =
    day.getMonth() !== selectedDay.value.getMonth() ||
    day.getFullYear() !== selectedDay.value.getFullYear()

  if (changedMonth) {
    goToMonth(day)
    return
  }

  selectedDay.value = new Date(day)
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

    <div class="container mx-auto space-y-4 p-4 sm:space-y-6">
      <div class="grid w-full grid-cols-2 rounded-xl border border-gray-200 bg-white p-1 shadow-sm sm:inline-flex sm:w-auto">
        <Link
          :href="route('employee.schedule', { filter_date: props.selected_date })"
          class="rounded-lg px-3 py-2 text-center text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 sm:px-4"
        >
          {{ t('employees.schedule_week') }}
        </Link>
        <Link
          :href="route('employee.schedule-month')"
          class="rounded-lg bg-blue-600 px-3 py-2 text-center text-sm font-medium text-white shadow-sm transition sm:px-4"
        >
          {{ t('employees.schedule_month') }}
        </Link>
      </div>

      <div class="grid gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(20rem,1fr)] lg:items-start">
        <section class="rounded-3xl border border-gray-200 bg-white p-3 shadow-sm sm:p-6">
          <div class="mb-4 flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 sm:size-11">
              <CalendarDays class="size-5" />
            </div>
            <div class="min-w-0">
              <h2 class="text-base font-semibold text-gray-900 sm:text-lg">{{ t('employees.monthly_schedule') }}</h2>
              <p class="text-sm text-gray-500">{{ t('employees.month_services') }}: {{ monthServices }}</p>
            </div>
          </div>

          <MonthlyDayCalendar
            v-model="selectedDay"
            :events-by-date="eventsByDate"
            @change-month="goToMonth"
            @select-day="handleSelectDay"
          />
        </section>

        <aside class="rounded-3xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:sticky lg:top-4">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-sm font-medium uppercase tracking-wide text-blue-600">{{ t('employees.selected_day') }}</p>
              <h2 class="text-base font-semibold capitalize text-gray-900 sm:text-lg">{{ selectedDayLabel }}</h2>
            </div>
            <span class="shrink-0 rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700">
              {{ selectedDayWorks.length }}
            </span>
          </div>

          <ul v-if="selectedDayWorks.length" role="list" class="flex flex-col gap-4">
            <li v-for="work in selectedDayWorks" :key="work.id" class="flex rounded-md shadow-xs">
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

          <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
            {{ t('employees.no_services_scheduled') }}
          </div>
        </aside>
      </div>
    </div>
  </AppLayoutEmployee>
</template>
