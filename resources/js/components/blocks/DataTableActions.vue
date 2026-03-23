<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Trash, MoreVertical, Eye, FileEdit, Trash2 } from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from '@/components/ui/dropdown-menu'

interface Props {
  actions: Array<string>
  inlineActions?: Array<string>
  id: number | string
  resource?: string
}

const props = defineProps<Props>()

const emit = defineEmits(['delete-item'])

function buildDeletePayload() {
  return {
    id: props.id,
    resource: props.resource,
  }
}
</script>

<template>
  <DropdownMenu v-if="actions && actions.length > 0">
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
        <MoreVertical class="h-4 w-4" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="p-2">
      <DropdownMenuItem v-if="actions.includes('view')" as-child class="cursor-pointer">
        <Link v-if="resource" :href="`/${resource}/${id}`" class="flex items-center">
        <Eye class="mr-2 h-4 w-4" />
        {{ $t('layout.view') }}
        </Link>
      </DropdownMenuItem>
      <DropdownMenuItem v-if="actions.includes('edit')" as-child class="cursor-pointer">
        <Link :href="`/${resource}/${id}/edit`" class="flex items-center">
          <FileEdit class="mr-2 h-4 w-4" />
          {{ $t('layout.edit') }}
        </Link>
      </DropdownMenuItem>
      <slot name="actions" />
      <DropdownMenuSeparator v-if="actions.includes('delete')" class="mx-2 my-1" />
      <DropdownMenuItem v-if="actions.includes('delete')" class="text-gray-800 focus:text-gray-800 cursor-pointer flex items-center" @click="emit('delete-item', buildDeletePayload())">
        <Trash2 class="mr-2 h-4 w-4" />
        {{ $t('layout.delete') }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
  <div class="flex justify-end gap-4" v-else-if="inlineActions && inlineActions.length > 0">
    <slot name="inlineActions" :id="props.id" />
    <Button
      variant="icon"
      size="icon"
      @click.prevent="emit('delete-item', buildDeletePayload())"
    >
      <Trash class="h-4 w-4" />
    </Button>
  </div>
</template>