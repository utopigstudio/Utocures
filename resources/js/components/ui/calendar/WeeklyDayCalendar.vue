<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: { type: Date, required: true },
})

const emit = defineEmits(['update:modelValue', 'change-week', 'select-day'])

const selected = ref(new Date(props.modelValue))

const weekStart = computed(() => {
  const d = new Date(selected.value)
  const day = d.getDay() === 0 ? 7 : d.getDay()
  d.setDate(d.getDate() - (day - 1))
  return d
})

const weekDays = computed(() => {
  const start = new Date(weekStart.value)
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(start)
    d.setDate(start.getDate() + i)
    return d
  })
})

function prevWeek() {
  const newDate = new Date(selected.value)
  newDate.setDate(newDate.getDate() - 7)
  selected.value = newDate
  emit('change-week', newDate)
}

function nextWeek() {
  const newDate = new Date(selected.value)
  newDate.setDate(newDate.getDate() + 7)
  selected.value = newDate
  emit('change-week', newDate)
}

function selectDay(day) {
  selected.value = day
  emit('update:modelValue', day)
  emit('select-day', day)
}

function formatDay(d) {
  return d.getDate()
}

function formatWeekday(d) {
  return ['Lu','Ma','Mi','Ju','Vi','Sa','Do'][d.getDay() === 0 ? 6 : d.getDay() - 1]
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex justify-between items-center">
      <button @click="prevWeek">‹</button>

      <div class="font-semibold">
        {{ selected.toLocaleDateString('es-ES', {
          month:'long',
          year:'numeric'
        }) }}
        –
        Semana del {{ weekDays[0].getDate() }} al {{ weekDays[6].getDate() }}
      </div>

      <button @click="nextWeek">›</button>
    </div>

    <div class="flex justify-between space-x-1">
      <div
        v-for="day in weekDays"
        :key="day.toISOString()"
        @click="selectDay(day)"
        class="rounded-md py-2 cursor-pointer text-center border flex flex-col items-center flex-1"
      >
        <div>{{ formatWeekday(day) }}</div>
        <div class="font-bold rounded-full w-6 h-6" :class="{
          'bg-blue-600 text-white border-blue-600': day.toDateString() === selected.toDateString(),
          'bg-gray-100 text-gray-700 border-gray-300': day.toDateString() !== selected.toDateString(),
        }">{{ formatDay(day) }}</div>
      </div>
    </div>
  </div>
</template>