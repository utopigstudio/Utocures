<script setup lang="ts">
import { ref, computed, getCurrentInstance } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { BooleanIcon } from '@/components/ui/basic'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { DeleteModal, DataTableActions } from '@/components/blocks'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils'
import type { Column, Paginated, SortDir, SortKey, Align } from '@/types'

interface Props<T = any> {
  items: Paginated<T> | Array<T>
  columns: Column<T>[]
  sortKey?: SortKey
  sortDir?: SortDir
  actions?: Array<string>
  inlineActions?: Array<string>
  resource?: string
  deleteRouteParams?: (payload: Record<string, any>) => string
  cardClass?: string
  striped?: boolean
}

const props = defineProps<Props>()

const { getInitials } = useInitials();
const selectedRowId = ref<string | null>(null)
const expandedRow = ref<string | null>(null)

function isPaginated<T>(items: Paginated<T> | Array<T>): items is Paginated<T> {
  return (items as Paginated<T>).links !== undefined
}

function selectRow(row: any) {
  if ((props.resource && row.id) && hasViewRoute) {
    router.visit(route(`${props.resource}.show`, row.id))
  }
}

const hasViewRoute: boolean = props.resource ? route().has(`${props.resource}.show`) : false

const emit = defineEmits<{
  (e: 'sort-change', payload: { key: string; dir: Exclude<SortDir, null> }): void,
  (e: 'download-excel'): void,
}>()

const isExportable = computed(() => !!getCurrentInstance()?.vnode.props?.onDownloadExcel)

function onSortClick(col: Column) {
  if (!col.sortable) return
  const key = String(col.key)
  let dir: Exclude<SortDir, null> = 'asc'
  if (props.sortKey === key) {
    dir = props.sortDir === 'asc' ? 'desc' : 'asc'
  }
  emit('sort-change', { key, dir })
}

function tdAlignClass(align?: Align) {
  if (align === 'center') return 'text-center'
  if (align === 'right') return 'text-right'
  return 'text-left'
}

function thAlignClass(align?: Align) {
  if (align === 'center') return 'text-center'
  if (align === 'right') return 'text-right'
  return 'text-left'
}

function getValue(row: any, col: Column) {
  const keys = String(col.key).split('.')
  let val = row
  for (const k of keys) {
    val = val?.[k]
    if (val === undefined) break
  }
  return val
}

function format(value: any, format?: string) {
  if (format === 'currency') {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value)
  }
  return value
}

const open = ref(false)
const toDelete = ref<Record<string, any> | null>(null)

function confirmDelete(payload: Record<string, any>) {
  toDelete.value = payload
  open.value = true
}

function defaultDeleteRoute(payload: Record<string, any>) {
  if (props.resource && payload?.id) {
    return route(`${props.resource}.destroy`, payload.id)
  }
  if (props.resource && payload?.row?.uuid) {
    return route(`${props.resource}.destroy`, { uuid: payload.row.uuid })
  }
  console.warn('No se pudo construir la ruta de borrado. Ajusta deleteRouteParams.')
  return '#'
}

function isLink(col: Column, row: any): boolean {
  if (typeof col.link !== 'function') return false
  let href = null
  try {
    href = col.link(row) || null
  } catch (e) {
    console.error('Error evaluating link function for column:', col, e)
    href = null
  }
  return !!href && typeof href === 'string'
}

function toggleRow(row: any) {
  const id = row.id ?? row.email
  expandedRow.value = expandedRow.value === id ? null : id
}
</script>

