<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { InputText, InputTextarea, Select } from '@/components/ui/input'
import DeleteModal from './DeleteModal.vue'
import { CalendarRange, ChevronDown, Pencil, Plus, Save, Trash2, X } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import type { EmployeeStatus, EmployeeStatusPeriod } from '@/types'

interface Props {
  employeeId: string
  statusPeriods: EmployeeStatusPeriod[]
  currentStatus: EmployeeStatus
  activeStatusPeriod?: EmployeeStatusPeriod | null
  nextStatusPeriod?: EmployeeStatusPeriod | null
}

const props = defineProps<Props>()
const { t } = useI18n()
const ALL_STATUS_TYPES = '__all__'

const modalOpen = ref(false)
const periodToEdit = ref<EmployeeStatusPeriod | null>(null)
const periodToDelete = ref<EmployeeStatusPeriod | null>(null)
const selectedType = ref<string>(ALL_STATUS_TYPES)
const expandedYears = ref<string[]>([])

const form = useForm({
  type: 'vacation',
  start_at: '',
  end_at: '',
  notes: '',
})

const statusClasses: Record<string, string> = {
  active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  vacation: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  sick_leave: 'bg-rose-50 text-rose-700 ring-rose-200',
  absence: 'bg-slate-100 text-slate-700 ring-slate-200',
  permission: 'bg-amber-50 text-amber-700 ring-amber-200',
}

const typeOptions = computed(() => [
  { label: t('employees.all_status_types'), value: ALL_STATUS_TYPES },
  { label: t('employees.status_type_vacation'), value: 'vacation' },
  { label: t('employees.status_type_sick_leave'), value: 'sick_leave' },
  { label: t('employees.status_type_absence'), value: 'absence' },
  { label: t('employees.status_type_permission'), value: 'permission' },
])

const filteredStatusPeriods = computed(() => {
  return props.statusPeriods.filter((period) => {
    if (selectedType.value === ALL_STATUS_TYPES) {
      return true
    }

    return period.type === selectedType.value
  })
})

const groupedPeriods = computed(() => {
  const groups = filteredStatusPeriods.value.reduce<Record<string, EmployeeStatusPeriod[]>>((carry, period) => {
    const year = period.start_at_input?.split('-')[0] ?? period.start_at_formatted?.split('/')[2] ?? t('employees.without_year')

    carry[year] ??= []
    carry[year].push(period)

    return carry
  }, {})

  return Object.entries(groups)
    .sort(([firstYear], [secondYear]) => secondYear.localeCompare(firstYear))
    .map(([year, periods]) => ({
      year,
      periods,
    }))
})

const yearsCount = computed(() => groupedPeriods.value.length)
const visibleCount = computed(() => filteredStatusPeriods.value.length)

watch(
  groupedPeriods,
  (groups) => {
    const availableYears = groups.map((group) => group.year)
    const keptYears = expandedYears.value.filter((year) => availableYears.includes(year))

    expandedYears.value = keptYears
  },
  { immediate: true },
)

function openCreateModal() {
  periodToEdit.value = null
  form.defaults({
    type: 'vacation',
    start_at: '',
    end_at: '',
    notes: '',
  })
  form.reset()
  form.clearErrors()
  modalOpen.value = true
}

function openEditModal(period: EmployeeStatusPeriod) {
  periodToEdit.value = period
  form.defaults({
    type: period.type,
    start_at: period.start_at_input,
    end_at: period.end_at_input,
    notes: period.notes ?? '',
  })
  form.reset()
  form.clearErrors()
  modalOpen.value = true
}

function closeModal() {
  modalOpen.value = false
  periodToEdit.value = null
  form.reset()
  form.clearErrors()
}

function submit() {
  if (periodToEdit.value) {
    form.put(route('employees.status-periods.update', {
      employee: props.employeeId,
      statusPeriod: periodToEdit.value.id,
    }), {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    })

    return
  }

  form.post(route('employees.status-periods.store', { employee: props.employeeId }), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
  })
}

