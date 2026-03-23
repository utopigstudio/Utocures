<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()
</script>

<template>
  <main
    data-slot="sidebar-inset"
    :class="cn(
      'bg-gray-50 relative flex w-full flex-1 flex-col',
      props.class,
    )"
  >
    <slot />
  </main>
</template>
