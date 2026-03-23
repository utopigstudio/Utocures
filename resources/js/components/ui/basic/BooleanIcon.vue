<script setup lang="ts">
  import { Check, X } from 'lucide-vue-next'

interface Props {
  value: boolean
}

const props = defineProps<Props>()
</script>

<template>
  <div v-if="props.value" class="bg-[#DCFCE7] w-[34px] h-[34px] flex items-center justify-center rounded-full">
    <Check :size=20 class="text-[#34D399]" aria-hidden="true" />
  </div>
  <div v-else class="bg-[#FFE4E6] w-[34px] h-[34px] flex items-center justify-center rounded-full">
    <X :size=20 class="text-[#F87171]" aria-hidden="true" />
  </div>
</template>
