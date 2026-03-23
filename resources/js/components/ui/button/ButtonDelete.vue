<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { DeleteModal } from '@/components/blocks';
import { type ButtonVariants } from '.'
import type { HTMLAttributes } from 'vue'

interface Props {
  action: string
  showCloseButton?: boolean
  variant?: ButtonVariants['variant']
  size?: ButtonVariants['size']
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
  showCloseButton: false,
  variant: 'destructive',
  size: 'default',
});

const emit = defineEmits(['deleted']);

const show = ref(false)
</script>

<template>
  <Button type="button" :variant="props.variant" :size="props.size" @click.prevent="show = true" :class="props.class">
    <slot />
  </Button>
  <DeleteModal :show="show" :action="props.action" @deleted="emit('deleted'); show = false" @close="show = false"
  />
</template>
