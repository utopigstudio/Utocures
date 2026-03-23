<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutBasic } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { InputText, Select } from '@/components/ui/input'
import { Plus } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n';

import type { Paginated, Column, SortKey, SortDir, User } from '@/types'

interface Props<T = any> {
  data: Paginated<T>
  sort?: SortKey
  dir?: SortDir
  filters?: {
    filter_search?: string
    filter_is_active?: string
  }
}

const props = defineProps<Props<User>>()

const { t } = useI18n();

const columns: Column<User>[] = [
  { key: 'avatar', label: t('users.avatar'), sortable: false, format: 'avatar' },
  { key: 'name', label: t('users.name'), sortable: true },
  { key: 'email', label: t('users.email'), sortable: true },
  { key: 'is_active', label: t('users.is_active'), sortable: true, format: 'boolean' },
]

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('users.index'),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}

const searchFilter = ref<string>(props.filters?.filter_search ?? '')
const activeFilter = ref<string>(props.filters?.filter_is_active ?? '')

watchDebounced(
  [searchFilter, activeFilter],
  ([newSearch, newActive]) => {
    if (newSearch && newSearch.length < 3) newSearch = ''

    router.get(
      route('users.index'),
      {
        filter_search: newSearch,
        filter_is_active: newActive
      },
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)
</script>

<template>
  <AppLayout>
    <LayoutBasic :title="t('users.title')">

      <DataTable
        :items="props.data"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        :actions="['edit']"
        resource="users"
      >
        <template #headerActions>
          <Link :href="route('users.create')"><Button as="span"><Plus class="size-4" />{{ t('users.create') }}</Button></Link>
        </template>
        <template #headerFilters>
          <div class="flex gap-4">
            <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="t('users.search_placeholder')" />
            <Select variant="filter" :placeholder="t('users.status_placeholder')" v-model="activeFilter" :options="[{ label: t('users.active'), value: '1' }, { label: t('users.inactive'), value: '0' }]" />
          </div>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ $t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

    </LayoutBasic>
  </AppLayout>
</template>
