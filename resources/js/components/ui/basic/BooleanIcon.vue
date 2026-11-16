<script setup lang="ts">
  import { Check, X } from 'lucide-vue-next'

interface Props {
  value: boolean
}

const props = defineProps<Props>()
</script>

<template>
  <div v-if="props.value" class="bg-success-100 w-[34px] h-[34px] flex items-center justify-center rounded-full">
    <Check :size=20 class="text-success-700" aria-hidden="true" />
  </div>
  <div v-else class="bg-danger-100 w-[34px] h-[34px] flex items-center justify-center rounded-full">
    <X :size=20 class="text-danger-700" aria-hidden="true" />
  </div>
</template>