<template>
  <div class="px-0">
    <div class="mt-6 flow-root">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row md:items-center md:justify-between px-4 md:px-0">
        <div class="w-full md:w-auto">
          <slot name="headerFilters" />
        </div>

        <div class="w-full md:w-auto flex justify-end">
          <slot name="headerActions" />
        </div>
      </div>
      <div
        data-slot="card"
        :class="cn(
          'bg-card text-card-foreground flex flex-col gap-6 border shadow-sm md:rounded-xl px-6 py-8 w-full',
          props.cardClass
        )"
      >
        <div class="w-full overflow-x-auto border-b border-gray-200">
          <table class="min-w-max w-full divide-y divide-gray-300">
            <thead>
              <tr>
                <th
                  v-for="col in columns"
                  :key="String(col.key)"
                  scope="col"
                  :class="[
                    'py-3.5 px-3 text-sm font-normal text-gray-500 select-none leading-5',
                    thAlignClass(col.align),
                    col.headerClass,
                  ]"
                >
                  <button
                    v-if="col.sortable"
                    type="button"
                    class="inline-flex items-center gap-1"
                    @click="onSortClick(col)"
                  >
                    <span>{{ col.label }}</span>
                    <span v-if="sortKey === String(col.key)">
                      <span v-if="sortDir === 'asc'">▲</span>
                      <span v-else-if="sortDir === 'desc'">▼</span>
                    </span>
                    <span v-else class="opacity-40">↕</span>
                  </button>
                  <span v-else>{{ col.label }}</span>
                </th>

                <th v-if="props.actions || $slots.actions || props.inlineActions || $slots.inlineActions" class="py-3.5 px-3 text-sm font-normal text-gray-500 select-none leading-5 text-right">
                  {{ $t('layout.actions') }}
                </th>
              </tr>
            </thead>

            <tbody v-if="(isPaginated(props.items) ? props.items.data : props.items).length === 0">
              <tr>
                <td :colspan="columns.length + (props.actions || $slots.actions || props.inlineActions || $slots.inlineActions ? 1 : 0)"
                    class="text-center py-4">
                  <slot name="empty" />
                </td>
              </tr>
            </tbody>

            <tbody v-else class="divide-y divide-gray-200">
              <template
                v-for="(row, index) in (isPaginated(props.items) ? props.items.data : props.items)"
                :key="row.id ?? row.email"
              >
                <tr
                  :class="[
                    'hover:bg-gray-50 transition-colors',
                    hasViewRoute || $slots.rowDetails ? 'cursor-pointer' : 'cursor-default',
                    selectedRowId === (row.id ?? row.email) ? 'bg-gray-50' : '',
                    props.striped && index % 2 === 0 ? 'bg-white' : '',
                    props.striped && index % 2 === 1 ? 'bg-transparent' : ''
                  ]"
                  @click="$slots.rowDetails ? toggleRow(row) : selectRow(row)"
                >
                  <td
                    v-for="col in columns"
                    :key="String(col.key)"
                    :class="[
                      'py-4 px-3 text-sm leading-5 font-normal whitespace-nowrap text-gray-800',
                      tdAlignClass(col.align),
                      col.class,
                    ]"
                  >
                    <slot
                      :name="`cell-${String(col.key)}`"
                      :row="row"
                      :value="getValue(row, col)"
                    >
                      <span
                        v-if="col.format === 'html'"
                        v-html="getValue(row, col)"
                        class="whitespace-normal break-words"
                      />
                      <span v-else-if="col.format === 'avatar'">
                        <Avatar class="h-8 w-8 overflow-hidden rounded-full">
                          <AvatarImage v-if="getValue(row, col)" :src="getValue(row, col)"></AvatarImage>
                          <AvatarFallback v-else class="rounded-full text-black bg-blue-gray-100 text-xs font-bold leading-4">
                            {{ getInitials(row.user?.name ?? row.name) }}
                          </AvatarFallback>
                        </Avatar>
                      </span>
                      <span v-else-if="col.format === 'boolean'">
                        <BooleanIcon :value="!!getValue(row, col)" />
                      </span>
                      <span v-else-if="col.format === 'pills'">
                        <template v-for="(item, i) in String(getValue(row, col)).split(',').map(i => i.trim())" :key="i">
                          <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full mr-1 mb-1">
                            {{ item }}
                          </span>
                        </template>
                      </span>
                      <template v-else>
                        <Link
                          v-if="isLink(col, row)"
                          :href="col.link?.(row)"
                          class="text-blue-600 hover:underline"
                          @click.stop
                        >
                          {{ format(getValue(row, col), col.format) }}
                        </Link>
                        <span v-else>
                          {{ format(getValue(row, col), col.format) }}
                        </span>
                      </template>
                    </slot>
                  </td>

                  <td
                    v-if="props.actions || $slots.actions || props.inlineActions || $slots.inlineActions"
                    class="py-4 px-1 text-center whitespace-nowrap text-right"
                    @click.stop
                  >
                    <DataTableActions
                      :id="row.id"
                      :resource="props.resource"
                      :actions="props.actions || []"
                      :inlineActions="props.inlineActions || []"
                      @delete-item="confirmDelete"
                    >
                      <template #actions>
                        <slot name="actions" :row="row" />
                      </template>
                      <template #inlineActions>
                        <slot name="inlineActions" :row="row" />
                      </template>
                    </DataTableActions>
                  </td>
                </tr>

                <tr
                  v-if="$slots.rowDetails && expandedRow === (row.id ?? row.email)"
                  class="bg-gray-50"
                >
                  <td
                    :colspan="columns.length + (props.actions || $slots.actions || props.inlineActions || $slots.inlineActions ? 1 : 0)"
                    class="p-0"
                  >
                    <slot name="rowDetails" :row="row" />
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <div v-if="isExportable && (isPaginated(props.items) && props.items.total > 0)" class="flex items-center justify-end -mt-4 -mb-4">
          <Button @click="$emit('download-excel')" variant="secondary">
            {{ $t('layout.download_excel') }}
          </Button>
        </div>
        <nav
          v-if="isPaginated(props.items)"
          class="flex items-center justify-between px-4"
        >
          <div class="-mt-px flex w-0 flex-1">
            <component
              :is="props.items.links[0].url ? Link : 'span'"
              :href="props.items.links[0].url || undefined"
              :class="[
                cn(
                  'inline-flex items-center border-t-2 border-transparent pt-4 pr-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700',
                  props.items.links[0].active ? 'border-indigo-500 text-indigo-600' : '',
                  props.items.links[0].url ? '' : 'pointer-events-none'
                ),
              ]"
            >
              <ChevronLeft class="mr-3 size-5 text-gray-400" aria-hidden="true" />
              {{ props.items.links[0].label }}
            </component>
          </div>

          <div class="-mt-px hidden md:flex">
            <Link
              v-for="(l, i) in props.items.links.slice(1, -1)"
              :key="i"
              :href="l.url"
              :class="[
                cn(
                  'inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700',
                  l.active ? 'border-blue-900 text-blue-900' : ''
                ),
              ]"
            >
              {{ l.label }}
            </Link>
          </div>

          <div class="-mt-px flex w-0 flex-1 justify-end">
            <component
              :is="props.items.links[props.items.links.length - 1].url ? Link : 'span'"
              :href="props.items.links[props.items.links.length - 1].url || undefined"
              :class="[
                cn(
                  'inline-flex items-center border-t-2 border-transparent pt-4 pl-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700',
                  props.items.links[props.items.links.length - 1].active
                    ? 'border-blue-900 text-blue-900'
                    : '',
                  props.items.links[props.items.links.length - 1].url ? '' : 'pointer-events-none'
                ),
              ]"
            >
              {{ props.items.links[props.items.links.length - 1].label }}
              <ChevronRight class="ml-3 size-5 text-gray-400" aria-hidden="true" />
            </component>
          </div>
        </nav>
        <div v-if="isPaginated(props.items) && props.items.total > 0" class="inline-flex items-center text-sm text-gray-700 self-center">
          {{ $t('layout.total_data') }}: {{ props.items.total }}
        </div>
      </div>
    </div>
    <DeleteModal
      :show="open"
      v-if="toDelete"
      :action="props.deleteRouteParams ? props.deleteRouteParams(toDelete) : defaultDeleteRoute(toDelete)"
      @deleted="open = false; toDelete = null"
      @close="open = false; toDelete = null"
    />
  </div>
</template>
