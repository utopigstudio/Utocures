<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutBasic } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { InputText } from '@/components/ui/input'
import { useI18n } from 'vue-i18n'
import { Plus } from 'lucide-vue-next'
import type { Paginated, Column, SortKey, SortDir, Employee } from '@/types'

interface Props<T = any> {
  data: Paginated<T>
  sort?: SortKey
  dir?: SortDir
  filters?: {
    filter_search?: string
  }
}

const props = defineProps<Props<Employee>>()

const { t } = useI18n()

const columns: Column<Employee>[] = [  
  { key: 'user.avatar', label: t('users.avatar'), sortable: false, format: 'avatar' },
  { key: 'nif', label: t('employees.cif_nif'), sortable: true },
  { key: 'user.name', label: t('employees.name'), sortable: true },
  { key: 'user.email', label: t('employees.email'), sortable: true },
  { key: 'phone', label: t('employees.phone'), sortable: true },
  { key: 'user.is_active', label: t('employees.is_active'), sortable: true, format: 'boolean' },
]

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('employees.index'),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}

const searchFilter = ref<string>(props.filters?.filter_search ?? '')

watchDebounced(
  searchFilter,
  (newSearch) => {
    if (newSearch && newSearch.length < 3) newSearch = ''

    router.get(
      route('employees.index'),
      {
        filter_search: newSearch,
        sort: props.sort,
        dir: props.dir
      },
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)

function downloadExcel() {
  window.location.href = route('employees.index',{
    filter_search: searchFilter.value,
    sort: props.sort,
    dir: props.dir,
    export: '1'
  })
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :title="t('employees.title')">

      <DataTable
        :items="props.data"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        :actions="['view', 'edit', 'delete']"
        resource="employees"
        @download-excel="downloadExcel"
      >
        <template #headerActions>
          <Link :href="route('employees.create')"><Button as="span"><Plus class="size-4" />{{ t('employees.create_employee') }}</Button></Link>
        </template>
        <template #headerFilters>
          <div class="flex gap-4">
            <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="t('employees.search_placeholder')" />
          </div>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ $t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

    </LayoutBasic>
  </AppLayout>
</template>
