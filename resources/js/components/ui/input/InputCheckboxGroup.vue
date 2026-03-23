<script setup lang="ts">
import { useVModel } from '@vueuse/core'
import { checkboxGroupVariants } from '.'
import InputError from '@/components/ui/input/InputError.vue'
import InputCheckbox from '@/components/ui/input/InputCheckbox.vue'
import type { Option } from '@/types'
import type { CheckboxGroupVariants, CheckboxVariants } from '.'

interface Props {
  name: string
  options: Option[]
  variant?: CheckboxGroupVariants['variant']
  variantItem?: CheckboxVariants['variant']
  label?: string
  error?: string
  defaultValue?: (string | number)[]
  modelValue?: (string | number)[]
  groupKey?: string | number
  class?: string
  labelClass?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: (string | number)[]): void
}>()

const selected = useVModel(props, 'modelValue', emit, {
  passive: true,
  defaultValue: props.defaultValue ?? [],
})

function toggle(value: string | number, checked: boolean) {
  if (checked && !selected.value?.includes(value)) {
    selected.value?.push(value)
  } else if (!checked) {
    selected.value = selected.value?.filter(v => v !== value)
  }
}

const isChecked = (value: string | number) => selected.value?.includes(value)
</script>

<template>
  <div :class="checkboxGroupVariants({ variant })">
    <div
      v-for="(option, index) in props.options"
      :key="`${props.groupKey ?? '0'}-${option.value}`"
      :class="props.class"
    >
      <InputCheckbox
        :id="`${props.name}${index}`"
        :label="option.label"
        :value="option.value"
        :variant="props.variantItem" 
        :model-value="isChecked(option.value)"
        :class="props.labelClass"
        @update:modelValue="val => { if (val !== 'indeterminate') toggle(option.value, val) }"
      />

      <input
        v-if="isChecked(option.value)"
        type="hidden"
        :name="props.name"
        :value="option.value"
      />
    </div>
  </div>

  <InputError :message="props.error" />
</template>
