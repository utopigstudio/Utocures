<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { Link, router } from '@inertiajs/vue3';
import { AppLayoutEmployee } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { ChevronLeft, CalendarClock } from 'lucide-vue-next';
import { Datepicker, Select } from '@/components/ui/input'
import { useI18n } from 'vue-i18n';
import type { Column, SortKey, SortDir, Paginated } from '@/types'

interface TimeRecord {
  id: string
  date_in: string
  date_out?: string
  time_in: string
  time_out?: string
  notes?: Array<{
    id: string
    content: string
    created_at: string
    user: {
      name: string
    }
  }>
}

interface Props {
  time_records: Paginated<TimeRecord>
  sort?: SortKey
  dir?: SortDir
  filters?: {
    filter_date?: string
    filter_period?: string
  }
}

const props = defineProps<Props>()

const { t } = useI18n()

const columns: Column<TimeRecord>[] = [
  { key: 'assigned_hour.client.name', label: t('employee_time_records.client_name') },
  { key: 'assigned_hour.service.name', label: t('employee_time_records.service_name') },
  { key: 'date_in', label: t('employee_time_records.date_in') },
  { key: 'time_in', label: t('employee_time_records.time_in') },
  { key: 'date_out', label: t('employee_time_records.date_out') },
  { key: 'time_out', label: t('employee_time_records.time_out') },
  { key: 'notes.length', label: t('employee_time_records.incidences'), sortable: false }
]

function formatDateTime(dateStr: string) {
  const date = new Date(dateStr)

  return `${date.toLocaleDateString('es-ES')} · ${date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}`
}

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('employee.hours-worked'),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}

const searchFilter = ref<string>(props.filters?.filter_date ?? '')
const periodFilter = ref<string>(props.filters?.filter_period ?? 'monthly')

watchDebounced(
  [searchFilter, periodFilter],
  ([newDate, newPeriod]) => {
    router.get(
      route('employee.hours-worked'),
      {
        filter_date: newDate,
        filter_period: newPeriod,
        sort: props.sort,
        dir: props.dir
      },
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)
</script>

<template>
  <AppLayoutEmployee>
    <div class="container mx-auto flex items-center gap-2 px-4 pt-0 pb-4 bg-gray-50">
      <Link :href="route('dashboard')" class="flex items-center justify-center text-gray-600 hover:text-gray-900 transition-colors">
        <ChevronLeft class="size-6" />
        <CalendarClock class="size-6 text-blue-600" />
        <h1 class="text-xl leading-7 font-bold text-gray-800">{{ t('employees.hours_worked') }}</h1>
      </Link>
    </div>

    <div class="container mx-auto">
      <DataTable
        :items="props.time_records"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        resource="employee.hours-worked"
        cardClass="shadow-none rounded-none px-0 pt-0 bg-transparent border-0"
        :striped="true"
      >
        <template #headerFilters>
          <div class="flex gap-4 w-full px-4">
            <div class="flex-1 min-w-0">
              <Datepicker variant="filter" :default-value="searchFilter" :placeholder="t('employee_time_records.date')" class="w-full"/>
            </div>
            <div class="flex-1 min-w-0">
              <Select class="w-full" variant="filter" v-model="periodFilter" :options="[
                { label: 'Mensual', value: 'monthly' },
                { label: 'Anual', value: 'yearly' }
              ]" />
            </div>
          </div>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ $t('layout.no_results_found') }}</div>
        </template>
        <template #rowDetails="{ row }">
          <div class="p-4 bg-gray-50 border-t border-gray-200">
            <template v-if="row.notes?.length">
              <div class="space-y-3">
                <div
                  v-for="note in row.notes"
                  :key="note.id"
                  class="rounded-lg border border-amber-200 bg-white px-4 py-3"
                >
                  <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                      {{ t('notes.incident') }}
                    </span>
                    <span class="text-xs text-blue-gray-500">{{ formatDateTime(note.created_at) }}</span>
                  </div>
                  <p class="mb-2 text-sm font-medium text-blue-gray-700">{{ note.content }}</p>
                  <p class="text-xs text-blue-gray-500">{{ note.user.name }}</p>
                </div>
              </div>
            </template>
            <p v-else class="text-sm text-gray-500">{{ t('employee_time_records.no_incidences') }}</p>
          </div>
        </template>
      </DataTable>
    </div>
  </AppLayoutEmployee>
</template>
