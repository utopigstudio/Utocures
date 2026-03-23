<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { VisuallyHidden } from 'reka-ui';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose, DialogDescription } from '@/components/ui/dialog'
import { Button, ButtonDelete } from '@/components/ui/button'
import { Form } from '@inertiajs/vue3'
import { InputText, Select, Timepicker, InputCheckboxGroup, RadioButtons, Datepicker } from '@/components/ui/input'
import { ListEmployees } from '@/components/blocks'
import { Calendar, X, Save, Trash2 } from 'lucide-vue-next'
import type { ServiceOption, AssignedHourTemplate, SelectOption } from '@/types'
import { useI18n } from 'vue-i18n'

interface Props {
  hourTemplate?: AssignedHourTemplate | null
  services: ServiceOption[]
  show: boolean
  parentId: string
}

const props = defineProps<Props>()

const emit = defineEmits(['close', 'saved', 'deleted'])

const serviceId = ref<string | undefined>(props.hourTemplate?.service_id ?? undefined)
const employeeId = ref<string | undefined>(props.hourTemplate?.employee_id ?? undefined)
const timeStart = ref<string | undefined>(props.hourTemplate?.time_start ?? undefined)
const timeEnd = ref<string | undefined>(props.hourTemplate?.time_end ?? undefined)
const recurrency = ref<string | number>(props.hourTemplate?.recurrency ?? '1')
const daysOfWeek = ref<(string | number)[] | undefined>(props.hourTemplate?.days_of_week ?? [])
const date = ref<string | undefined>(props.hourTemplate?.date ?? undefined)
const dateStart = ref<string | undefined>(props.hourTemplate?.date_start ?? undefined)
const dateEnd = ref<string | undefined>(props.hourTemplate?.date_end ?? undefined)
const isEdit = computed(() => props.hourTemplate?.id !== undefined)

const { t } = useI18n()

const serviceOptions: SelectOption[] = props.services.map((service) => ({
    label: service.name,
    value: service.id,
  }))

const recurrencyOptions: SelectOption[] = [
  { label: t('layout.once'), value: '0' },
  { label: t('layout.weekly'), value: '1' },
  { label: t('layout.biweekly'), value: '2' },
]
  
const daysOfWeekOptions: SelectOption[] = [
  { label: t('layout.monday'), value: '1' },
  { label: t('layout.tuesday'), value: '2' },
  { label: t('layout.wednesday'), value: '3' },
  { label: t('layout.thursday'), value: '4' },
  { label: t('layout.friday'), value: '5' },
  { label: t('layout.saturday'), value: '6' },
  { label: t('layout.sunday'), value: '0' },
]

watch(() => props.hourTemplate, (newVal) => {
  if (!newVal) return
  serviceId.value = newVal.service_id
  employeeId.value = newVal.employee_id
  timeStart.value = newVal.time_start
  timeEnd.value = newVal.time_end
  recurrency.value = newVal.recurrency
  daysOfWeek.value = newVal.days_of_week
  date.value = newVal.date
  dateStart.value = newVal.date_start
  dateEnd.value = newVal.date_end
})

watch(recurrency, () => {
  if (recurrency.value == '0') {
    daysOfWeek.value = []
    dateStart.value = undefined
    dateEnd.value = undefined
  } else {
    date.value = undefined
  }
})

const resetForm = () => {
  serviceId.value = undefined
  employeeId.value = undefined
  timeStart.value = undefined
  timeEnd.value = undefined
  recurrency.value = '1'
  daysOfWeek.value = []
  date.value = undefined
  dateStart.value = undefined
  dateEnd.value = undefined
}
</script>

