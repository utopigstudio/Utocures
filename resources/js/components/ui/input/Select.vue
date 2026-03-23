<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { Label } from '@/components/ui/label'
import InputError from '@/components/ui/input/InputError.vue';
import { selectVariants, type SelectVariants } from '.'

import {
  SelectPortal,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectItemIndicator,
  SelectLabel,
  SelectRoot,
  SelectTrigger,
  SelectValue,
  SelectViewport,
  SelectItemText,
} from 'reka-ui'

interface Props {
  variant?: SelectVariants['variant']
  name?: string
  label?: string
  defaultValue?: string | number
  modelValue?: string
  options: { label: string; icon?: string; value: string }[]
  class?: HTMLAttributes['class']
  containerClass?: HTMLAttributes['class']
  autocomplete?: string
  required?: boolean
  error?: string
  disabled?: boolean
  placeholder?: string
}

const props = defineProps<Props>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue != null ? String(props.defaultValue) : undefined,
})
</script>

<template>
  <div :class="props.containerClass">
    <Label v-if="props.label">{{ props.label }}</Label>

    <SelectRoot v-model="modelValue" :name="props.name">

      <SelectTrigger :class="cn(
          selectVariants({ variant: props.variant }),
          props.class,
        )"
        :disabled="props.disabled"
      >
        <SelectValue as="div" :placeholder="props.placeholder ?? $t('generic.select_placeholder')" class="text-blue-gray-600 flex items-center gap-2">
          <slot name="valueStart" :value="modelValue" />
          <span>
            {{ props.options.find(o => String(o.value) === modelValue)?.label || (props.placeholder ?? $t('generic.select_placeholder')) }}
          </span>
          <slot name="valueEnd" :value="modelValue" />
        </SelectValue>
        <svg class="ml-2" xmlns="http://www.w3.org/2000/svg" width="16" height="8" viewBox="0 0 16 8" fill="none">
          <path
            d="M8.0002 7.8249C7.83145 7.8249 7.69082 7.76865 7.5502 7.65615L1.08145 1.2999C0.82832 1.04678 0.82832 0.653028 1.08145 0.399903C1.33457 0.146778 1.72832 0.146778 1.98145 0.399903L8.0002 6.27803L14.0189 0.343652C14.2721 0.0905273 14.6658 0.0905273 14.9189 0.343652C15.1721 0.596777 15.1721 0.990528 14.9189 1.24365L8.45019 7.5999C8.30957 7.74053 8.16895 7.8249 8.0002 7.8249Z"
            fill="black" />
        </svg>
      </SelectTrigger>

      <SelectPortal :disabled="true">
        <SelectContent
          :body-lock="false"
          class="z-1 mt-1 rounded-md border border-blue-gray-200 bg-white shadow-lg min-w-[var(--reka-select-trigger-width)]"
          position="popper"
          :align="'start'"
        >
          <SelectViewport class="max-h-60 overflow-y-auto p-4">
            <SelectGroup>
              <SelectLabel class="sr-only">Opciones</SelectLabel>
              <SelectItem v-for="opt in props.options" :key="String(opt.value)" :value="String(opt.value)" class="cursor-pointer select-none px-4 py-2 mb-4 last:mb-0 text-blue-gray-600
                hover:bg-gray-100
                data-[state=checked]:bg-blue-gray-200
                data-[state=checked]:text-blue-gray-900
                flex items-center gap-2
                rounded-md
                outline-none focus:outline-none focus-visible:outline-none"
              >
                <!-- <SelectItemIndicator>
                  <svg class="h-4 w-4 text-blue-gray-900" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </SelectItemIndicator>
                <SelectItemText>
                  <span v-if="opt.icon" v-html="opt.icon"></span>
                  {{ opt.label }}
                  <slot name="valueOptionEnd" :value="opt.value" />
                </SelectItemText> -->
                <div class="flex items-center gap-2">
                  <SelectItemIndicator>
                    <svg class="h-4 w-4 text-blue-gray-900" fill="none" stroke="currentColor"
                      stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </SelectItemIndicator>

                  <span v-if="opt.icon" v-html="opt.icon"></span>
                  <SelectItemText>
                    {{ opt.label }}
                  </SelectItemText>
                </div>

                <slot name="valueOptionEnd" :value="opt.value" />
              </SelectItem>
            </SelectGroup>
          </SelectViewport>
        </SelectContent>
      </SelectPortal>
    </SelectRoot>
    <InputError class="mt-2" :message="props.error" />
  </div>
</template>
