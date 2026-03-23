<script setup lang="ts">
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'reka-ui'
import { cn } from '@/lib/utils'
import { ref, watch } from 'vue'
import { Label } from '@/components/ui/label'
import { checkboxVariants } from '.'
import InputError from '@/components/ui/input/InputError.vue'
import type { HTMLAttributes } from 'vue'
import type { CheckboxRootProps } from 'reka-ui'
import type { CheckboxVariants } from '.'

interface Props extends CheckboxRootProps {
  variant?: CheckboxVariants['variant']
  class?: HTMLAttributes['class']
  name?: string
  id?: string
  label?: string
  error?: string
  defaultValue?: boolean
  modelValue?: boolean
  value?: string | number
  tabindex?: number
  title?: string
}

const props = defineProps<Props>()
const emits = defineEmits<{
  (e: 'update:modelValue', value: boolean | 'indeterminate'): void
}>()
const checked = ref(!!props.defaultValue)

watch(
  () => props.modelValue,
  val => {
    if (typeof val === 'boolean') checked.value = val
  },
  { immediate: true }
)

function toggle(val: boolean | 'indeterminate') {
  if (val === 'indeterminate') return
  checked.value = val
  emits('update:modelValue', val)
}

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <div :class="cn(props.title ? 'flex flex-col gap-2' : '')">
    <p v-if="props.title" class="text-blue-gray-500 text-sm leading-5 font-normal mb-2">{{ props.title }}</p>
    <Label v-if="props.label" :class="cn(checkboxVariants.label({ variant: props.variant }), 'cursor-pointer flex items-center gap-2', props.class)">
      <input
        v-if="props.name"
        type="hidden"
        :name="props.name"
        value="0"
      />

      <CheckboxRoot
        v-bind="forwarded"
        :id="props.id ?? props.name"
        :name="props.name"
        :value="1"
        :checked="checked"
        @update:modelValue="toggle"
        :class="checkboxVariants.input({ variant: props.variant })"
        :tabindex="props.tabindex"
      >
        <CheckboxIndicator force-mount class="flex items-center justify-center text-white transition-none">
          <slot>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" style="fill: currentColor">
              <path
                d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"
              />
            </svg>
          </slot>
        </CheckboxIndicator>
      </CheckboxRoot>
      {{ props.label }}
    </Label>
  </div>

  <InputError :message="props.error" />
</template>