<template>
  <Dialog :open="show">
    <DialogContent class="w-full max-w-lg sm:max-w-xl md:max-w-2xl lg:max-w-3xl max-h-[90vh] overflow-y-auto" :showCloseButton="false">
      <Form
        :method="isEdit ? 'put' : 'post'"
        :action="isEdit ? route('clients.assigned-hours-templates.update', {client: props.parentId, template: props.hourTemplate?.id}) :  route('clients.assigned-hours-templates.store', {id: props.parentId})  "
        reset-on-success
        :options="{
          preserveScroll: true
        }"
        class="space-y-8"
        v-slot="{ errors, processing, reset, clearErrors }"
        @success="() => { emit('saved'); emit('close'); resetForm(); }"
      >
        <DialogHeader>
          <DialogTitle class="text-2xl leading-8 font-bold text-blue-gray-900">
            {{ isEdit ? $t('clients.edit_service_hours') : $t('clients.add_service_hours') }}
          </DialogTitle>
          <VisuallyHidden asChild>
            <DialogDescription />
          </VisuallyHidden>
        </DialogHeader>
        <DialogDescription>
          <div class="grid grid-cols-12 gap-4">
            <Select
              name="service_id"
              :label="$t('clients.service')"
              containerClass="col-span-12 md:col-span-6"
              :model-value="serviceId"
              @update:modelValue="(value) => serviceId = value"
              :options="serviceOptions"
              :required="true"
              :error="errors?.service_id"
            />

            <Timepicker
              containerClass="col-span-6 md:col-span-3"
              type="date"
              name="time_start"
              :label="$t('clients.start_hour')"
              :placeholder="$t('clients.start_hour')"
              :model-value="timeStart"
              @update:modelValue="(value) => timeStart = value"
              :required="true"
              :error="errors?.time_start"
            />

            <Timepicker
              containerClass="col-span-6 md:col-span-3"
              type="date"
              name="time_end"
              :label="$t('clients.end_hour')" 
              :placeholder="$t('clients.end_hour')"
              :model-value="timeEnd"
              @update:modelValue="(value) => timeEnd = value"
              :required="true"
              :error="errors?.time_end"
            />
          </div>
          <div class="flex flex-col gap-4 mt-6">
            <label class="text-lg leading-7 font-medium text-blue-gray-900">{{ $t('clients.recurrency') }}</label>
            <RadioButtons
              name="recurrency"
              :model-value="recurrency?.toString()"
              @update:modelValue="(value) => recurrency = value"
              :options="recurrencyOptions"
              :error="errors?.recurrency"
              layout="vertical"
            />
          </div>
          <div v-if="recurrency != '0'" class="flex flex-col gap-4 mt-6">
            <label class="text-lg leading-7 font-medium text-blue-gray-900">{{ $t('clients.select_days_week') }}</label>
            <InputCheckboxGroup
              :name="`days_of_week[]`"
              :model-value="daysOfWeek"
              @update:modelValue="(value) => daysOfWeek = value"
              :options="daysOfWeekOptions"
              :error="errors?.days_of_week"
            />
          </div>
          <div v-if="recurrency != '0'" class="grid grid-cols-12 gap-4 mt-6">
            <div class="col-span-12 md:col-span-6">
              <div class="flex items-center mb-2 text-blue-gray-500">
                <label class="text-sm leading-5 font-normal ">{{ $t('clients.start_date_optional') }}</label>
              </div>
              <Datepicker
                name="date_start"
                :default-value="dateStart"
                @update:modelValue="(value: any) => dateStart = value"
                :minDate="new Date().toISOString().split('T')[0]"
                :error="errors?.date_start"
              />
            </div>
            <div class="col-span-12 md:col-span-6">
              <div class="flex items-center mb-2 text-blue-gray-500">
                <label class="text-sm leading-5 font-normal ">{{ $t('clients.end_date_optional') }}</label>
              </div>
              <Datepicker
                name="date_end"
                :default-value="dateEnd"
                @update:modelValue="(value: any) => dateEnd = value"
                :minDate="dateStart || new Date().toISOString().split('T')[0]"
                :error="errors?.date_end"
              />
            </div>
          </div>
          <div v-else class="flex flex-col gap-4 mt-6">
            <div class="flex items-center gap-2 text-blue-gray-500">
              <Calendar class="w-4 h-4" />
              <label class="text-sm leading-5 font-normal ">{{ $t('clients.date') }}</label>
            </div>
            <Datepicker
              name="date"
              :default-value="date"
              @update:modelValue="(value: any) => date = value"
              :minDate="new Date().toISOString().split('T')[0]"
              :error="errors?.date"
            />
          </div>
          <div class="leading-7 font-medium text-blue-gray-900 mt-6">
            <ListEmployees
              name="employee_id"
              :label="$t('clients.select_employee')"
              :model-value="employeeId"
              @update:modelValue="(value) => employeeId = value"
              :event-id="props.hourTemplate?.id"
              :client-id="props.parentId"
              :service-id="serviceId"
              :time-start="timeStart"
              :time-end="timeEnd"
              :recurrency="recurrency"
              :days-of-week="daysOfWeek"
              :date-start="dateStart"
              :date-end="dateEnd"
              :date="date"
              :error="errors?.employee_id"
            />
            <InputText type="hidden" name="employee_id" :model-value="employeeId" />
          </div>
        </DialogDescription>

        <DialogFooter class="gap-6 flex items-center">
          <ButtonDelete
            v-if="props.hourTemplate?.id"
            :action="route('clients.assigned-hours-templates.destroy', { client: props.parentId, template: props.hourTemplate?.id })"
            @deleted="() => { emit('close'); resetForm(); }"
            class="has-[>svg]:px-6 bg-red-700 hover:bg-red-600 text-white"
          >
            <Trash2 class="size-4 text-white" />
            {{ $t('layout.delete') }}
          </ButtonDelete>
          <div class="flex gap-6 ml-auto">
            <DialogClose as-child>
              <Button 
                class="text-blue-gray-600 border-blue-gray-500 border-2 has-[>svg]:px-6"
                variant="outline" 
                @click="
                  () => {
                    emit('close');
                    resetForm();
                    clearErrors();
                    reset();
                  }
                "
              >
                <X class="size-4" strokeWidth="3" />
                {{ $t('layout.cancel') }}
              </Button>
            </DialogClose>
            <Button type="submit" :disabled="processing" class="has-[>svg]:px-6">
              <Save class="size-4 text-white" />
              {{ isEdit ? $t('clients.save_changes') : $t('clients.create') }}
            </Button>
          </div>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>
</template>