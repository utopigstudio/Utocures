<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { ref, computed } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { Label } from '@/components/ui/label'
import { Eye, EyeOff } from 'lucide-vue-next'
import InputError from '@/components/ui/input/InputError.vue';
import { inputVariants, type InputVariants } from '.'

interface Props {
  variant?: InputVariants['variant']
  name?: string
  label?: string
  type?: string
  step?: string | number
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
  labelClass?: HTMLAttributes['class']
  containerClass?: HTMLAttributes['class']
  placeholder?: string
  autocomplete?: string
  autofocus?: boolean
  required?: boolean
  error?: string
  tabindex?: number
  icon?: any
  disabled?: boolean
}

const props = defineProps<Props>()
const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, { passive: true, defaultValue: props.defaultValue })
const showPassword = ref(false)
const isPassword = computed(() => props.type === 'password')
const inputType = computed(() => isPassword.value ? (showPassword.value ? 'text' : 'password') : props.type || 'text')
const eyeIcon = computed(() => showPassword.value ? EyeOff : Eye)
</script>

<template>
  <div :class="props.containerClass">
    <Label v-if="props.label" :for="props.name" :class="props.labelClass">{{ props.label }}</Label>
    <div class="relative">
      <input
        :id="props.name"
        :type="inputType"
        :step="props.step"
        :name="props.name"
        v-model="modelValue"
        data-slot="input"
        :class="cn(
          inputVariants({ variant: props.variant }),
          isPassword && 'pr-12',
          props.class,
        )"
        :autocomplete="props.autocomplete"
        :placeholder="props.placeholder"
        :autofocus="props.autofocus || undefined"
        :required="props.required || undefined"
        :tabindex="props.tabindex"
        :disabled="props.disabled"
      />
      <button v-if="isPassword" type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 transition-colors focus:outline-none" :aria-label="showPassword ? $t('inputs.password.hide') : $t('inputs.password.show')">
        <component :is="eyeIcon" class="w-5 h-5" />
      </button>
      <div
        v-if="props.icon && !isPassword"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 transition-colors focus:outline-none"
      >
        <component :is="props.icon" class="w-5 h-5" />
      </div>
    </div>
    <InputError class="mt-2" :message="props.error" />
  </div>
</template>
