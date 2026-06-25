<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutBasic } from '@/layouts';
import { DataTable } from '@/components/blocks'
import { router, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { InputText } from '@/components/ui/input'
import { Megaphone, Plus } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n';
import type { Paginated, Column, SortKey, SortDir, Announcement } from '@/types'

interface Props<T = any> {
  data: Paginated<T>
  sort?: SortKey
  dir?: SortDir
  filters?: {
    filter_search?: string
  }
}

const props = defineProps<Props<Announcement>>()
const { t } = useI18n()

const columns: Column<Announcement>[] = [
  { key: 'image', label: t('announcements.image'), sortable: false, format: 'avatar' },
  { key: 'title', label: t('announcements.title_field'), sortable: true },
  { key: 'created_at', label: t('announcements.created_at'), sortable: true },
]

function onSortChange({ key, dir }: { key: string, dir: SortDir }) {
  router.get(
    route('announcements.index'),
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
      route('announcements.index'),
      {
        filter_search: newSearch,
        sort: props.sort,
        dir: props.dir,
      },
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="Megaphone" :title="t('announcements.title')">
      <DataTable
        :items="props.data"
        :columns="columns"
        :sort-key="props.sort ?? null"
        :sort-dir="props.dir ?? null"
        @sort-change="onSortChange"
        :actions="['edit', 'delete']"
        resource="announcements"
      >
        <template #headerActions>
          <Link :href="route('announcements.create')"><Button as="span"><Plus class="size-4" />{{ t('announcements.create') }}</Button></Link>
        </template>
        <template #headerFilters>
          <div class="flex gap-4">
            <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="t('announcements.search_placeholder')" />
          </div>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ $t('announcements.no_results') }}</div>
        </template>
      </DataTable>
    </LayoutBasic>
  </AppLayout>
</template>