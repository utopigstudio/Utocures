<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Label } from '@/components/ui/label'
import { Calendar } from 'lucide-vue-next'
import { format } from 'date-fns'
import InputError from '@/components/ui/input/InputError.vue'
import { datepickerVariants, type DatepickerVariants } from '.'

interface Props {
  variant?: DatepickerVariants['variant']
  name?: string
  label?: string
  placeholder?: string
  defaultValue?: string | null
  modelValue?: string | null
  required?: boolean
  minDate?: string
  maxDate?: string
  error?: string
  class?: string
}

const props = defineProps<Props>()

const showCalendar = ref(false)

function isValidDate(d: any): d is Date {
  return d instanceof Date && !isNaN(d.getTime())
}

function parseDDMMYYYY(str?: string | null): Date | null {
  if (!str) return null
  const [day, month, year] = str.split('/').map(Number)
  const d = new Date(year, month - 1, day)
  return isValidDate(d) ? d : null
}

function toDate(value?: string | Date | null): Date | null {
  if (typeof value === 'string') return parseDDMMYYYY(value)
  return null
}

function formatDisplay(d: Date | null): string {
  return isValidDate(d) ? format(d, 'dd/MM/yyyy') : ''
}

function formatISODate(d: Date | null): string {
  return isValidDate(d) ? format(d, 'yyyy-MM-dd') : ''
}

const selected = ref<Date | null>(toDate(props.defaultValue))
const currentMonth = ref<Date>(selected.value ? new Date(selected.value) : new Date())

const emit = defineEmits(['update:modelValue'])

function selectDate(date: Date) {
  if (!isValidDate(date)) return
  selected.value = date
  emit('update:modelValue', formatISODate(date))
  showCalendar.value = false
}

function nextMonth() {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1)
}

function prevMonth() {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1)
}

const years = computed(() => {
  const displayed = currentMonth.value.getFullYear()
  const now = new Date().getFullYear()

  const minYear = props.minDate
    ? new Date(props.minDate).getFullYear()
    : now - 100

  const maxYear = props.maxDate
    ? new Date(props.maxDate).getFullYear()
    : Math.max(now, displayed)

  const length = maxYear - minYear + 1;

  return Array.from({ length: length }, (_, i) => maxYear - i)
})

const currentYear = computed({
  get: () => currentMonth.value.getFullYear(),
  set: (year: number) => {
    currentMonth.value = new Date(year, currentMonth.value.getMonth(), 1)
  }
})

const daysInMonth = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  const end = new Date(year, month + 1, 0)

  return Array.from({ length: end.getDate() }, (_, i) => new Date(year, month, i + 1))
})

const monthLabel = computed(() =>
  currentMonth.value.toLocaleString('default', { month: 'long' })
)

function isToday(d: Date) {
  return d.toDateString() === new Date().toDateString()
}

function isSelected(d: Date) {
  return selected.value?.toDateString() === d.toDateString()
}

function stripTime(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate())
}

function isDisabled(d: Date) {
  const day = stripTime(d)

  if (props.minDate) {
    const min = stripTime(new Date(props.minDate))
    if (day < min) return true
  }

  if (props.maxDate) {
    const max = stripTime(new Date(props.maxDate))
    if (day > max) return true
  }

  return false
}

function firstDayOffset() {
  const day = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth(), 1).getDay()
  return day === 0 ? 6 : day - 1
}

function deleteSelected() {
  selected.value = null
  emit('update:modelValue', null)
}

onMounted(() => {
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement
    if (!target.closest(`#picker-${props.name}`)) showCalendar.value = false
  })
})
</script>

<template>
  <div class="relative" :class="props.class || 'w-60'" :id="`picker-${props.name}`">
    <Label v-if="props.label">{{ props.label }}</Label>

    <div class="relative">
      <input
        type="text"
        readonly
        :value="formatDisplay(selected)"
        :placeholder="props.placeholder"
        @click="showCalendar = !showCalendar"
        :class="datepickerVariants({ variant: props.variant })"
      />

      <input type="hidden" :name="props.name" :value="formatISODate(selected)" />

      <span
        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 cursor-pointer"
        @click="showCalendar = !showCalendar"
      >
        <Calendar class="w-5 h-5" />
      </span>
    </div>

    <transition name="fade">
      <div
        v-if="showCalendar"
        class="absolute z-1 mt-1 w-64 bg-white border rounded-lg shadow-lg p-3"
      >
        <div class="flex justify-center mb-2">
          <button
            v-if="selected"
            @click.prevent="deleteSelected"
            class="text-sm text-red-500 hover:underline"
          >
            {{ $t('layout.clear_date') }}
          </button>
        </div>
        <div class="flex items-center justify-between gap-2 mb-2">
          <button @click.prevent="prevMonth" class="px-2">‹</button>

          <div class="flex gap-2 items-center">
            <span class="text-sm font-semibold capitalize">{{ monthLabel }}</span>

            <select
              v-model.number="currentYear"
              class="border rounded px-2 py-1 pe-10 text-sm bg-size-[10px]"
            >
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>

          <button @click.prevent="nextMonth" class="px-2">›</button>
        </div>

        <div class="grid grid-cols-7 text-xs text-center mb-1 text-gray-500">
          <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
        </div>

        <div class="grid grid-cols-7 gap-1 text-sm">
          <div v-for="n in firstDayOffset()" :key="'sp' + n"></div>

          <button
            v-for="d in daysInMonth"
            :key="d.toISOString()"
            :disabled="isDisabled(d)"
            @click.prevent="!isDisabled(d) && selectDate(d)"
            class="w-8 h-8 rounded-full"
            :class="[
              isSelected(d) && 'bg-black text-white',
              !isSelected(d) && isToday(d) && 'border border-black',
              !isSelected(d) && !isToday(d) && 'hover:bg-gray-100',
              isDisabled(d) && 'text-gray-300 cursor-not-allowed'
            ]"
          >
            {{ d.getDate() }}
          </button>
        </div>
      </div>
    </transition>

    <InputError class="mt-2" :message="props.error" />
  </div>
</template>
