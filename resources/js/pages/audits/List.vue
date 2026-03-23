<script setup lang="ts">
import { AppLayout, LayoutBasic } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n';
import { History } from 'lucide-vue-next'
import type { Paginated, Column, SortKey, SortDir, Audit } from '@/types'

interface Props<T = any> {
  data: Paginated<T>
  sort?: SortKey
  dir?: SortDir
}

const props = defineProps<Props<Audit>>()
const { t } = useI18n()

const columns: Column<Audit>[] = [
  { key: 'user.avatar', label: t('audit.avatar'), sortable: false, format: 'avatar' },
  { key: 'user.name', label: t('audit.user'), sortable: true },
  { key: 'action', label: t('audit.action'), sortable: true },
  { key: 'resource', label: t('audit.resource'), sortable: true },
  { key: 'resource_name', label: t('audit.resource_name'), sortable: false },
  { key: 'created_at_formatted', label: t('audit.created_at'), sortable: true }
]

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('audits.index'),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="History" :title="t('audit.title')">

      <DataTable
        :items="props.data"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        :actions="['view']"
        resource="audits"
      >
        <template #empty>
          <div class="text-center text-gray-500">{{ $t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

    </LayoutBasic>
  </AppLayout>
</template>
