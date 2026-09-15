<script setup lang="ts">
import { Button, ButtonDelete } from '@/components/ui/button'
import { InputText } from '@/components/ui/input'
import { AppLayout, LayoutBasic } from '@/layouts'
import type { EntityResource } from '@/types'
import { router, Link } from '@inertiajs/vue3'
import { watchDebounced } from '@vueuse/core'
import { Mail, MapPin, MapPinned, Pencil, Phone, Plus, Trash2 } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import ResourceMap from './components/ResourceMap.vue'

interface Props {
  data: EntityResource[]
  types: string[]
  filters?: {
    filter_search?: string
    filter_type?: string
  }
}

const props = defineProps<Props>()
const searchFilter = ref(props.filters?.filter_search ?? '')
const typeFilter = ref(props.filters?.filter_type ?? '')
const selectedId = ref<string | null>(props.data[0]?.id ?? null)

function applyFilters(): void {
  const search = searchFilter.value.length >= 3 ? searchFilter.value : ''

  router.get(
    route('resources.index'),
    { filter_search: search, filter_type: typeFilter.value || undefined },
    { preserveState: true, replace: true },
  )
}

watchDebounced(searchFilter, applyFilters, { debounce: 500, maxWait: 1000 })
watchDebounced(typeFilter, applyFilters, { debounce: 500, maxWait: 1000 })
watch(() => props.data, (resources) => {
  if (!resources.some((resource) => resource.id === selectedId.value)) {
    selectedId.value = resources[0]?.id ?? null
  }
})
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="MapPinned" :title="$t('resources.title')">
      <div class="flex flex-col gap-4 px-4 md:flex-row md:items-center md:justify-between md:px-0">
        <div class="flex w-full flex-col gap-3 sm:flex-row md:w-auto">
          <InputText
            v-model="searchFilter"
            variant="filter"
            type="search"
            :placeholder="$t('resources.search_placeholder')"
            container-class="w-full sm:w-80"
          />
          <InputText
            v-model="typeFilter"
            variant="filter"
            type="search"
            list="resource-type-filters"
            :placeholder="$t('resources.filter_type_placeholder')"
            container-class="w-full sm:w-64"
          />
          <datalist id="resource-type-filters">
            <option v-for="type in props.types" :key="type" :value="type"></option>
          </datalist>
        </div>

        <Link :href="route('resources.create')">
          <Button as="span"><Plus class="size-4" />{{ $t('resources.create') }}</Button>
        </Link>
      </div>

      <div class="mx-4 grid min-h-[640px] overflow-hidden rounded-xl border bg-white shadow-sm md:mx-0 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
        <div class="relative min-h-[430px] border-b lg:min-h-[640px] lg:border-r lg:border-b-0">
          <ResourceMap :resources="props.data" :selected-id="selectedId" @select="selectedId = $event" />
          <div v-if="!props.data.length" class="pointer-events-none absolute inset-0 z-[1100] flex items-center justify-center bg-white/70 p-6">
            <div class="rounded-xl bg-white p-6 text-center shadow-lg">
              <MapPin class="mx-auto mb-3 size-9 text-blue-600" />
              <p class="font-semibold text-gray-800">{{ $t('resources.no_results') }}</p>
              <p class="mt-1 text-sm text-gray-500">{{ $t('resources.no_results_help') }}</p>
            </div>
          </div>
        </div>

        <aside class="max-h-[640px] overflow-y-auto p-4">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">{{ $t('resources.points') }}</h2>
            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ props.data.length }}</span>
          </div>

          <div class="space-y-3">
            <article
              v-for="resource in props.data"
              :key="resource.id"
              class="cursor-pointer rounded-lg border p-4 transition hover:border-blue-300 hover:bg-blue-50/40"
              :class="selectedId === resource.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
              @click="selectedId = resource.id"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <span class="text-xs font-bold tracking-wide text-blue-600 uppercase">{{ resource.type }}</span>
                  <h3 class="truncate font-semibold text-gray-900">{{ resource.name }}</h3>
                </div>
                <div class="flex shrink-0 gap-1" @click.stop>
                  <Link :href="route('resources.edit', resource.id)">
                    <Button variant="ghost" size="sm" :aria-label="$t('layout.edit')"><Pencil class="size-4" /></Button>
                  </Link>
                  <ButtonDelete
                    :action="route('resources.destroy', resource.id)"
                    variant="ghost"
                    size="sm"
                  >
                    <Trash2 class="size-4 text-red-700" />
                    <span class="sr-only">{{ $t('layout.delete') }}</span>
                  </ButtonDelete>
                </div>
              </div>

              <p class="mt-2 flex gap-2 text-sm leading-5 text-gray-500">
                <MapPin class="mt-0.5 size-4 shrink-0" />
                <span>{{ resource.address }}</span>
              </p>

              <div v-if="resource.phone || resource.email" class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-blue-700">
                <a v-if="resource.phone" :href="`tel:${resource.phone}`" class="inline-flex items-center gap-1 hover:underline" @click.stop>
                  <Phone class="size-3.5" />{{ resource.phone }}
                </a>
                <a v-if="resource.email" :href="`mailto:${resource.email}`" class="inline-flex items-center gap-1 hover:underline" @click.stop>
                  <Mail class="size-3.5" />{{ resource.email }}
                </a>
              </div>
            </article>
          </div>
        </aside>
      </div>
    </LayoutBasic>
  </AppLayout>
</template>
