<script setup lang="ts">
import { AppLogoIcon } from '@/components/app';
import { useSidebar } from '../ui/sidebar/utils'

const { state } = useSidebar()
</script>

<template>
  <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
    <AppLogoIcon />
  </div>
  <div v-if="state === 'expanded'" class="ml-1 grid flex-1 text-left text-sm">
    <span class="mb-0.5 text-2xl leading-8 font-bold font-sans">Utocures</span>
  </div>
</template>
