<script setup lang="ts">
import Label from '@/components/ui/label/Label.vue'
import BooleanIcon from '@/components/ui/basic/BooleanIcon.vue'

interface Props {
  label?: string
  value: boolean
}

const props = defineProps<Props>()
</script>

<template>
   <div class="flex items-center gap-2">
    <Label :id="props.label" class="mb-0">{{ props.label }}</Label>
    <BooleanIcon :value="props.value" />
  </div>
</template>
