<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutTabs } from '@/layouts';
import { Link, router } from '@inertiajs/vue3'
import { DataTable } from '@/components/blocks'
import { ButtonDelete, Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card'
import { Select } from '@/components/ui/input'
import { Pencil, Plus, Trash2 } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import type { Paginated, Column, SortKey, SortDir, BreadcrumbItem, TabItem, Client, Contract, Status } from '@/types'

interface Props {
  client: Client
  contracts: Paginated<Contract>
  statuses: Status[]
  sort?: SortKey
  dir?: SortDir
  filters?: {
    filter_status?: string
  }
}

const props = defineProps<Props>()

const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('clients.show'), href: '/clients/view' },
]

const tabItems: TabItem[] = [
  { title: t('clients.personal_data'), href: route('clients.show', props.client.id) },
  { title: t('clients.invoices'), href: route('clients.invoices', props.client.id) },
  { title: t('clients.contracts'), href: route('clients.contracts', props.client.id) },
  { title: t('clients.budgets'), href: route('clients.budgets', props.client.id) },
  { title: t('clients.notes'), href: route('clients.notes', props.client.id) }
]

const columns: Column<Contract>[] = [
  { key: 'title', label: t('clients.title'), sortable: true },
  { key: 'status_name', label: t('clients.status'), sortable: true },
  { key: 'date_start', label: t('clients.start_date'), sortable: true },
  { key: 'date_end', label: t('clients.end_date'), sortable: true },
]

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('clients.contracts', props.client.id),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}

const statusFilter = ref<string>(props.filters?.filter_status ?? '')

watchDebounced(
  statusFilter,
  (newStatus) => {
    router.get(
      route('clients.contracts', props.client.id),
      {
        filter_status: newStatus,
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
  <AppLayout>
    <LayoutTabs :tabs="tabItems" :breadcrumbs="breadcrumbItems" backTo="clients.index">

      <Card>
        <CardContent class="md:flex md:items-center md:justify-between">
          <div class="min-w-0 flex-1">
            <h2 class="text-2xl leading-8 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">{{ props.client.name }}</h2>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4 gap-x-6">
            <ButtonDelete :action="route('clients.destroy', props.client.id)">
              <Trash2 class="text-white"/>
              {{ $t('layout.delete') }}
            </ButtonDelete>
            <Link :href="route('clients.edit', props.client.id)">
                <Button>
                <Pencil class="text-white"/>
                {{ $t('layout.edit') }}
              </Button>
            </Link>
          </div>
        </CardContent>
      </Card>

      <DataTable
        :items="props.contracts"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        :actions="['view', 'edit', 'delete']"
        resource="contracts"
      >
        <template #headerActions>
          <Link :href="route('contracts.create')"><Button as="span"><Plus class="size-4" />{{ t('clients.create_contract') }}</Button></Link>
        </template>
        <template #headerFilters>
          <div class="flex gap-4">
            <Select variant="filter" v-model="statusFilter" :options="props.statuses.map(m => ({ label: m.name, value: m.id }))" />
          </div>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ $t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

    </LayoutTabs>
  </AppLayout>
</template>