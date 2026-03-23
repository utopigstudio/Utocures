<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { ref, watch, computed, onMounted } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { Label } from '@/components/ui/label'
import InputError from '@/components/ui/input/InputError.vue'
import type { ClientOption } from '@/types'

import {
  ComboboxPortal,
  ComboboxAnchor,
  ComboboxContent,
  ComboboxEmpty,
  ComboboxGroup,
  ComboboxInput,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxRoot,
  ComboboxSeparator,
  ComboboxTrigger,
  ComboboxViewport,
} from 'reka-ui'

interface Props {
  name: string
  label?: string
  defaultValue?: string
  modelValue?: string
  class?: HTMLAttributes['class']
  autocomplete?: string
  required?: boolean
  error?: string
  freeClient?: boolean
}

const props = defineProps<Props>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue
})

const options = ref<ClientOption[]>([])
const query = ref('')
const labelMap = new Map<string, string>()
const loading = ref(false)

async function fetchAllClients() {
  loading.value = true
  try {
    const res = await fetch('/api/clients/options')
    const data = await res.json()

    options.value = (data.data || []).map((client: { id: string; name: string }) => {
      const opt = { value: client.id, label: client.name }
      labelMap.set(opt.value, opt.label)
      return opt
    })
  } catch (err) {
    console.error('Error fetching clients:', err)
  } finally {
    loading.value = false
  }
}

const filteredOptions = computed(() => {
  const q = query.value.toLowerCase().trim()
  if (!q) return options.value
  return options.value.filter(o => o.label.toLowerCase().includes(q))
})

const displayValue = (v: unknown) => {
  if (v == null) return ''
  return (
    labelMap.get(v as any) ??
    options.value.find(o => String(o.value) === String(v))?.label ??
    (typeof v === 'string' ? v : '')
  )
}

watch(query, (val) => {
  if (props.freeClient) {
    const exists = options.value.some(o => o.label === val)
    if (!exists) modelValue.value = val
  }
})

watch(modelValue, (newVal) => {
  const selected = options.value.find(opt => String(opt.value) === String(newVal))
  if (selected) query.value = selected.label
})

onMounted(async () => {
  await fetchAllClients()

  if (props.defaultValue || props.modelValue) {
    const val = props.modelValue ?? props.defaultValue
    const selected = options.value.find(opt => String(opt.value) === String(val))
    if (selected) query.value = selected.label
  }
})
</script>

<template>
  <Label v-if="props.label" :for="props.name">{{ props.label }}</Label>
  <ComboboxRoot v-model="modelValue" class="relative">
    <ComboboxAnchor :class="cn(
      'flex h-12 w-full items-center justify-between rounded border border-blue-gray-200 bg-white px-4 py-3 text-base shadow-xs transition focus-visible:border-blue-900 focus-visible:ring-2 focus-visible:ring-blue-900/50 outline-none disabled:opacity-50 text-left',
      props.class,
    )">
      <ComboboxInput
        v-model="query"
        :display-value="displayValue"
        :placeholder="props.label ? `Elige ${props.label?.toLowerCase()} o escribe el nombre` : 'Buscar cliente...'"
        :id="props.name"
        class="flex-1 outline-none bg-transparent"
      />
      <ComboboxTrigger class="flex-shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="8" viewBox="0 0 16 8" fill="none">
          <path
            d="M8.0002 7.8249C7.83145 7.8249 7.69082 7.76865 7.5502 7.65615L1.08145 1.2999C0.82832 1.04678 0.82832 0.653028 1.08145 0.399903C1.33457 0.146778 1.72832 0.146778 1.98145 0.399903L8.0002 6.27803L14.0189 0.343652C14.2721 0.0905273 14.6658 0.0905273 14.9189 0.343652C15.1721 0.596777 15.1721 0.990528 14.9189 1.24365L8.45019 7.5999C8.30957 7.74053 8.16895 7.8249 8.0002 7.8249Z"
            fill="black" />
        </svg>
      </ComboboxTrigger>
    </ComboboxAnchor>

    <ComboboxPortal>
      <ComboboxContent
        class="z-1 mt-1 rounded-md border border-blue-gray-200 bg-white shadow-lg w-[var(--reka-combobox-trigger-width)] max-h-60 overflow-y-auto overflow-x-hidden"
        position="popper" :align="'start'"
      >
        <ComboboxViewport class="p-4 w-[var(--reka-combobox-trigger-width)]">
          <ComboboxEmpty class="text-blue-gray-500 block">
            No se encontraron resultados
            <template v-if="props.freeClient">
              <span class="text-sm block mt-5">
                Se guardará como: "{{ query }}"
              </span>
            </template>
          </ComboboxEmpty>
          <ComboboxGroup v-if="filteredOptions.length > 0">
            <ComboboxItem
              v-for="opt in filteredOptions"
              :key="opt.value"
              :value="opt.value"
              class="cursor-pointer select-none px-4 py-2 mb-4 last:mb-0 text-blue-gray-600
              hover:bg-gray-100
              data-[state=checked]:bg-blue-gray-200
              data-[state=checked]:text-blue-gray-900
              flex items-center gap-2
              rounded-md
              outline-none focus:outline-none focus-visible:outline-none"
            >
              <ComboboxItemIndicator>
                <svg class="h-4 w-4 text-blue-gray-900" fill="none" stroke="currentColor"
                  stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </ComboboxItemIndicator>
              <span>
                {{ opt.label }}
              </span>
            </ComboboxItem>
          </ComboboxGroup>
          <ComboboxSeparator />
        </ComboboxViewport>
      </ComboboxContent>
    </ComboboxPortal>
  </ComboboxRoot>
  <input type="hidden" :name="props.name" :value="modelValue" />
  <InputError class="mt-2" :message="props.error" />
</template>
