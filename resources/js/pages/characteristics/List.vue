<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutBasic } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { InputText } from '@/components/ui/input'
import { Plus, Star } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n';
import type { Paginated, Column, SortKey, SortDir, Characteristic } from '@/types'

interface Props<T = any> {
  data: Paginated<T>
  sort?: SortKey
  dir?: SortDir
  filters?: {
    filter_search?: string
  }
}

const props = defineProps<Props<Characteristic>>()
const { t } = useI18n()
const columns: Column<Characteristic>[] = [
  { key: 'name', label: t('characteristics.name'), sortable: true },
  { key: 'options_names', label: t('characteristics.options'), sortable: true, format: 'pills' },
]

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('characteristics.index'),
    { sort: key, dir },
    { preserveState: true, replace: true }
  )
}

const searchFilter = ref<string>(props.filters?.filter_search ?? '')

watchDebounced(
  [searchFilter],
  ([newSearch]) => {
    if (newSearch && newSearch.length < 3) newSearch = ''

    router.get(
      route('characteristics.index'),
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
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="Star" :title="t('characteristics.title')">

      <DataTable
        :items="props.data"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        :actions="['edit', 'delete']"
        resource="characteristics"
      >
        <template #headerFilters>
          <div class="flex gap-4">
            <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="t('characteristics.search_placeholder')" />
          </div>
        </template>
        <template #headerActions>
          <Link :href="route('characteristics.create')"><Button as="span"><Plus class="size-4" />{{ t('characteristics.create') }}</Button></Link>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

    </LayoutBasic>
  </AppLayout>
</template>
