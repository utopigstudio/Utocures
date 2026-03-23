<script setup lang="ts">
import { Label } from '@/components/ui/label'

interface Props {
  label?: string
  value: any
}

const props = defineProps<Props>()
</script>

<template>
  <div>
    <Label class="mt-4 text-sm leading-5 font-normal text-blue-gray-500 mb-1">{{ props.label }}</Label>
    <div class="text-blue-gray-700 text-base leading-6 font-normal">
      {{ props.value || '-' }}
    </div>
  </div>
</template>
