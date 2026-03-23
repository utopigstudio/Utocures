<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Trash, Plus } from 'lucide-vue-next'
import type { AnyErrors, AnyItem, ItemTemplateFn } from '@/types'
import { cn } from '@/lib/utils'

interface Props<T = AnyItem> {
  modelValue: T[]
  itemTemplate: ItemTemplateFn<T>
  name: string
  errors?: Record<string, string>
  title?: string
  disableActions?: boolean
  customRemoveClass?: string
}

const props = defineProps<Props>()
const items = ref(props.modelValue ?? [])
const emits = defineEmits<{ (e: 'update:modelValue', val: AnyItem[]): void }>()

if (!items.value.length) {
  items.value.push(props.itemTemplate())
}

watch(
  () => props.modelValue,
  (val) => {
    if (val && val !== items.value) {
      items.value.splice(0, items.value.length, ...val)
    }
  },
  { deep: true }
)

watch(items, (val) => emits('update:modelValue', val), { deep: true })

function addItem() {
  items.value.push(props.itemTemplate())
}

function removeItem(index: number) {
  items.value.splice(index, 1)
}

function onUpdate(index: number, key: string, value: any) {
  items.value[index][key] = value
}

const groupedErrors = computed(() => {
  if (!props.errors) return {}
  const grouped: Record<number, AnyErrors> = {}
  const regex = new RegExp(`^${props.name}\\.(\\d+)\\.(.+)$`)
  for (const [key, msg] of Object.entries(props.errors)) {
    const match = key.match(regex)
    if (!match) continue
    const [, i, field] = match
    const idx = Number(i)
    if (!grouped[idx]) grouped[idx] = {}
    grouped[idx][field] = msg
  }
  return grouped
})

function getError(index: number, field: string) {
  return groupedErrors.value[index]?.[field]
}

function getButtonClass(index: number) {
  return cn(`m-0 p-0 self-center ${index ? 'mb-4' : 'mt-4'}`, props.customRemoveClass)
}

function field(index: number, key: string) {
 return {
    id: items.value[index].id ?? null,
    name: `${props.name}[${index}][${key}]`,
    modelValue: items.value[index][key],
    'onUpdate:modelValue': (val: any) => onUpdate(index, key, val),
    error: getError(index, key) || '',
  }
}
</script>

<template>
  <div v-if="props.title" class="mb-4 text-lg font-medium">
    {{ props.title }}
  </div>

  <div class="space-y-4" @keydown.enter.stop.prevent>
    <div
      v-for="(item, index) in items"
      :key="index"
      class="flex flex-col gap-2 lg:flex-row lg:items-start lg:gap-2"
    >
      <slot
        :model="item"
        :index="index"
        :field="(key: string) => field(index, key)"
      />
      <Button v-if="!disableActions" type="button" :class="getButtonClass(index)" variant="ghost" @click="removeItem(index)">
        <Trash />
      </Button>
    </div>
    <Button
      v-if="!disableActions"
      type="button"
      variant="secondary"
      size="lg"
      class="w-full lg:w-auto"
      @click="addItem"
    >
      <Plus /> {{ $t('layout.add_item') }}
    </Button>
    <slot name="footer" />
  </div>
</template>


