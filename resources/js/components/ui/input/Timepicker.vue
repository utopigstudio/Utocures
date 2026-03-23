<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { Label } from '@/components/ui/label'
import { useVModel } from '@vueuse/core'
import InputError from '@/components/ui/input/InputError.vue'
import { ClockIcon } from 'lucide-vue-next'

interface Props {
  name: string
  label?: string
  required?: boolean
  defaultValue?: string
  modelValue?: string
  error?: string
  step?: number
  containerClass?: string
}

const props = defineProps<Props>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

const showPicker = ref(false)
const selected = ref<string>(modelValue.value || '')
const hours = Array.from({ length: 24 }, (_, i) => i)
const step = props.step || 10
const minutes = Array.from({ length: 60 / step }, (_, i) => i * step)

function selectTime(hour: number, minute: number) {
  const formatted = String(hour).padStart(2, '0') + ':' + String(minute).padStart(2, '0')
  selected.value = formatted
  modelValue.value = formatted
  showPicker.value = false
}

onMounted(() => {
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement
    if (!target.closest(`#picker-${props.name}`)) showPicker.value = false
  })
})

const selectedHour = computed(() =>
  selected.value ? Number(selected.value.split(':')[0]) : 0
)

const selectedMinute = computed(() =>
  selected.value ? Number(selected.value.split(':')[1]) : 0
)

watch(
  () => modelValue.value,
  (v) => {
    if (v !== selected.value) selected.value = v || ''
  }
)

const hourCol = ref<HTMLElement | null>(null)
const optionHeight = 32

function scrollToHour() {
  if (!hourCol.value) return
  const targetIndex = selected.value ? selectedHour.value : 8
  hourCol.value.scrollTop = targetIndex * optionHeight
}

watch(showPicker, (isOpen) => {
  if (isOpen) {
    requestAnimationFrame(() => scrollToHour())
  }
})

function onManualInput(e: Event) {
  const val = (e.target as HTMLInputElement).value
  selected.value = val
}

function onManualBlur() {
  const clean = selected.value.trim()

  const regexColon = /^(\d{1,2}):(\d{2})$/
  const matchColon = clean.match(regexColon)

  if (matchColon) {
    return normalizeFromParts(matchColon[1], matchColon[2])
  }

  const regexCompact = /^(\d{1,2})(\d{2})$/
  const matchCompact = clean.match(regexCompact)

  if (matchCompact) {
    return normalizeFromParts(matchCompact[1], matchCompact[2])
  }

  const regexHourOnly = /^(\d{1,2})$/
  const matchHourOnly = clean.match(regexHourOnly)

  if (matchHourOnly) {
    return normalizeFromParts(matchHourOnly[1], "00")
  }

  resetOrKeep()
}

function normalizeFromParts(h: string, m: string) {
  const hour = Number(h)
  const minute = Number(m)

  if (hour < 0 || hour > 23 || minute < 0 || minute > 59) {
    resetOrKeep()
    return
  }

  const formatted =
    String(hour).padStart(2, '0') + ':' + String(minute).padStart(2, '0')

  selected.value = formatted
  modelValue.value = formatted
}

function resetOrKeep() {
  selected.value = modelValue.value || ''
}

</script>

<template>
  <div class="relative" :id="`picker-${props.name}`" :class="props.containerClass ?? 'w-48'">
    <Label v-if="props.label" :for="props.name">{{ props.label }}</Label>

    <div class="relative">
      <input
        type="text"
        :id="`${props.name}_display`"
        :value="selected"
        @input="onManualInput"
        @blur="onManualBlur"
        @focus="showPicker = false"
        class="w-full cursor-text border border-blue-gray-200 rounded-sm px-3 py-2 
              focus:outline-none focus:ring-2 focus:ring-black h-12 text-base leading-6 
              font-normal text-center"
      />
      <input type="hidden" :name="props.name" v-model="modelValue" />

      <span
        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 cursor-pointer"
        @click="showPicker = !showPicker"
      >
        <ClockIcon class="w-5 h-5" />
      </span>
    </div>

    <transition name="fade">
      <div
        v-if="showPicker"
        class="absolute z-10 mt-1 bg-white border rounded-lg shadow-lg p-2 w-full flex justify-center"
      >
        <div
          ref="hourCol"
          class="flex flex-col max-h-48 overflow-y-auto"
        >
          <div
            v-for="hour in hours"
            :key="'h-' + hour"
            @click="selectTime(hour, selectedMinute)"
            class="px-6 py-2 text-sm cursor-pointer text-center rounded"
            :class="[
              hour === selectedHour
                ? 'bg-black text-white'
                : 'hover:bg-gray-100'
            ]"
          >
            {{ hour.toString().padStart(2, '0') }}
          </div>
        </div>

        <div class="flex flex-col max-h-48 overflow-y-auto">
          <div
            v-for="minute in minutes"
            :key="'m-' + minute"
            @click="selectTime(selectedHour, minute)"
            class="px-6 py-2 text-sm cursor-pointer text-center rounded"
            :class="[
              minute === selectedMinute
                ? 'bg-black text-white'
                : 'hover:bg-gray-100'
            ]"
          >
            {{ minute.toString().padStart(2, '0') }}
          </div>
        </div>
      </div>
    </transition>

    <InputError class="mt-2" :message="props.error" />
  </div>
</template>

<style scoped>
::-webkit-scrollbar {
    width: 3px;
}
</style>