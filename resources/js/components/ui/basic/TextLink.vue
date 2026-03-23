<script setup lang="ts">
import { Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';

interface Props {
  href: string;
  tabindex?: number;
  method?: Method;
  as?: string;
  class?: string;
}

const props = defineProps<Props>();

const overrideClasses = cn('text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!', props.class);
</script>

<template>
  <Link :href="href" :tabindex="tabindex" :method="method" :as="as" :class="overrideClasses">
    <slot />
  </Link>
</template>
