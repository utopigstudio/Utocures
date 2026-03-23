<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutBasic } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { InputText, Select } from '@/components/ui/input'
import { useI18n } from 'vue-i18n'
import { Plus, Users } from 'lucide-vue-next'
import type { Paginated, Column, SortKey, SortDir, Client } from '@/types'

interface Props<T = any> {
  data: Paginated<T>
  sort?: SortKey
  dir?: SortDir
  filters?: {
    filter_search?: string
    filter_is_partner?: string
  }
}

const props = defineProps<Props<Client>>()

const { t } = useI18n()

const columns: Column<Client>[] = [
  { key: 'name',  label: t('clients.name'),  sortable: true },
  { key: 'cif_nif', label: t('clients.cif_nif'), sortable: true },
  { key: 'email', label: t('clients.email'), sortable: true },
  { key: 'phone', label: t('clients.phone'), sortable: true },
  { key: 'is_partner', label: t('clients.is_partner'), sortable: true, format: 'boolean' },
  { key: 'address', label: t('clients.address'), sortable: true }
]

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('clients.index'),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}

const searchFilter = ref<string>(props.filters?.filter_search ?? '')
const isPartnerFilter = ref<string>(props.filters?.filter_is_partner ?? '')

watchDebounced(
  [searchFilter, isPartnerFilter],
  ([newSearch, newIsPartner]) => {
    if (newSearch && newSearch.length < 3) newSearch = ''

    router.get(
      route('clients.index'),
      {
        filter_search: newSearch,
        filter_is_partner: newIsPartner,
        sort: props.sort,
        dir: props.dir
      },
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)

function downloadExcel() {
  window.location.href = route('clients.index',{
    filter_search: searchFilter.value,
    filter_is_partner: isPartnerFilter.value,
    sort: props.sort,
    dir: props.dir,
    export: '1'
  })
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="Users" :title="t('clients.title')">

      <DataTable
        :items="props.data"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        :actions="['view', 'edit', 'delete']"
        resource="clients"
        @download-excel="downloadExcel"
      >
        <template #headerActions>
          <Link :href="route('clients.create')"> <Button as="span"><Plus class="size-4" />{{ t('clients.create_client') }}</Button></Link>
        </template>
        <template #headerFilters>
          <div class="flex gap-4">
            <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="t('clients.search_placeholder')" />
            <Select variant="filter" v-model="isPartnerFilter" :placeholder="t('clients.status_placeholder')" :options="[{ label: t('generic.yes'), value: '1' }, { label: t('generic.no'), value: '0' }]" />
          </div>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

    </LayoutBasic>
  </AppLayout>
</template>
