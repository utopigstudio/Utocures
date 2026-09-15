<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import HelpButton from '@/components/help/HelpButton.vue'

interface Props {
  class?: HTMLAttributes['class']
  helpArticle?: string
  helpSection?: 'steps' | 'effects' | 'questions'
  helpLabel?: string
}

const props = defineProps<Props>()
</script>

<template>
  <h3
    data-slot="card-title"
    :class="cn('leading-none font-semibold', props.class)"
  >
    <slot />
    <HelpButton v-if="helpArticle" :article="helpArticle" :section="helpSection" :label="helpLabel" />
  </h3>
</template>
