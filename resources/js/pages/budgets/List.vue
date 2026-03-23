<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutBasic } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { InputText, Select } from '@/components/ui/input'
import { useI18n } from 'vue-i18n';
import { Download, Mail, Plus, FileMinus } from 'lucide-vue-next'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'
import type { Paginated, Column, SortKey, SortDir, Budget, Status } from '@/types'

interface Props<T = any> {
  data: Paginated<T>
  statuses: Status[]
  sort?: SortKey
  dir?: SortDir
  filters?: {
    filter_search?: string
    filter_status?: string
    filter_year?: string
  }
}

const props = defineProps<Props<Budget>>()

const { t } = useI18n()

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 5 }, (_, i) => (currentYear - i).toString())
const columns: Column<Budget>[] = [
  { key: 'client_name', label: t('budgets.client'), sortable: true, link: (row) => row.client?.id ? route('clients.show', row.client.id) : null },
  { key: 'status_name', label: t('budgets.status'), sortable: true },
  { key: 'created_at', label: t('budgets.created_at'), sortable: true },
  { key: 'subtotal', label: t('budgets.subtotal'), sortable: true, format: 'currency' },
  { key: 'discount', label: t('budgets.discount'), sortable: true, format: 'currency' },
  { key: 'tax', label: t('budgets.tax'), sortable: true, format: 'currency' },
  { key: 'total', label: t('budgets.total'), sortable: true, format: 'currency' }
]

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('budgets.index'),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}

const searchFilter = ref<string>(props.filters?.filter_search ?? '')
const statusFilter = ref<string>(props.filters?.filter_status ?? '')
const yearFilter = ref<string>(props.filters?.filter_year ?? currentYear.toString())

watchDebounced(
  [searchFilter, statusFilter, yearFilter],
  ([newSearch, newStatus, newYear]) => {
    if (newSearch && newSearch.length < 3) newSearch = ''

    router.get(
      route('budgets.index'),
      {
        filter_search: newSearch,
        filter_status: newStatus,
        filter_year: newYear,
        sort: props.sort,
        dir: props.dir
      },
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)

function downloadExcel() {
  window.location.href = route('budgets.index',{
    filter_search: searchFilter.value,
    filter_status: statusFilter.value,
    filter_year: yearFilter.value,
    sort: props.sort,
    dir: props.dir,
    export: '1'
  })
}

function downloadPdf(id: number | string) {
  window.location.href = route('budgets.download-pdf', id)
}

function sendEmail(id: number | string) {
  window.location.href = route('budgets.send-email', id)
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="FileMinus" :title="t('budgets.title')">

      <DataTable
        :items="props.data"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        :actions="['edit', 'delete', 'view']"
        resource="budgets"
        @download-excel="downloadExcel"
      >
        <template #headerActions>
          <Link :href="route('budgets.create')"><Button as="span"><Plus class="size-4" />{{ t('budgets.create_budget') }}</Button></Link>
        </template>
        <template #headerFilters>
          <div class="flex gap-4">
            <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="t('budgets.search_placeholder')" />
            <Select variant="filter" v-model="yearFilter" :options="years.map(year => ({ label: year, value: year }))" />
            <Select variant="filter" v-model="statusFilter" :options="props.statuses.map(status => ({ label: status.name, value: status.id }))" :placeholder="t('budgets.status_placeholder')" />
          </div>
        </template>
        <template #actions="{ row }">
          <DropdownMenuItem as-child class="cursor-pointer" @click="downloadPdf(row.id)">
            <div class="flex items-center">
            <Download class="mr-2 h-4 w-4" />
            {{ t('budgets.download_pdf') }}
            </div>
          </DropdownMenuItem>
          <DropdownMenuItem as-child class="cursor-pointer" @click="sendEmail(row.id)">
            <div class="flex items-center">
            <Mail class="mr-2 h-4 w-4" />
            {{ t('budgets.send_email') }}
            </div>
          </DropdownMenuItem>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ $t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

    </LayoutBasic>
  </AppLayout>
</template>
