<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { InputText, InputTextarea, Select } from '@/components/ui/input'
import { MonthlyDayCalendar } from '@/components/ui/calendar'
import { CalendarDays, Pencil, Save } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

interface EmployeeOption {
  label: string
  value: string
}

interface CalendarRecord {
  id: string
  date_in: string
  date_out?: string | null
  time_in: string
  time_out?: string | null
  date_in_formatted: string
  date_out_formatted?: string | null
  client_name?: string
  service_name?: string
  notes_count: number
  latest_modification?: {
    audit_id: string
    reason: string
    user_name?: string | null
    created_at_formatted: string
  } | null
}

interface Props {
  employeeOptions: EmployeeOption[]
  selectedEmployeeId?: string | null
  selectedDate: string
  records: CalendarRecord[]
  selectedEmployee?: {
    id: string
    name?: string | null
    avatar?: string | null
  } | null
}

const props = defineProps<Props>()
const { t } = useI18n()

function parseLocalDate(value: string) {
  const [year, month, day] = value.split('-').map(Number)

  return new Date(year, month - 1, day)
}

function formatDateForRequest(day: Date) {
  const year = day.getFullYear()
  const month = `${day.getMonth() + 1}`.padStart(2, '0')
  const date = `${day.getDate()}`.padStart(2, '0')

  return `${year}-${month}-${date}`
}

const selectedDay = ref(parseLocalDate(props.selectedDate))
const employeeId = ref<string>(props.selectedEmployeeId ?? props.employeeOptions[0]?.value ?? '')
const editModalOpen = ref(false)
const selectedTimeRecord = ref<CalendarRecord | null>(null)
const editForm = useForm({
  date_in: '',
  date_out: '',
  time_in: '',
  time_out: '',
  modification_reason: '',
})

watch(
  () => props.selectedDate,
  (value) => {
    selectedDay.value = parseLocalDate(value)
  }
)

watch(
  () => props.selectedEmployeeId,
  (value) => {
    employeeId.value = value ?? props.employeeOptions[0]?.value ?? ''
  }
)

const groupedRecords = computed<Record<string, CalendarRecord[]>>(() => {
  return props.records.reduce<Record<string, CalendarRecord[]>>((carry, record) => {
    carry[record.date_in] ??= []
    carry[record.date_in].push(record)

    return carry
  }, {})
})

const eventsByDate = computed<Record<string, { count: number }>>(() => {
  return Object.entries(groupedRecords.value).reduce<Record<string, { count: number }>>((carry, [date, records]) => {
    carry[date] = { count: records.length }

    return carry
  }, {})
})

const selectedDayKey = computed(() => formatDateForRequest(selectedDay.value))
const selectedDayRecords = computed(() => groupedRecords.value[selectedDayKey.value] ?? [])
const selectedDayLabel = computed(() => selectedDay.value.toLocaleDateString('es-ES', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
}))

function visitCalendar(overrides: Partial<Record<'employee_id' | 'filter_date' | 'view', string>>) {
  router.get(
    route('employee-time-records.index'),
    {
      view: 'calendar',
      employee_id: employeeId.value,
      filter_date: formatDateForRequest(selectedDay.value),
      ...overrides,
    },
    { preserveState: true, replace: true }
  )
}

function changeEmployee(value: string) {
  employeeId.value = value
  visitCalendar({ employee_id: value })
}

function selectDay(day: Date) {
  selectedDay.value = new Date(day)
  visitCalendar({ filter_date: formatDateForRequest(day) })
}

function changeMonth(day: Date) {
  selectedDay.value = new Date(day)
  visitCalendar({ filter_date: formatDateForRequest(day) })
}

function resetEditModal() {
  editModalOpen.value = false
  selectedTimeRecord.value = null
  editForm.reset()
  editForm.clearErrors()
}

function openEditModal(timeRecord: CalendarRecord) {
  selectedTimeRecord.value = timeRecord
  editForm.defaults({
    date_in: timeRecord.date_in,
    date_out: timeRecord.date_out ?? '',
    time_in: timeRecord.time_in,
    time_out: timeRecord.time_out ?? '',
    modification_reason: '',
  })
  editForm.reset()
  editForm.clearErrors()
  editModalOpen.value = true
}

function submitTimeRecordUpdate() {
  if (!selectedTimeRecord.value) return

  editForm.put(route('employee-time-records.update', selectedTimeRecord.value.id), {
    preserveScroll: true,
    onSuccess: () => resetEditModal(),
  })
}
</script>

