<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { Label } from '@/components/ui/label'
import InputError from '@/components/ui/input/InputError.vue'

interface Props {
  name?: string
  label?: string
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
  containerClass?: HTMLAttributes['class']
  placeholder?: string
  autocomplete?: string
  autofocus?: boolean
  required?: boolean
  error?: string
  tabindex?: number
  step?: string | number
}

const props = defineProps<Props>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

function onInput(e: Event) {
  let val = (e.target as HTMLInputElement).value

  if (val.includes(',')) {
    val = val.replace(',', '.')
  }

  if (val && !/^-?\d*(\.\d*)?$/.test(val)) {
    return
  }

  modelValue.value = val
}
</script>

<template>
  <div :class="props.containerClass">
    <Label v-if="props.label" :for="props.name">{{ props.label }}</Label>

    <input
      :id="props.name"
      :name="props.name"
      :value="modelValue"
      @input="onInput"
      data-slot="input"
      inputmode="decimal"
      type="number"
      :step="props.step ?? 'any'"
      :class="cn(
        'file:text-blue-gray-600 placeholder:text-blue-gray-600 selection:bg-primary selection:text-primary-foreground flex h-12 w-full min-w-0 shrink-0 items-center gap-2 rounded border border-blue-gray-200 bg-transparent px-4 py-3 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm mb-4',
        'focus-visible:border-blue-900 focus-visible:ring-blue-900/50 focus-visible:ring-blue-900-[3px]',
        'aria-invalid:ring-destructive/20 aria-invalid:border-destructive',
        props.class,
      )"
      :autocomplete="props.autocomplete"
      :placeholder="props.placeholder"
      :autofocus="props.autofocus || undefined"
      :required="props.required || undefined"
      :tabindex="props.tabindex"
    />

    <InputError class="mt-2" :message="props.error" />
  </div>
</template>
