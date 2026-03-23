<script setup lang="ts">
import { type HTMLAttributes, onMounted, ref, watch } from 'vue'
import { useVModel } from '@vueuse/core'
import { cn } from '@/lib/utils'
import { Label } from '@/components/ui/label'
import type { ItemTemplateOption } from '@/types'

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
  type: string
  label?: string
  modelValue?: string
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()
const options = ref<ItemTemplateOption[]>([])
const contentMap = new Map<string, string>()

const fetchTemplates = async () => {
  try {
    let res;
    if (props.type === 'budgets') {
      res = await fetch('/api/budget-templates')
    } else {
      res = await fetch('/api/contract-templates')
    }

    const data = await res.json()

    options.value = data.data.map((item: { id: string; name: string; content: string }) => {
      const opt = { value: item.id, label: item.name }
      contentMap.set(item.id, item.content)
      return opt
    })
  } catch (err) {
    console.error('Error fetching templates:', err)
  }
}

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string): void
  (e: 'template-selected', payload: string): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true
})

onMounted(() => {
  fetchTemplates()
})

watch(modelValue, (newVal) => {
  if (newVal) {
    const content = contentMap.get(newVal)
    if (content) emits('template-selected', content)
  }
})
</script>

<template>
  <Label v-if="props.label">{{ props.label }}</Label>

  <SelectRoot v-model="modelValue">

    <SelectTrigger :class="cn(
      'flex h-12 w-full items-center justify-between rounded border border-blue-gray-200 bg-white px-4 py-3 text-base shadow-xs transition focus-visible:border-blue-900 focus-visible:ring-2 focus-visible:ring-blue-900/50 outline-none disabled:opacity-50 text-left cursor-pointer',
      props.class,
    )">
      <SelectValue as="div" placeholder="Selecciona..." class="text-blue-gray-600" />
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="8" viewBox="0 0 16 8" fill="none">
        <path
          d="M8.0002 7.8249C7.83145 7.8249 7.69082 7.76865 7.5502 7.65615L1.08145 1.2999C0.82832 1.04678 0.82832 0.653028 1.08145 0.399903C1.33457 0.146778 1.72832 0.146778 1.98145 0.399903L8.0002 6.27803L14.0189 0.343652C14.2721 0.0905273 14.6658 0.0905273 14.9189 0.343652C15.1721 0.596777 15.1721 0.990528 14.9189 1.24365L8.45019 7.5999C8.30957 7.74053 8.16895 7.8249 8.0002 7.8249Z"
          fill="black" />
      </svg>
    </SelectTrigger>

    <SelectPortal :disabled="true">
      <SelectContent
        :body-lock="false"
        class="z-1 mt-1 rounded-md border border-blue-gray-200 bg-white shadow-lg w-[var(--reka-select-trigger-width)]"
        position="popper" :align="'start'">
        <SelectViewport class="max-h-60 overflow-y-auto p-4">
          <SelectGroup>
            <SelectLabel class="sr-only">Opciones</SelectLabel>
            <SelectItem v-for="opt in options" :key="opt.value" :value="opt.value" class="cursor-pointer select-none px-4 py-2 mb-4 last:mb-0 text-blue-gray-600
              hover:bg-gray-100
              data-[state=checked]:bg-blue-gray-200
              data-[state=checked]:text-blue-gray-900
              flex items-center gap-2
              rounded-md
              outline-none focus:outline-none focus-visible:outline-none">
              <SelectItemIndicator>
                <svg class="h-4 w-4 text-blue-gray-900" fill="none" stroke="currentColor"
                  stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </SelectItemIndicator>
              <SelectItemText>
                {{ opt.label }}
              </SelectItemText>
            </SelectItem>
          </SelectGroup>
        </SelectViewport>
      </SelectContent>
    </SelectPortal>
  </SelectRoot>
</template>
