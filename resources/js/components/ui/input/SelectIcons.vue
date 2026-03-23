<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { ref, onMounted } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { Label } from '@/components/ui/label'
import InputError from '@/components/ui/input/InputError.vue'
import type { IconOption } from '@/types'

import {
  SelectPortal,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectRoot,
  SelectTrigger,
  SelectValue,
  SelectViewport,
  SelectItemText,
} from 'reka-ui'

interface Props {
  name: string
  label?: string
  defaultValue?: string
  modelValue?: string
  class?: HTMLAttributes['class']
  required?: boolean
  error?: string
  collections?: string[]
  limit?: number
}

const props = defineProps<Props>()

const options = ref<IconOption[]>([])
const loading = ref(false)
const offset = ref(0)
const hasMore = ref(true)

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

const fetchIcons = async () => {
  if (loading.value || !hasMore.value) return

  try {
    loading.value = true

    const collections = props.collections ?? ['healthicons']
    const limit = props.limit ?? 100

    const params = new URLSearchParams({
      collections: collections.join(','),
      limit: String(limit),
      offset: String(offset.value),
      height: '20px',
    })

    const response = await fetch(route('api.icon.index') + '?' + params, {
      headers: { Accept: 'application/json' },
    })

    if (!response.ok) throw new Error('Failed to fetch icons')

    const data = await response.json()

    const newIcons: IconOption[] = data.data.map((icon: any) => ({
      slug: icon.slug,
      svg: icon.svg,
    }))

    options.value.push(...newIcons)
    offset.value += newIcons.length

    if (newIcons.length < limit) {
      hasMore.value = false
    }
  } catch (e) {
    console.error('Error fetching icons:', e)
  } finally {
    loading.value = false
  }
}

const onScroll = (e: Event) => {
  const el = e.target as HTMLElement
  if (el.scrollTop + el.clientHeight >= el.scrollHeight - 10) {
    fetchIcons()
  }
}

onMounted(() => {
  fetchIcons()
})
</script>

<template>
  <Label v-if="props.label">{{ props.label }}</Label>

  <SelectRoot v-model="modelValue" :name="props.name">

    <!-- TRIGGER -->
    <SelectTrigger
      :class="cn(
        'flex h-12 w-full items-center justify-between rounded border border-blue-gray-200 bg-white px-4 py-3 text-base shadow-xs transition focus-visible:border-blue-900 focus-visible:ring-2 focus-visible:ring-blue-900/50 outline-none cursor-pointer',
        props.class
      )"
    >
      <SelectValue as="div" class="text-blue-gray-600">
        <span
          v-if="modelValue"
          v-html="options.find(opt => opt.slug === modelValue)?.svg"
        />
      </SelectValue>

      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="8" viewBox="0 0 16 8">
        <path
          d="M8 7.82c-.17 0-.31-.06-.45-.17L1.08 1.3a.64.64 0 0 1 0-.9.64.64 0 0 1 .9 0L8 6.28l6.02-5.94a.64.64 0 0 1 .9 0 .64.64 0 0 1 0 .9L8.45 7.6c-.14.14-.28.22-.45.22Z"
          fill="black"
        />
      </svg>
    </SelectTrigger>

    <SelectPortal :disabled="true">
      <SelectContent
        :body-lock="false"
        class="z-1 mt-1 rounded-md border border-blue-gray-200 bg-white shadow-lg w-[var(--reka-select-trigger-width)]"
        position="popper"
        align="start"
      >
        <SelectViewport
          class="max-h-60 overflow-y-auto p-4"
          @scroll="onScroll"
        >
          <SelectGroup>
            <SelectLabel class="sr-only">Iconos</SelectLabel>

            <div class="grid grid-cols-5 gap-3">
              <SelectItem
                v-for="opt in options"
                :key="opt.slug"
                :value="opt.slug"
                class="cursor-pointer p-2 flex items-center justify-center
                       rounded-md border border-transparent
                       hover:bg-gray-100
                       data-[state=checked]:bg-blue-100
                       data-[state=checked]:border-blue-500
                       outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
              >
                <SelectItemText>
                  <span v-html="opt.svg" />
                </SelectItemText>
              </SelectItem>
              <div
                v-if="loading"
                class="col-span-5 text-center text-xs text-gray-500 py-2"
              >
                Cargando más iconos…
              </div>
              <div
                v-if="!hasMore && !loading"
                class="col-span-5 text-center text-xs text-gray-400 py-2"
              >
                No hay más iconos
              </div>
            </div>
          </SelectGroup>
        </SelectViewport>
      </SelectContent>
    </SelectPortal>

  </SelectRoot>
  <InputError class="mt-2" :message="props.error" />
</template>
