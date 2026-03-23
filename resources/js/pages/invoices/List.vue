<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutBasic } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { InputText, Select } from '@/components/ui/input'
import { useI18n } from 'vue-i18n';
import { Plus, FileMinus } from 'lucide-vue-next'
import type { Paginated, Column, SortKey, SortDir, Invoice } from '@/types'

interface Props<T = any> {
  data: Paginated<T>
  sort?: SortKey
  dir?: SortDir
  filters?: {
    filter_search?: string
    filter_date?: string
    filter_year?: string
  }
}

const props = defineProps<Props<Invoice>>()

const { t } = useI18n()

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 5 }, (_, i) => (currentYear - i).toString())
const columns: Column<Invoice>[] = [
  { key: 'client.name', label: t('invoices.client'), sortable: true, link: (row) => !row.client.deleted_at ? route('clients.show', row.client.id) : undefined },
  { key: 'date', label: t('invoices.date'), sortable: true },
  { key: 'subtotal', label: t('invoices.subtotal'), sortable: true, format: 'currency' },
  { key: 'discount', label: t('invoices.discount'), sortable: true, format: 'currency' },
  { key: 'total', label: t('invoices.total'), sortable: true, format: 'currency' }
]

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('invoices.index'),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}

const searchFilter = ref<string>(props.filters?.filter_search ?? '')
const yearFilter = ref<string>(props.filters?.filter_year ?? currentYear.toString())

watchDebounced(
  [searchFilter, yearFilter],
  ([newSearch, newYear]) => {
    if (newSearch && newSearch.length < 3) newSearch = ''

    router.get(
      route('invoices.index'),
      {
        filter_search: newSearch,
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
  window.location.href = route('invoices.index',{
    filter_search: searchFilter.value,
    filter_year: yearFilter.value,
    sort: props.sort,
    dir: props.dir,
    export: '1'
  })
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="FileMinus" :title="t('invoices.title')">
      <DataTable
        :items="props.data"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        :actions="['edit', 'delete']"
        resource="invoices"
        @download-excel="downloadExcel"
      >
        <template #headerActions>
          <Link :href="route('invoices.create')"><Button as="span"><Plus class="size-4" />{{ t('invoices.create') }}</Button></Link>
        </template>
        <template #headerFilters>
          <div class="flex gap-4">
            <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="t('invoices.search_placeholder')" />
            <Select variant="filter" v-model="yearFilter" :options="years.map(year => ({ label: year, value: year }))" />
          </div>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

    </LayoutBasic>
  </AppLayout>
</template>
