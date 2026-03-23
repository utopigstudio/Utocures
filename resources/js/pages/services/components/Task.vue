<script setup lang="ts">
import { InputText } from '@/components/ui/input'
import type { FieldBinding } from '@/types'

interface Props {
  field: (key: string) => FieldBinding
  index: number
}

const props = defineProps<Props>()
</script>

<template>
  <input type="hidden" :name="props.field('id').name" :value="props.field('id').id" />
  <InputText v-bind="props.field('name')" class="mb-0" containerClass="flex items-center gap-2" labelClass="mb-0 whitespace-nowrap" :label="`${$t('services.task')} ${props.index + 1}`" />
</template>
