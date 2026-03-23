<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutTabs } from '@/layouts';
import { Card, CardContent } from '@/components/ui/card'
import { router, Link } from '@inertiajs/vue3'
import { ButtonDelete, Button } from '@/components/ui/button';
import Notes from '@/components/blocks/Notes.vue'
import { Trash2, Pencil } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import type { BreadcrumbItem, TabItem, Client, Note } from '@/types'

interface Props {
  client: Client
  notes: Note[]
  filters?: {
    filter_search?: string
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

const searchFilter = ref<string>(props.filters?.filter_search ?? '')

watchDebounced(
  searchFilter,
  (newSearch) => {
    if (newSearch && newSearch.length < 3) newSearch = ''

    router.get(
      route('clients.notes', props.client.id),
      {
        filter_search: newSearch,
      },
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)

function reloadNotes() {
  router.reload({ only: ['notes'] });
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
          <div class="mt-4 flex flex-col sm:flex-row md:mt-0 md:ml-4 gap-3 sm:gap-2">
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

      <Notes :notes="props.notes" resource="client" :filter="props.filters?.filter_search" :parent-id="props.client.id" @search="searchFilter = $event" @saved="reloadNotes" />

    </LayoutTabs>
  </AppLayout>
</template>