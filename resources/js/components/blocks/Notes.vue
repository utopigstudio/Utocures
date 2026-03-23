<script setup lang="ts">
import { ref, watch } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Form } from '@inertiajs/vue3'
import { InputTextarea } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose, DialogDescription } from '@/components/ui/dialog'
import type { Note } from '@/types'
import DeleteModal from './DeleteModal.vue'
import { InputText } from '@/components/ui/input'
import { StickyNote, Calendar, Clock, Pencil, Trash2 } from 'lucide-vue-next'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils'
import type { HTMLAttributes } from 'vue'

interface Props {
  notes?: Note[];
  resource: string
  parentId: number | string
  actionsEnabled?: boolean
  filter?: string
  class?: HTMLAttributes['class']
  titleClass?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
  actionsEnabled: true
})

const emit = defineEmits(['saved', 'search'])

const modalShow = ref(false)
const modalShowDestroy = ref(false)
const toEdit = ref<Note | null>(null)
const toDelete = ref<Note | null>(null)
const searchFilter = ref<string>(props.filter || '')
const { getInitials } = useInitials();

function formatDate(dateStr: any) {
  const date = new Date(dateStr)
  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

function formatTime(dateStr: any) {
  const date = new Date(dateStr)
  return date.toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

watch(
  () => searchFilter.value,
  (newSearch) => {
    emit('search', newSearch)
  }
)
</script>

<template>
  <Card :class="cn('rounded-xl', props.class)">
    <CardHeader>
      <CardTitle class="text-xl flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 mb-4">
        <div :class="cn('flex items-center gap-2 whitespace-nowrap', props.titleClass)">
          <StickyNote class="text-blue-600 w-5 h-5 flex-shrink-0" />
          <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('clients.notes') }}</span>
        </div>
        <Button type="button" class="sm:ml-auto w-full sm:w-auto" @click="modalShow = true">{{ $t('clients.add_note') }}</Button>
      </CardTitle>
    </CardHeader>
    <CardContent>
      <div class="flex gap-4 mb-6">
        <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="$t('clients.search')" />
      </div>
      <template v-if="notes?.length">
        <div
          v-for="(note, i) in notes"
          :key="i"
          class="bg-blue-gray-50 rounded-lg p-4 mb-6 last:mb-0"
        >
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-3">
            <div class="flex items-center gap-3">
              <Avatar class="h-8 w-8 overflow-hidden rounded-full">
                <AvatarImage v-if="note.user.avatar" :src="note.user.avatar"></AvatarImage>
                <AvatarFallback v-else class="rounded-full text-black bg-blue-gray-100 text-xs font-bold leading-4">
                  {{ getInitials(note.user.name) }}
                </AvatarFallback>
              </Avatar>
              <p class="font-semibold text-gray-800">{{ note.user.name }}</p>
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3">
              <div class="text-sm text-gray-500 flex flex-row items-start gap-2 sm:gap-3">
                <div class="flex items-center gap-2">
                  <Calendar class="text-blue-gray-400 size-4" />
                  <span class="text-sm leading-5">{{ formatDate(note.created_at) }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <Clock class="text-blue-gray-400 size-4" />
                  <span class="text-sm leading-5">{{ formatTime(note.created_at) }} h</span>
                </div>
              </div>
              <div class="flex flex-row items-center gap-2 whitespace-nowrap flex-shrink-0" v-if="actionsEnabled">
                <Button
                  variant="destructive"
                  size="sm"
                  @click.prevent="toDelete = note; modalShowDestroy = true"
                >
                  <Trash2 class="text-white"/>
                  {{ $t('layout.delete') }}
                </Button>
                <Button
                  size="sm"
                  @click.prevent="toEdit = note; modalShow = true"
                >
                  <Pencil class="text-white"/>
                  {{ $t('layout.edit') }}
                </Button>
              </div>
            </div>
          </div>
          <p class="text-sm sm:text-base leading-6 font-normal text-blue-gray-700">
            {{ note.content }}
          </p>
        </div>
      </template>
      <p v-else class="text-center text-gray-500">{{ $t('layout.no_results_found') }}</p>
    </CardContent>
  </Card>

  <Dialog v-model:open="modalShow">
    <DialogContent class="sm:max-w-3xl" :showCloseButton="true">
      <Form
        :method="toEdit ? 'put' : 'post'"
        :action="toEdit ? route('notes.update', { note: toEdit.id, resource: resource, id: parentId }) : route('notes.store', { resource: resource, id: parentId })"
        reset-on-success
        :options="{
          preserveScroll: true
        }"
        class="space-y-8"
        v-slot="{ errors, processing, reset, clearErrors }"
        @success="() => { emit('saved'); modalShow = false; toEdit = null }"
      >
        <DialogHeader>
          <DialogTitle class="text-lg font-medium leading-6 text-gray-900">
            {{ toEdit ? $t('notes.edit_note') : $t('notes.add_note') }}
          </DialogTitle>
        </DialogHeader>

        <DialogDescription>
          <InputTextarea
            name="content"
            :default-value="toEdit?.content || ''"
            :placeholder="$t('notes.write_your_note_here')"
            :error="errors?.content?.[0]"
            required
          />
        </DialogDescription>

        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline" type="button" @click="reset(); clearErrors(); toEdit = null">{{ $t('layout.cancel') }}</Button>
          </DialogClose>
          <Button type="submit" :disabled="processing">
            {{ toEdit ? $t('layout.save_changes') : $t('layout.save') }}
          </Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>

  <DeleteModal
    v-if="toDelete"
    :show="modalShowDestroy"
    :action="route('notes.destroy', { note: toDelete.id, resource: resource, id: parentId })"
    @deleted="emit('saved'); modalShowDestroy = false; toDelete = null"
  />
</template>