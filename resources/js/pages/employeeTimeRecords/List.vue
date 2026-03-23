<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutBasic } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n';
import { Clock3 } from 'lucide-vue-next'
import { Datepicker, InputText } from '@/components/ui/input'
import type { Paginated, Column, SortKey, SortDir, Employee } from '@/types'

interface Props<T = any> {
  data: Paginated<T>
  sort?: SortKey
  dir?: SortDir
  filters: {
    filter_search?: string
    filter_start: string
    filter_end: string
  }
}

const props = defineProps<Props<Employee>>()
const { t } = useI18n()
const columns: Column<Employee>[] = [
  { key: 'user.avatar', label: t('employee_time_records.avatar'), sortable: false, format: 'avatar' },
  { key: 'user.name', label: t('employee_time_records.employee'), sortable: false },
  { key: 'programmed_hours', label: t('employee_time_records.programmed_hours'), sortable: false },
  { key: 'registered_hours', label: t('employee_time_records.registered_hours'), sortable: false },
]

const dateStartFilter = ref<string>(props.filters?.filter_start ?? '');
const dateEndFilter = ref<string>(props.filters?.filter_end ?? '');
const searchFilter = ref<string>(props.filters?.filter_search ?? '');

function isoToDDMMYYYY(iso: string) {
  if (!iso) return null
  const [year, month, day] = iso.split('-')
  return `${day}/${month}/${year}`
}

watchDebounced(
  [searchFilter, dateStartFilter, dateEndFilter],
  ([newSearch, newStart, newEnd]) => {
    router.get(
      route('employee-time-records.index'),
      {
        filter_search: newSearch,
        filter_start: newStart,
        filter_end: newEnd,
        sort: props.sort,
        dir: props.dir
      },
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('employee-time-records.index'),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}

function downloadExcel() {
  window.location.href = route('employee-time-records.index',{
    filter_search: searchFilter.value,
    filter_start: dateStartFilter.value,
    filter_end: dateEndFilter.value,
    sort: props.sort,
    dir: props.dir,
    export: '1'
  })
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="Clock3" :title="t('employee_time_records.title')">

      <DataTable
        :items="props.data"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        @download-excel="downloadExcel"
      >
        <template #headerFilters>
          <div class="flex gap-4">
            <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="t('employee_time_records.search_placeholder')" />
            <Datepicker variant="filter" name="filter_start" type="date" :default-value="isoToDDMMYYYY(dateStartFilter)" @update:modelValue="val => dateStartFilter = val" />
            <Datepicker variant="filter" name="filter_end" type="date" :default-value="isoToDDMMYYYY(dateEndFilter)" @update:modelValue="val => dateEndFilter = val" />
          </div>
        </template>
        <template #rowDetails="{ row }">
          <div v-if="row.grouped_services?.length" class="p-4 bg-gray-50 border-t border-gray-200">
            <table class="min-w-full divide-y text-sm">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.client_name') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.service_name') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.programmed_hours') }}</th>
                  <th class="px-3 py-2 text-left font-medium">{{ t('employee_time_records.registered_hours') }}</th>
                </tr>
              </thead>

              <tbody class="divide-y">
                <tr v-for="item in row.grouped_services" :key="item.id">
                  <td class="px-3 py-2 whitespace-nowrap">
                    {{ item.client_name }}
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap">
                    {{ item.service_name }}
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap">
                    {{ item.programmed_hours }}
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap">
                    {{ item.registered_hours }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="p-4 text-center text-gray-500">
            {{ t('employee_time_records.no_time_records') }}
          </div>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

    </LayoutBasic>
  </AppLayout>
</template>