<template>
  <div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-[minmax(0,18rem)_1fr] md:items-end">
      <Select
        :label="t('employee_time_records.select_employee')"
        :model-value="employeeId"
        @update:modelValue="changeEmployee"
        :options="employeeOptions"
        :disabled="!employeeOptions.length"
        :placeholder="t('employee_time_records.select_employee')"
      />

      <p class="text-sm text-muted-foreground">
        {{ t('employee_time_records.calendar_description') }}
      </p>
    </div>

    <div v-if="employeeOptions.length" class="grid gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(22rem,1fr)] lg:items-start">
      <section class="rounded-3xl border border-gray-200 bg-white p-3 shadow-sm sm:p-6">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex size-10 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 sm:size-11">
            <CalendarDays class="size-5" />
          </div>
          <div class="min-w-0">
            <h2 class="text-base font-semibold text-gray-900 sm:text-lg">{{ props.selectedEmployee?.name }}</h2>
            <p class="text-sm text-gray-500">{{ t('employee_time_records.records') }}: {{ props.records.length }}</p>
          </div>
        </div>

        <MonthlyDayCalendar
          v-model="selectedDay"
          :events-by-date="eventsByDate"
          @change-month="changeMonth"
          @select-day="selectDay"
        />
      </section>

      <aside class="rounded-3xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:sticky lg:top-4">
        <div class="mb-4 flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm font-medium uppercase tracking-wide text-blue-600">{{ t('employee_time_records.selected_day') }}</p>
            <h2 class="text-base font-semibold capitalize text-gray-900 sm:text-lg">{{ selectedDayLabel }}</h2>
          </div>
          <span class="shrink-0 rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700">
            {{ selectedDayRecords.length }}
          </span>
        </div>

        <div class="mb-4 text-sm font-medium text-gray-700">{{ t('employee_time_records.records_for_day') }}</div>

        <ul v-if="selectedDayRecords.length" role="list" class="flex flex-col gap-3">
          <li v-for="record in selectedDayRecords" :key="record.id" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 space-y-1 text-sm text-gray-600">
                <p class="font-medium text-gray-900">
                  {{ record.date_in_formatted }} · {{ record.time_in }}
                  <span v-if="record.date_out_formatted || record.time_out">
                    → {{ record.date_out_formatted ?? record.date_in_formatted }} · {{ record.time_out ?? '--:--' }}
                  </span>
                </p>
                <p>{{ record.client_name }}</p>
                <p>{{ record.service_name }}</p>
                <p class="text-xs text-amber-700">{{ t('employee_time_records.incidences') }}: {{ record.notes_count }}</p>

                <div v-if="record.latest_modification" class="mt-3 rounded-2xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs leading-5 text-blue-900">
                  <p class="font-semibold text-blue-950">{{ t('employee_time_records.last_modification') }}</p>
                  <p class="mt-1 whitespace-pre-wrap">{{ record.latest_modification.reason }}</p>
                  <p v-if="record.latest_modification.user_name" class="mt-2 text-blue-800">
                    <span class="font-medium">{{ t('employee_time_records.modified_by') }}:</span>
                    {{ record.latest_modification.user_name }}
                  </p>
                  <p class="text-blue-800">
                    <span class="font-medium">{{ t('employee_time_records.modified_at') }}:</span>
                    {{ record.latest_modification.created_at_formatted }}
                  </p>
                  <Link
                    class="mt-2 inline-flex text-blue-700 underline underline-offset-2 hover:text-blue-900"
                    :href="route('audits.show', record.latest_modification.audit_id)"
                  >
                    {{ t('audit.view') }}
                  </Link>
                </div>
              </div>

              <Button variant="outline" size="sm" type="button" @click="openEditModal(record)">
                <Pencil class="mr-1 size-3.5" />
                {{ t('employee_time_records.edit') }}
              </Button>
            </div>
          </li>
        </ul>

        <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
          {{ t('employee_time_records.no_records_for_day') }}
        </div>
      </aside>
    </div>

    <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center text-sm text-gray-500">
      {{ t('employee_time_records.no_employee_available') }}
    </div>

    <Dialog v-model:open="editModalOpen" @update:open="value => { if (!value) resetEditModal() }">
      <DialogContent class="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{{ t('employee_time_records.edit_title') }}</DialogTitle>
        </DialogHeader>

        <DialogDescription>
          {{ t('employee_time_records.edit_description') }}
        </DialogDescription>

        <form class="space-y-4" @submit.prevent="submitTimeRecordUpdate">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <InputText
              v-model="editForm.date_in"
              name="date_in"
              type="date"
              :label="t('employee_time_records.date_in')"
              :error="editForm.errors.date_in"
              :required="true"
            />

            <InputText
              v-model="editForm.time_in"
              name="time_in"
              type="time"
              :label="t('employee_time_records.time_in')"
              :error="editForm.errors.time_in"
              :required="true"
            />

            <InputText
              v-model="editForm.date_out"
              name="date_out"
              type="date"
              :label="t('employee_time_records.date_out')"
              :error="editForm.errors.date_out"
            />

            <InputText
              v-model="editForm.time_out"
              name="time_out"
              type="time"
              :label="t('employee_time_records.time_out')"
              :error="editForm.errors.time_out"
            />
          </div>

          <InputTextarea
            v-model="editForm.modification_reason"
            name="modification_reason"
            :label="t('employee_time_records.modification_reason')"
            :placeholder="t('employee_time_records.modification_reason_placeholder')"
            :error="editForm.errors.modification_reason"
            :required="true"
          />

          <DialogFooter class="gap-3">
            <Button variant="outline" type="button" @click="resetEditModal()">
              {{ t('layout.cancel') }}
            </Button>
            <Button type="submit" :disabled="editForm.processing">
              <Save class="mr-1 size-4 text-white" />
              {{ t('layout.save_changes') }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
