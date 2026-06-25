<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { VisuallyHidden } from 'reka-ui'
import { Form } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogClose } from '@/components/ui/dialog'
import { Button, ButtonDelete } from '@/components/ui/button'
import { Datepicker, InputText, Timepicker } from '@/components/ui/input'
import { ListEmployees } from '@/components/blocks'
import { AlertTriangle, Calendar, Save, Trash2, UserRound, X } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import type { AssignedHour } from '@/types'

interface Props {
  assignedHour?: AssignedHour | null
  employeeId: string
  show: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
}>()

const { t } = useI18n()

const selectedEmployeeId = ref<string | undefined>(props.assignedHour?.employee_id)
const date = ref<string | undefined>(props.assignedHour?.date)
const timeStart = ref<string | undefined>(props.assignedHour?.time_start)
const timeEnd = ref<string | undefined>(props.assignedHour?.time_end)

const serviceId = computed(() => props.assignedHour?.service_id)
const isReady = computed(() => !!props.assignedHour?.id)
const isLocked = computed(() => (props.assignedHour?.time_records_count ?? 0) > 0)

watch(
  () => props.assignedHour,
  (value) => {
    selectedEmployeeId.value = value?.employee_id
    date.value = value?.date
    timeStart.value = value?.time_start
    timeEnd.value = value?.time_end
  },
  { immediate: true }
)

function resetForm() {
  selectedEmployeeId.value = props.assignedHour?.employee_id
  date.value = props.assignedHour?.date
  timeStart.value = props.assignedHour?.time_start
  timeEnd.value = props.assignedHour?.time_end
}
</script>

<template>
  <Dialog :open="show">
    <DialogContent class="w-full max-w-lg sm:max-w-xl md:max-w-2xl lg:max-w-3xl max-h-[90vh] overflow-y-auto" :showCloseButton="false">
      <Form
        v-if="assignedHour && isReady"
        method="put"
        :action="route('employees.assigned-hour-events.update', { employee: employeeId, assignedHour: assignedHour.id })"
        reset-on-success
        :options="{ preserveScroll: true }"
        class="space-y-8"
        v-slot="{ errors, processing, reset, clearErrors }"
        @success="() => { emit('saved'); emit('close'); }"
      >
        <DialogHeader>
          <DialogTitle class="text-2xl leading-8 font-bold text-blue-gray-900">
            {{ t('employees.edit_assigned_hour') }}
          </DialogTitle>
          <VisuallyHidden asChild>
            <DialogDescription />
          </VisuallyHidden>
        </DialogHeader>

        <DialogDescription>
          <div
            v-if="isLocked"
            class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800"
          >
            <AlertTriangle class="mt-0.5 size-5 shrink-0 text-amber-600" />
            <div>
              <p class="font-semibold">{{ t('employees.assigned_hour_locked') }}</p>
              <p>{{ t('employees.assigned_hour_locked_help') }}</p>
            </div>
          </div>

          <div class="grid grid-cols-12 gap-4">
            <InputText
              containerClass="col-span-12 md:col-span-6"
              :label="t('employees.assigned_hour_client')"
              :model-value="assignedHour.client.name"
              disabled
            />

            <InputText
              containerClass="col-span-12 md:col-span-6"
              :label="t('employees.assigned_hour_service')"
              :model-value="assignedHour.service.name"
              disabled
            />

            <template v-if="isLocked">
              <InputText
                containerClass="col-span-12 md:col-span-4"
                :label="t('employees.assigned_hour_date')"
                :model-value="date"
                disabled
              />

              <InputText
                containerClass="col-span-6 md:col-span-4"
                :label="t('clients.start_hour')"
                :model-value="timeStart"
                disabled
              />

              <InputText
                containerClass="col-span-6 md:col-span-4"
                :label="t('clients.end_hour')"
                :model-value="timeEnd"
                disabled
              />
            </template>

            <template v-else>
              <div class="col-span-12 md:col-span-4">
                <div class="mb-2 flex items-center gap-2 text-blue-gray-500">
                  <Calendar class="size-4" />
                  <label class="text-sm leading-5 font-normal">{{ t('employees.assigned_hour_date') }}</label>
                </div>
                <Datepicker
                  name="date"
                  :default-value="date"
                  @update:modelValue="(value: any) => date = value"
                  :error="errors?.date"
                />
              </div>

              <Timepicker
                containerClass="col-span-6 md:col-span-4"
                name="time_start"
                :label="t('clients.start_hour')"
                :model-value="timeStart"
                @update:modelValue="(value) => timeStart = value"
                :error="errors?.time_start"
                required
              />

              <Timepicker
                containerClass="col-span-6 md:col-span-4"
                name="time_end"
                :label="t('clients.end_hour')"
                :model-value="timeEnd"
                @update:modelValue="(value) => timeEnd = value"
                :error="errors?.time_end"
                required
              />
            </template>
          </div>

          <div class="mt-6 leading-7 font-medium text-blue-gray-900">
            <div class="mb-2 flex items-center gap-2">
              <UserRound class="size-4 text-blue-600" />
              <label class="text-lg">{{ t('employees.assigned_hour_employee') }}</label>
            </div>

            <InputText
              v-if="isLocked"
              :model-value="assignedHour.employee?.user?.name"
              disabled
            />

            <ListEmployees
              v-else
              name="employee_id"
              :model-value="selectedEmployeeId"
              @update:modelValue="(value) => selectedEmployeeId = value"
              :assigned-hour-id="assignedHour.id"
              :event-id="assignedHour.assigned_hours_template_id"
              :service-id="serviceId"
              :time-start="timeStart"
              :time-end="timeEnd"
              recurrency="0"
              :date="date"
              :error="errors?.employee_id"
            />
            <InputText type="hidden" name="employee_id" :model-value="selectedEmployeeId" />
          </div>
        </DialogDescription>

        <DialogFooter class="gap-6 flex items-center">
          <ButtonDelete
            v-if="!isLocked"
            :action="route('employees.assigned-hour-events.destroy', { employee: employeeId, assignedHour: assignedHour.id })"
            @deleted="() => { emit('close'); emit('saved'); }"
            class="has-[>svg]:px-6 bg-red-700 hover:bg-red-600 text-white"
          >
            <Trash2 class="size-4 text-white" />
            {{ t('layout.delete') }}
          </ButtonDelete>

          <div class="ml-auto flex gap-6">
            <DialogClose as-child>
              <Button
                class="text-blue-gray-600 border-blue-gray-500 border-2 has-[>svg]:px-6"
                variant="outline"
                @click="() => { emit('close'); resetForm(); clearErrors(); reset(); }"
              >
                <X class="size-4" strokeWidth="3" />
                {{ t('layout.cancel') }}
              </Button>
            </DialogClose>

            <Button v-if="!isLocked" type="submit" :disabled="processing" class="has-[>svg]:px-6">
              <Save class="size-4 text-white" />
              {{ t('clients.save_changes') }}
            </Button>
          </div>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>
</template>