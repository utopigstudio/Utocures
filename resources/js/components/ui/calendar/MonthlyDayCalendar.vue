<script setup lang="ts">
import { computed, ref, watch } from 'vue'

interface DaySummary {
  count: number
  labels?: string[]
}

const props = withDefaults(defineProps<{
  modelValue: Date
  eventsByDate?: Record<string, DaySummary>
}>(), {
  eventsByDate: () => ({})
})

const emit = defineEmits<{
  'update:modelValue': [value: Date]
  'change-month': [value: Date]
  'select-day': [value: Date]
}>()

const selected = ref(new Date(props.modelValue))

watch(
  () => props.modelValue,
  (value) => {
    selected.value = new Date(value)
  }
)

const monthStart = computed(() => new Date(selected.value.getFullYear(), selected.value.getMonth(), 1))
const monthEnd = computed(() => new Date(selected.value.getFullYear(), selected.value.getMonth() + 1, 0))

const leadingEmptyDays = computed(() => {
  const day = monthStart.value.getDay() === 0 ? 7 : monthStart.value.getDay()

  return day - 1
})

const trailingEmptyDays = computed(() => {
  const totalCells = leadingEmptyDays.value + monthEnd.value.getDate()
  const remainder = totalCells % 7

  return remainder === 0 ? 0 : 7 - remainder
})

const calendarDays = computed(() => {
  const days: Date[] = []
  const totalDays = monthEnd.value.getDate()

  for (let day = 1; day <= totalDays; day += 1) {
    days.push(new Date(selected.value.getFullYear(), selected.value.getMonth(), day))
  }

  return days
})

const monthLabel = computed(() => monthStart.value.toLocaleDateString('es-ES', {
  month: 'long',
  year: 'numeric',
}))

const weekDays = ['L', 'M', 'X', 'J', 'V', 'S', 'D']

function dateKey(day: Date) {
  const year = day.getFullYear()
  const month = `${day.getMonth() + 1}`.padStart(2, '0')
  const date = `${day.getDate()}`.padStart(2, '0')

  return `${year}-${month}-${date}`
}

function changeMonth(offset: number) {
  const currentDay = selected.value.getDate()
  const next = new Date(selected.value)

  next.setDate(1)
  next.setMonth(next.getMonth() + offset)

  const lastDayOfMonth = new Date(next.getFullYear(), next.getMonth() + 1, 0).getDate()
  next.setDate(Math.min(currentDay, lastDayOfMonth))

  selected.value = next
  emit('change-month', next)
}

function selectDay(day: Date) {
  selected.value = day
  emit('update:modelValue', day)
  emit('select-day', day)
}

function isToday(day: Date) {
  return day.toDateString() === new Date().toDateString()
}

function isSelected(day: Date) {
  return day.toDateString() === selected.value.toDateString()
}

function labelsFor(day: Date) {
  return props.eventsByDate[dateKey(day)]?.labels ?? []
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
      <button
        type="button"
        class="flex size-10 items-center justify-center rounded-full border border-gray-200 bg-white text-lg text-gray-700 transition hover:bg-gray-50"
        @click="changeMonth(-1)"
      >
        ‹
      </button>

      <div class="text-center">
        <p class="text-base font-semibold capitalize text-gray-900 sm:text-lg">{{ monthLabel }}</p>
      </div>

      <button
        type="button"
        class="flex size-10 items-center justify-center rounded-full border border-gray-200 bg-white text-lg text-gray-700 transition hover:bg-gray-50"
        @click="changeMonth(1)"
      >
        ›
      </button>
    </div>

    <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 sm:gap-2">
      <span v-for="day in weekDays" :key="day" class="py-2">{{ day }}</span>
    </div>

    <div class="grid grid-cols-7 gap-1 sm:gap-2">
      <div
        v-for="emptyIndex in leadingEmptyDays"
        :key="`leading-${emptyIndex}`"
        class="min-h-[3.5rem] rounded-2xl border border-transparent bg-transparent sm:min-h-[6.5rem]"
        aria-hidden="true"
      />

      <button
        v-for="day in calendarDays"
        :key="day.toISOString()"
        type="button"
        class="flex min-h-[3.75rem] flex-col rounded-2xl border p-1.5 text-left transition sm:min-h-[6.5rem] sm:p-2"
        :class="[
          isSelected(day)
            ? 'border-blue-600 bg-blue-50 shadow-sm'
            : 'border-gray-200 bg-white hover:border-blue-200 hover:bg-blue-50/40',
          'text-gray-900',
        ]"
        @click="selectDay(day)"
      >
        <div class="flex items-start justify-between gap-1 sm:gap-2">
          <span
            class="flex size-6 items-center justify-center rounded-full text-xs font-semibold sm:size-7 sm:text-sm"
            :class="{
              'bg-blue-600 text-white': isSelected(day),
              'bg-gray-900 text-white': isToday(day) && !isSelected(day),
            }"
          >
            {{ day.getDate() }}
          </span>
        </div>

        <div class="mt-auto space-y-1">
          <span
            v-if="eventsByDate[dateKey(day)]?.count"
            class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 sm:px-2 sm:text-[11px]"
          >
            {{ eventsByDate[dateKey(day)].count }}
          </span>
        </div>
      </button>

      <div
        v-for="emptyIndex in trailingEmptyDays"
        :key="`trailing-${emptyIndex}`"
        class="min-h-[3.5rem] rounded-2xl border border-transparent bg-transparent sm:min-h-[6.5rem]"
        aria-hidden="true"
      />
    </div>
  </div>
</template>
