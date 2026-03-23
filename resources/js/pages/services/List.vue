<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core'
import { AppLayout, LayoutBasic } from '@/layouts';
import { InputText } from '@/components/ui/input'
import { Service } from '@/components/blocks'
import { Button } from '@/components/ui/button'
import { Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { Plus, BadgeCheck } from 'lucide-vue-next'
import type { Service as ServiceType } from '@/types'

interface Props {
  data: ServiceType[]
  filters?: {
    filter_search?: string
  }
}

const props = defineProps<Props>()

const searchFilter = ref<string>(props.filters?.filter_search ?? '')

watchDebounced(
  searchFilter,
  (newSearch) => {
    if (newSearch && newSearch.length < 3) newSearch = ''

    router.get(
      route('services.index'),
      {
        filter_search: newSearch
      },
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="BadgeCheck" :title="$t('services.title')">
    <div class="mb-6 flex flex-col-reverse md:flex-row md:items-center md:justify-between gap-4 px-4 md:px-0">
        <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="$t('layout.search_service')" />
        <Link :href="route('services.create')"><Button as="span"><Plus class="size-4" />{{ $t('services.create') }}</Button></Link>
      </div>
      <div class="col-span-full grid grid-cols-12 gap-6 pt-0 md:pt-7 px-4 md:px-0">
        <template v-if="props.data.length">
          <Service class="col-span-12 md:col-span-6" v-for="service in props.data" :key="service.id" :service="service"></Service>
        </template>
        <div v-else class="col-span-12 text-center text-gray-500">{{ $t('services.no_results') }}</div>
      </div>
    </LayoutBasic>
  </AppLayout>
</template>
