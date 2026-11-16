<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
  size?: HTMLAttributes['class'];
}>();
</script>

<template>
  <img
    src="/images/icon.svg"
    alt="Utocures"
    width="500"
    height="500"
    :class="cn('size-8 shrink-0 object-contain', props.size)"
  />
</template>
