<script setup lang="ts">
import { RadioGroupRoot, RadioGroupItem, RadioGroupIndicator, useForwardPropsEmits } from 'reka-ui'
import { ref, watch, computed } from 'vue'
import { Label } from '@/components/ui/label'
import InputError from '@/components/ui/input/InputError.vue'
import type { HTMLAttributes } from 'vue'

interface Props {
  class?: HTMLAttributes['class']
  name?: string
  id?: string
  label?: string
  error?: string
  options: { label: string; value: string | number }[]
  modelValue?: string | number | null
  layout?: 'horizontal' | 'vertical'
}

const props = defineProps<Props>()
const emits = defineEmits<{
  (e: 'update:modelValue', value: string | number): void
}>()

const selected = ref(props.modelValue ?? null)

watch(
  () => props.modelValue,
  val => (selected.value = val ?? null),
  { immediate: true }
)

function onSelect(value: string | number) {
  selected.value = value
  emits('update:modelValue', value)
}

const forwarded = useForwardPropsEmits(props, emits)

const layoutClass = computed(() =>
  props.layout === 'horizontal' ? 'flex flex-row gap-4' : 'flex flex-col gap-4'
)
</script>

<template>
  <div>
    <Label v-if="props.label" class="mb-2 block font-medium">{{ props.label }}</Label>

    <RadioGroupRoot
      :id="props.id ?? props.name"
      :name="props.name"
      :modelValue="selected"
      @update:modelValue="onSelect"
      :class="layoutClass"
    >
      <div
        v-for="opt in props.options"
        :key="opt.value"
        class="flex items-center gap-2 cursor-pointer"
      >
        <RadioGroupItem
          :value="opt.value"
          :id="`${props.name}-${opt.value}`"
          class="peer size-[20px] shrink-0 rounded-full border border-input shadow-xs transition-shadow outline-none focus-visible:ring-[3px] focus-visible:border-ring focus-visible:ring-ring/50 data-[state=checked]:bg-blue-600 data-[state=checked]:border-blue-600 disabled:cursor-not-allowed disabled:opacity-50 flex items-center justify-center cursor-pointer"
        >
          <RadioGroupIndicator class="flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-4 h-4">
              <path
                d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"
                fill="white"
              />
            </svg>
          </RadioGroupIndicator>
        </RadioGroupItem>
        <label
          :for="`${props.name}-${opt.value}`"
          class="cursor-pointer select-none"
        >{{ opt.label }}</label>
      </div>
    </RadioGroupRoot>

    <InputError :message="props.error" />
  </div>
</template>