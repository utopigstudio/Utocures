<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Form } from '@inertiajs/vue3'
import { InputTextarea } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose, DialogDescription } from '@/components/ui/dialog'
import type { Note } from '@/types'
import DeleteModal from './DeleteModal.vue'
import { InputText } from '@/components/ui/input'
import { StickyNote, Calendar, Clock, Pencil, Trash2, AlertTriangle, Link2 } from 'lucide-vue-next'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils'
import { useI18n } from 'vue-i18n'
import type { HTMLAttributes } from 'vue'

interface Props {
  notes?: Note[];
  resource: string
  parentId: number | string
  actionsEnabled?: boolean
  filter?: string
  createType?: 'general' | 'incident'
  allowTypeSelection?: boolean
  employeeTimeRecordId?: string | null
  createButtonLabel?: string
  highlightNoteId?: string | null
  class?: HTMLAttributes['class']
  titleClass?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
  actionsEnabled: true,
  createType: 'general'
})

const emit = defineEmits(['saved', 'search'])
const { t } = useI18n()

const modalShow = ref(false)
const modalShowDestroy = ref(false)
const toEdit = ref<Note | null>(null)
const toDelete = ref<Note | null>(null)
const searchFilter = ref<string>(props.filter || '')
const createNoteType = ref<'general' | 'incident'>(props.createType)
const { getInitials } = useInitials();
const canCreateNote = computed(() => {
  if (props.allowTypeSelection) {
    return true
  }

  return props.createType !== 'incident' || !!props.employeeTimeRecordId
})
const createButtonLabel = computed(() => {
  if (props.createButtonLabel) {
    return props.createButtonLabel
  }

  if (props.allowTypeSelection) {
    return t('notes.add_note_or_incident')
  }

  return props.createType === 'incident' ? t('notes.add_incident') : t('notes.add_note')
})
const incidentSelected = computed(() => !toEdit.value && createNoteType.value === 'incident')
const canSubmit = computed(() => !incidentSelected.value || !!props.employeeTimeRecordId)
const createDialogTitle = computed(() => {
  if (toEdit.value) {
    return t('notes.edit_note')
  }

  return createNoteType.value === 'incident' ? t('notes.add_incident') : t('notes.add_note')
})
const highlightedNoteId = computed(() => props.highlightNoteId ?? null)

async function scrollToHighlightedNote(noteId?: string | null) {
  if (!noteId || typeof window === 'undefined') {
    return
  }

  await nextTick()

  const element = document.getElementById(`note-${noteId}`)

  if (!element) {
    return
  }

  element.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

function openCreateModal() {
  toEdit.value = null
  createNoteType.value = props.createType
  modalShow.value = true
}

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

function formatRecordRange(note: Note) {
  if (!note.employee_time_record) return null

  const timeOut = note.employee_time_record.time_out ? ` - ${note.employee_time_record.time_out}` : ''

  return `${note.employee_time_record.date_in} · ${note.employee_time_record.time_in}${timeOut}`
}

function linkedRecordLabel(note: Note) {
  if (!note.employee_time_record) {
    return t('notes.linked_time_record_unavailable')
  }

  const employeeName = note.employee_time_record.employee?.user?.name
  const serviceName = note.employee_time_record.assigned_hour?.service?.name
  const parts = [formatRecordRange(note), employeeName, serviceName].filter(Boolean)

  return parts.join(' · ')
}

watch(
  () => searchFilter.value,
  (newSearch) => {
    emit('search', newSearch)
  }
)

watch(
  () => highlightedNoteId.value,
  (noteId) => {
    scrollToHighlightedNote(noteId)
  },
  { immediate: true }
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
        <Button type="button" class="sm:ml-auto w-full sm:w-auto" :disabled="!canCreateNote" @click="openCreateModal">{{ createButtonLabel }}</Button>
      </CardTitle>
    </CardHeader>
    <CardContent>
      <div class="flex gap-4 mb-6">
        <InputText variant="filter" type="text" v-model="searchFilter" :placeholder="$t('clients.search')" />
      </div>
      <p v-if="!props.allowTypeSelection && props.createType === 'incident' && !canCreateNote" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
        {{ $t('notes.start_time_record_before_incident') }}
      </p>
      <template v-if="notes?.length">
        <div
          v-for="(note, i) in notes"
          :key="i"
          :id="`note-${note.id}`"
          :class="[
            'rounded-lg p-4 mb-6 last:mb-0 transition-all duration-300',
            note.id === highlightedNoteId
              ? 'bg-amber-50 ring-2 ring-amber-300 shadow-sm'
              : 'bg-blue-gray-50',
          ]"
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
                <span
                  class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
                  :class="note.type === 'incident' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'"
                >
                  <AlertTriangle v-if="note.type === 'incident'" class="mr-1 size-3.5" />
                  {{ note.type === 'incident' ? $t('notes.incident') : $t('notes.general_note') }}
                </span>
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
          <div v-if="note.type === 'incident'" class="mb-3 flex items-start gap-2 rounded-lg bg-white px-3 py-2 text-sm text-blue-gray-600">
            <Link2 class="mt-0.5 size-4 shrink-0 text-amber-600" />
            <span>{{ linkedRecordLabel(note) }}</span>
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
            {{ createDialogTitle }}
          </DialogTitle>
        </DialogHeader>

        <DialogDescription>
          <div v-if="!toEdit && props.allowTypeSelection" class="mb-4 flex flex-wrap gap-2">
            <Button
              type="button"
              size="sm"
              :variant="createNoteType === 'general' ? 'default' : 'outline'"
              @click="createNoteType = 'general'"
            >
              {{ $t('notes.general_note') }}
            </Button>
            <Button
              type="button"
              size="sm"
              :variant="createNoteType === 'incident' ? 'default' : 'outline'"
              @click="createNoteType = 'incident'"
            >
              {{ $t('notes.incident') }}
            </Button>
          </div>
          <input v-if="!toEdit" type="hidden" name="type" :value="createNoteType" />
          <input v-if="!toEdit && incidentSelected && props.employeeTimeRecordId" type="hidden" name="employee_time_record_id" :value="props.employeeTimeRecordId" />
          <InputTextarea
            name="content"
            :default-value="toEdit?.content || ''"
            :placeholder="$t('notes.write_your_note_here')"
            :error="errors?.content?.[0]"
            required
          />
          <p v-if="incidentSelected && !props.employeeTimeRecordId" class="mt-2 text-sm text-amber-700">
            {{ $t('notes.start_time_record_before_incident') }}
          </p>
          <p v-if="errors?.employee_time_record_id?.[0]" class="mt-2 text-sm text-red-600">
            {{ errors.employee_time_record_id[0] }}
          </p>
        </DialogDescription>

        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline" type="button" @click="reset(); clearErrors(); toEdit = null">{{ $t('layout.cancel') }}</Button>
          </DialogClose>
          <Button type="submit" :disabled="processing || !canSubmit">
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