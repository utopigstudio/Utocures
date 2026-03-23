<script setup lang="ts">
import { AppLayout, LayoutTabs } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { router, Link } from '@inertiajs/vue3'
import { Card, CardContent } from '@/components/ui/card'
import { ButtonDelete, Button } from '@/components/ui/button';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import type { BreadcrumbItem, TabItem, Paginated, Column, SortKey, SortDir, Client, Invoice } from '@/types'

interface Props {
  client: Client
  invoices: Paginated<Invoice>
  sort?: SortKey
  dir?: SortDir
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

const columns: Column<Invoice>[] = [
  { key: 'client.name', label: t('clients.client') },
  { key: 'created_at', label: t('clients.created_at'), sortable: true },
  { key: 'subtotal', label: t('clients.subtotal'), sortable: true, format: 'currency' },
  { key: 'discount', label: t('clients.discount'), sortable: true, format: 'currency' },
  { key: 'total', label: t('clients.total'), sortable: true, format: 'currency' }
]

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('clients.invoices', props.client.id),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}
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
        :items="props.invoices"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        :actions="['edit', 'delete']"
        resource="invoices"
      >
        <template #headerActions>
          <Link :href="route('invoices.create', { client_id: props.client.id })"><Button as="span"><Plus class="size-4" />{{ t('clients.create_invoice') }}</Button></Link>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

    </LayoutTabs>
  </AppLayout>
</template>