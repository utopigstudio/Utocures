<script setup lang="ts">
import { computed, ref } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { router } from '@inertiajs/vue3'
import { AppLayout, LayoutBasic } from '@/layouts'
import { DataTable } from '@/components/blocks'
import { Button } from '@/components/ui/button'
import { Datepicker, InputText } from '@/components/ui/input'
import { Clock3 } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import CalendarView from './components/CalendarView.vue'
import type { Paginated, Column, SortDir, SortKey } from '@/types'

type GroupBy = 'employee' | 'client'
type PageView = 'list' | 'calendar'

interface EmployeeOption {
  label: string
  value: string
}

interface IncidentNote {
  id: string
  content: string
  created_at: string
  employee_name?: string
  record_date?: string
  record_time_in?: string
  record_time_out?: string | null
}

interface DetailRow {
  detail_key: string
  client_name?: string
  employee_name?: string
  employee_avatar?: string
  service_name: string
  programmed_hours: string
  registered_hours: string
  incident_notes: IncidentNote[]
}

interface ListRow {
  id: string
  name?: string
  user?: {
    name?: string
    avatar?: string
  }
  programmed_hours: string
  registered_hours: string
  grouped_details?: DetailRow[]
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
}

interface CalendarPayload {
  selected_employee_id?: string | null
  selected_employee?: {
    id: string
    name?: string | null
    avatar?: string | null
  } | null
  selected_date: string
  records: CalendarRecord[]
}

interface Props {
  view?: PageView
  data?: Paginated<ListRow> | null
  sort?: SortKey
  dir?: SortDir
  filters: {
    view?: PageView
    filter_search?: string
    filter_start?: string
    filter_end?: string
    group_by?: GroupBy
    employee_id?: string
    filter_date?: string
  }
  employee_options?: EmployeeOption[]
  calendar?: CalendarPayload | null
}

const props = defineProps<Props>()
const { t } = useI18n()

const currentView = computed<PageView>(() => props.view ?? 'list')
const dateStartFilter = ref<string>(props.filters?.filter_start ?? '')
const dateEndFilter = ref<string>(props.filters?.filter_end ?? '')
const searchFilter = ref<string>(props.filters?.filter_search ?? '')
const groupBy = computed<GroupBy>(() => props.filters?.group_by ?? 'employee')

const columns = computed<Column<ListRow>[]>(() => {
  if (groupBy.value === 'client') {
    return [
      { key: 'name', label: t('employee_time_records.client'), sortable: false },
      { key: 'programmed_hours', label: t('employee_time_records.programmed_hours'), sortable: false },
      { key: 'registered_hours', label: t('employee_time_records.registered_hours'), sortable: false },
    ]
  }

  return [
    { key: 'user.avatar', label: t('employee_time_records.avatar'), sortable: false, format: 'avatar' },
    { key: 'user.name', label: t('employee_time_records.employee'), sortable: false },
    { key: 'programmed_hours', label: t('employee_time_records.programmed_hours'), sortable: false },
    { key: 'registered_hours', label: t('employee_time_records.registered_hours'), sortable: false },
  ]
})

const searchPlaceholder = computed(() => groupBy.value === 'client'
  ? t('employee_time_records.search_placeholder_client')
  : t('employee_time_records.search_placeholder_employee'))

const emptyPaginated: Paginated<ListRow> = {
  data: [],
  links: [],
  current_page: 1,
  last_page: 1,
  per_page: 0,
  total: 0,
}

function buildListParams(overrides: Partial<Record<string, string>>) {
  const params: Record<string, string> = {
    view: 'list',
    filter_search: searchFilter.value,
    filter_start: dateStartFilter.value,
    filter_end: dateEndFilter.value,
    group_by: groupBy.value,
  }

  if (props.sort) {
    params.sort = props.sort
  }

  if (props.dir) {
    params.dir = props.dir
  }

  return {
    ...params,
    ...overrides,
  }
}

function isoToDDMMYYYY(iso?: string) {
  if (!iso) return null
  const [year, month, day] = iso.split('-')
  return `${day}/${month}/${year}`
}

function formatDateTime(dateStr: string) {
  const date = new Date(dateStr)

  return `${date.toLocaleDateString('es-ES')} · ${date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}`
}

