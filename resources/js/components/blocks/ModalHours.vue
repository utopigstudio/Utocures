<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { VisuallyHidden } from 'reka-ui';
import { Button, ButtonDelete } from '@/components/ui/button'
import { Timepicker } from '@/components/ui/input'
import { Form } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose, DialogDescription } from '@/components/ui/dialog'
import type { AvailableHour } from '@/types'

interface Props {
  hour?: AvailableHour | null
  show: boolean
  parentId: number | string
}

const props = defineProps<Props>()

const emit = defineEmits(['close', 'saved', 'deleted'])

const dayOfWeek = ref<number | undefined>(undefined)
const timeStart = ref<string>('')
const timeEnd = ref<string>('')
const isEdit = computed(() => !!props.hour?.id)

watch(() => props.hour, (newVal) => {
  if (!newVal) return

  dayOfWeek.value = newVal?.day_of_week
  timeStart.value = newVal?.time_start
  timeEnd.value = newVal?.time_end
})
</script>

<template>
  <Dialog :open="show">
    <DialogContent class="max-w-3xl" :showCloseButton="false">
      <Form
        :method="isEdit ? 'put' : 'post'"
        :action="isEdit ? route('employees.assigned-hours.update', { employee: props.parentId, hour: props.hour?.id, day_of_week: dayOfWeek }) : route('employees.assigned-hours.store', { employee: props.parentId, day_of_week: dayOfWeek })"
        reset-on-success
        :options="{
          preserveScroll: true
        }"
        class="space-y-8"
        v-slot="{ errors, processing, reset, clearErrors }"
        @success="() => { emit('saved'); emit('close'); }"
      >
        <DialogHeader>
          <DialogTitle class="text-2xl leading-8 font-bold text-blue-gray-900">
            {{ isEdit ? $t('clients.edit_hours') : $t('clients.add_hours') }}
          </DialogTitle>
          <VisuallyHidden asChild>
            <DialogDescription />
          </VisuallyHidden>
        </DialogHeader>

        <div class="flex flex-row gap-4">
          <Timepicker
            class="mt-1 block w-full"
            name="time_start"
            :label="$t('clients.time_start')"
            :model-value="timeStart"
            @update:modelValue="val => timeStart = val"
            :error="errors.time_start?.[0]"
            required
          />
          <Timepicker
            class="mt-1 block w-full"
            name="time_end"
            :label="$t('clients.time_end')"
            :model-value="timeEnd"
            @update:modelValue="val => timeEnd = val"
            :error="errors.time_end?.[0]"
            required
          />
        </div>

        <DialogFooter class="mt-4 flex gap-2">
          <DialogClose asChild>
            <Button variant="outline" @click="
              () => {
                emit('close');
                clearErrors();
                reset();
              }
            ">
              {{ $t('layout.cancel') }}
            </Button>
          </DialogClose>
          <ButtonDelete
            v-if="props.hour?.id"
            :action="route('employees.assigned-hours.destroy', { employee: props.parentId, hour: props.hour.id })"
            @deleted="emit('close'), emit('saved')"
          >
            {{ $t('layout.delete') }}
          </ButtonDelete>
          <Button type="submit" :disabled="processing">
            {{ isEdit ? $t('clients.save_changes') : $t('clients.create') }}
          </Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>
</template>
