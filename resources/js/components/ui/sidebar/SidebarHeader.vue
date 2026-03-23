<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useSidebar } from './utils'

interface Props {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()
const { state } = useSidebar()
</script>

<template>
  <div
    data-slot="sidebar-header"
    data-sidebar="header"
    :class="cn('flex flex-row gap-2 pt-10 pb-4', props.class, state === 'expanded' ? 'px-6' : 'px-4')"
  >
    <slot />
  </div>
</template>