watchDebounced(
  [searchFilter, dateStartFilter, dateEndFilter],
  ([, newStart, newEnd]) => {
    if (currentView.value !== 'list') return

    router.get(
      route('employee-time-records.index'),
      buildListParams({
        filter_start: newStart,
        filter_end: newEnd,
      }),
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('employee-time-records.index'),
    buildListParams({ sort: key, dir: dir ?? 'asc' }),
    { preserveState: true, replace: true }
  )
}

function changeGroupBy(value: GroupBy) {
  if (value === groupBy.value) return

  router.get(
    route('employee-time-records.index'),
    buildListParams({ group_by: value }),
    { preserveState: true, replace: true }
  )
}

function downloadExcel() {
  window.location.href = route('employee-time-records.index', {
    ...buildListParams({}),
    export: '1',
  })
}

function changeView(view: PageView) {
  if (view === currentView.value) return

  if (view === 'calendar') {
    router.get(
      route('employee-time-records.index'),
      {
        view: 'calendar',
        employee_id: props.calendar?.selected_employee_id ?? props.employee_options?.[0]?.value,
        filter_date: props.calendar?.selected_date ?? props.filters?.filter_date ?? new Date().toISOString().split('T')[0],
      },
      { preserveState: true, replace: true }
    )

    return
  }

  router.get(
    route('employee-time-records.index'),
    buildListParams({}),
    { preserveState: true, replace: true }
  )
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="Clock3" :title="t('employee_time_records.title')">
      <div class="mb-4 grid w-full grid-cols-2 rounded-xl border border-gray-200 bg-white p-1 shadow-sm sm:inline-flex sm:w-auto">
        <Button type="button" class="rounded-lg" :variant="currentView === 'list' ? 'default' : 'ghost'" @click="changeView('list')">
          {{ t('employee_time_records.list_view') }}
        </Button>
        <Button type="button" class="rounded-lg" :variant="currentView === 'calendar' ? 'default' : 'ghost'" @click="changeView('calendar')">
          {{ t('employee_time_records.calendar_view') }}
        </Button>
      </div>

      <CalendarView
        v-if="currentView === 'calendar'"
        :employee-options="props.employee_options ?? []"
        :selected-employee-id="props.calendar?.selected_employee_id"
        :selected-employee="props.calendar?.selected_employee"
        :selected-date="props.calendar?.selected_date ?? props.filters?.filter_date ?? new Date().toISOString().split('T')[0]"
        :records="props.calendar?.records ?? []"
      />

      <DataTable
        v-else
        :items="props.data ?? emptyPaginated"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        @download-excel="downloadExcel"
      >
        <template #headerFilters>
          <div class="flex gap-4">
            <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="searchPlaceholder" />
            <Datepicker variant="filter" name="filter_start" :default-value="isoToDDMMYYYY(dateStartFilter)" @update:modelValue="val => dateStartFilter = val" />
            <Datepicker variant="filter" name="filter_end" :default-value="isoToDDMMYYYY(dateEndFilter)" @update:modelValue="val => dateEndFilter = val" />
          </div>
        </template>

        <template #headerActions>
          <div class="flex flex-wrap items-center justify-end gap-2">
            <span class="text-sm text-muted-foreground">{{ t('employee_time_records.view_by') }}</span>
            <Button size="sm" :variant="groupBy === 'employee' ? 'default' : 'outline'" @click="changeGroupBy('employee')">
              {{ t('employee_time_records.group_by_employee') }}
            </Button>
            <Button size="sm" :variant="groupBy === 'client' ? 'default' : 'outline'" @click="changeGroupBy('client')">
              {{ t('employee_time_records.group_by_client') }}
            </Button>
          </div>
        </template>

        <template #rowDetails="{ row }">
          <div v-if="row.grouped_details?.length" class="p-4 bg-gray-50 border-t border-gray-200">
            <table class="min-w-full divide-y text-sm">
              <thead class="bg-gray-100">
                <tr v-if="groupBy === 'employee'">
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.client_name') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.service_name') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.programmed_hours') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.registered_hours') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.incidences') }}</th>
                </tr>
                <tr v-else>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.employee') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.service_name') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.programmed_hours') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.registered_hours') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.incidences') }}</th>
                </tr>
              </thead>

              <tbody class="divide-y">
                <tr v-for="item in row.grouped_details" :key="item.detail_key">
                  <template v-if="groupBy === 'employee'">
                    <td class="px-3 py-2 whitespace-nowrap">{{ item.client_name }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ item.service_name }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ item.programmed_hours }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ item.registered_hours }}</td>
                    <td class="px-3 py-2 align-top">
                      <div v-if="item.incident_notes.length" class="space-y-2">
                        <ul class="space-y-2">
                          <li v-for="note in item.incident_notes" :key="note.id" class="rounded-lg bg-white px-3 py-2 text-xs text-blue-gray-600 shadow-sm">
                            <p class="font-medium text-blue-gray-800">{{ note.content }}</p>
                            <p class="mt-1">{{ formatDateTime(note.created_at) }}</p>
                            <p v-if="note.record_date" class="mt-1">{{ note.record_date }} · {{ note.record_time_in }}<span v-if="note.record_time_out"> - {{ note.record_time_out }}</span></p>
                          </li>
                        </ul>
                      </div>
                      <span v-else class="text-xs text-gray-400">{{ t('employee_time_records.no_incidences') }}</span>
                    </td>
                  </template>

                  <template v-else>
                    <td class="px-3 py-2 whitespace-nowrap">
                      <div class="flex items-center gap-3">
                        <img
                          v-if="item.employee_avatar"
                          :src="item.employee_avatar"
                          :alt="item.employee_name"
                          class="h-8 w-8 rounded-full object-cover"
                        >
                        <div v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-gray-100 text-xs font-semibold text-blue-gray-600">
                          {{ item.employee_name?.slice(0, 2)?.toUpperCase() }}
                        </div>
                        <span>{{ item.employee_name }}</span>
                      </div>
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ item.service_name }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ item.programmed_hours }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ item.registered_hours }}</td>
                    <td class="px-3 py-2 align-top">
                      <div v-if="item.incident_notes.length" class="space-y-2">
                        <ul class="space-y-2">
                          <li v-for="note in item.incident_notes" :key="note.id" class="rounded-lg bg-white px-3 py-2 text-xs text-blue-gray-600 shadow-sm">
                            <p class="font-medium text-blue-gray-800">{{ note.content }}</p>
                            <p class="mt-1">{{ note.employee_name }}</p>
                            <p class="mt-1">{{ formatDateTime(note.created_at) }}</p>
                            <p v-if="note.record_date" class="mt-1">{{ note.record_date }} · {{ note.record_time_in }}<span v-if="note.record_time_out"> - {{ note.record_time_out }}</span></p>
                          </li>
                        </ul>
                      </div>
                      <span v-else class="text-xs text-gray-400">{{ t('employee_time_records.no_incidences') }}</span>
                    </td>
                  </template>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="p-4 text-center text-gray-500">
            {{ t('employee_time_records.no_time_records') }}
          </div>
        </template>

        <template #empty>
          <div class="text-center text-gray-500">{{ $t('layout.no_results_found') }}</div>
        </template>
      </DataTable>
    </LayoutBasic>
  </AppLayout>
</template>