function helperText() {
  if (props.activeStatusPeriod) {
    return t('employees.status_active_until', { date: props.activeStatusPeriod.end_at_formatted })
  }

  if (props.nextStatusPeriod) {
    return t('employees.status_next_change', {
      label: props.nextStatusPeriod.label,
      date: props.nextStatusPeriod.start_at_formatted,
    })
  }

  return t('employees.status_no_upcoming_changes')
}

function resetFilters() {
  selectedType.value = ALL_STATUS_TYPES
}

function isYearExpanded(year: string) {
  return expandedYears.value.includes(year)
}

function toggleYear(year: string) {
  if (isYearExpanded(year)) {
    expandedYears.value = expandedYears.value.filter((value) => value !== year)

    return
  }

  expandedYears.value = [...expandedYears.value, year]
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <CalendarRange class="h-5 w-5 text-blue-600" />
            <span class="text-xl font-bold leading-7 text-gray-800">{{ t('employees.status_periods') }}</span>
          </div>
          <p class="mt-2 text-sm font-normal text-blue-gray-600">
            {{ t('employees.status_periods_help') }}
          </p>
        </div>

        <Button type="button" class="w-full xl:w-auto" @click="openCreateModal">
          <Plus class="mr-2 h-4 w-4 text-white" />
          {{ t('employees.add_status_period') }}
        </Button>
      </CardTitle>
    </CardHeader>

    <CardContent class="space-y-6">
      <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4">
        <div class="flex flex-wrap items-center gap-3">
          <span class="text-sm font-medium text-gray-600">{{ t('employees.current_status') }}</span>
          <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold ring-1" :class="statusClasses[props.currentStatus.code] ?? 'bg-gray-50 text-gray-700 ring-gray-200'">
            <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
            {{ props.currentStatus.label }}
          </span>
        </div>
        <p class="mt-3 text-sm text-blue-gray-600">
          {{ helperText() }}
        </p>
      </div>

      <template v-if="statusPeriods.length">
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm lg:flex-row lg:items-end lg:justify-between">
          <div class="grid gap-4 lg:flex-1 lg:grid-cols-[minmax(0,18rem)_1fr]">
            <Select
              variant="filter"
              v-model="selectedType"
              :label="t('employees.filter_by_type')"
              :options="typeOptions"
            />

            <div class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-blue-gray-700">
              <p class="font-medium text-gray-800">{{ t('employees.history_summary_title') }}</p>
              <p class="mt-1">
                {{ t('employees.history_summary', { visible: visibleCount, total: statusPeriods.length, years: yearsCount }) }}
              </p>
            </div>
          </div>

          <Button
            v-if="selectedType"
            variant="outline"
            type="button"
            class="w-full lg:w-auto"
            @click="resetFilters()"
          >
            <X class="mr-2 h-4 w-4" />
            {{ t('employees.clear_filters') }}
          </Button>
        </div>

        <div v-if="groupedPeriods.length" class="space-y-4">
          <section
            v-for="group in groupedPeriods"
            :key="group.year"
            class="rounded-2xl border border-gray-200 bg-white shadow-sm"
          >
            <button
              type="button"
              class="flex w-full items-center justify-between gap-4 px-4 py-4 text-left sm:px-5"
              @click="toggleYear(group.year)"
            >
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                  <h3 class="text-base font-semibold text-gray-900">{{ group.year }}</h3>
                  <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-200">
                    {{ t('employees.records_count', { count: group.periods.length }) }}
                  </span>
                </div>
                <p class="mt-1 text-sm text-blue-gray-600">
                  {{ isYearExpanded(group.year) ? t('employees.collapse_year_hint') : t('employees.expand_year_hint') }}
                </p>
              </div>

              <ChevronDown
                class="h-5 w-5 shrink-0 text-gray-500 transition-transform duration-200"
                :class="isYearExpanded(group.year) ? 'rotate-180' : ''"
              />
            </button>

            <div v-if="isYearExpanded(group.year)" class="border-t border-gray-100 px-4 py-4 sm:px-5">
              <div class="grid gap-4 xl:grid-cols-2">
                <article
                  v-for="period in group.periods"
                  :key="period.id"
                  class="rounded-2xl border border-gray-200 bg-white p-4"
                >
                  <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 space-y-3">
                      <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="statusClasses[period.type] ?? 'bg-gray-50 text-gray-700 ring-gray-200'">
                          {{ period.label }}
                        </span>
                        <span class="text-xs text-gray-500">{{ t('employees.last_updated') }}: {{ period.updated_at_formatted }}</span>
                      </div>

                      <dl class="grid gap-3 text-sm text-blue-gray-700 sm:grid-cols-2">
                        <div>
                          <dt class="font-medium text-gray-600">{{ t('employees.start_at') }}</dt>
                          <dd class="mt-1 text-gray-900">{{ period.start_at_formatted }}</dd>
                        </div>
                        <div>
                          <dt class="font-medium text-gray-600">{{ t('employees.end_at') }}</dt>
                          <dd class="mt-1 text-gray-900">{{ period.end_at_formatted }}</dd>
                        </div>
                      </dl>

                      <div>
                        <p class="text-sm font-medium text-gray-600">{{ t('employees.observations') }}</p>
                        <p class="mt-1 text-sm text-blue-gray-700">
                          {{ period.notes || t('employees.status_period_no_notes') }}
                        </p>
                      </div>

                      <p v-if="period.updated_by?.name" class="text-xs text-gray-500">
                        {{ t('employees.updated_by') }}: {{ period.updated_by.name }}
                      </p>
                    </div>

                    <div class="flex shrink-0 flex-row gap-2 sm:flex-col">
                      <Button variant="outline" size="sm" type="button" @click="openEditModal(period)">
                        <Pencil class="mr-1 h-3.5 w-3.5" />
                        {{ t('layout.edit') }}
                      </Button>
                      <Button variant="destructive" size="sm" type="button" @click="periodToDelete = period">
                        <Trash2 class="mr-1 h-3.5 w-3.5 text-white" />
                        {{ t('layout.delete') }}
                      </Button>
                    </div>
                  </div>
                </article>
              </div>
            </div>
          </section>
        </div>

        <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center text-sm text-gray-500">
          {{ t('employees.no_status_periods_for_filters') }}
        </div>
      </template>

      <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-10 text-center text-sm text-gray-500">
        {{ t('employees.no_status_periods') }}
      </div>
    </CardContent>
  </Card>

  <Dialog v-model:open="modalOpen" @update:open="value => { if (!value) closeModal() }">
    <DialogContent class="sm:max-w-2xl">
      <DialogHeader>
        <DialogTitle>
          {{ periodToEdit ? t('employees.edit_status_period') : t('employees.add_status_period') }}
        </DialogTitle>
      </DialogHeader>

      <DialogDescription>
        {{ t('employees.status_period_form_help') }}
      </DialogDescription>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid gap-4 md:grid-cols-2">
          <Select
            v-model="form.type"
            name="type"
            :label="t('employees.period_type')"
            :options="typeOptions"
            :error="form.errors.type"
            :required="true"
          />

          <div></div>

          <InputText
            v-model="form.start_at"
            name="start_at"
            type="datetime-local"
            step="60"
            :label="t('employees.start_at')"
            :error="form.errors.start_at"
            :required="true"
          />

          <InputText
            v-model="form.end_at"
            name="end_at"
            type="datetime-local"
            step="60"
            :label="t('employees.end_at')"
            :error="form.errors.end_at"
            :required="true"
          />
        </div>

        <InputTextarea
          v-model="form.notes"
          name="notes"
          :label="t('employees.observations')"
          :placeholder="t('employees.status_period_notes_placeholder')"
          :error="form.errors.notes"
        />

        <DialogFooter class="gap-3">
          <Button variant="outline" type="button" @click="closeModal()">
            {{ t('layout.cancel') }}
          </Button>
          <Button type="submit" :disabled="form.processing">
            <Save class="mr-1 h-4 w-4 text-white" />
            {{ periodToEdit ? t('layout.save_changes') : t('layout.save') }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>

  <DeleteModal
    v-if="periodToDelete"
    :show="true"
    :action="route('employees.status-periods.destroy', { employee: props.employeeId, statusPeriod: periodToDelete.id })"
    @close="periodToDelete = null"
    @deleted="periodToDelete = null"
  />
</template>
