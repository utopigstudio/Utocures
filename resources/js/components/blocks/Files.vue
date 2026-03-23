<script setup lang="ts">
import { ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose, DialogDescription } from '@/components/ui/dialog'
import { DataTable } from '@/components/blocks'
import { Button } from '@/components/ui/button'
import { Form } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { File, Download, Camera } from 'lucide-vue-next'
import type { Column, File as FileType } from '@/types'

interface Props {
  files: Array<FileType> | undefined
  resource: string
  parentId: string
}

const props = defineProps<Props>()

const { t } = useI18n()

const columns: Column<FileType>[] = [
  { key: 'name', label: t('files.name'), sortable: true },
  { key: 'created_at', label: t('files.date_created'), sortable: true }
]

const modalShow = ref(false)
const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const isDragOver = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

const emit = defineEmits(['saved'])

function handleFile(file: File) {
  selectedFile.value = file
  if (file.type.startsWith('image/')) previewUrl.value = URL.createObjectURL(file)
  else previewUrl.value = null

  if (fileInput.value) {
    const dt = new DataTransfer()
    dt.items.add(file)
    fileInput.value.files = dt.files
  }
}

function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) handleFile(file)
}

function onDrop(e: DragEvent) {
  e.preventDefault()
  isDragOver.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) handleFile(file)
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
  isDragOver.value = true
}

function onDragLeave(e: DragEvent) {
  e.preventDefault()
  isDragOver.value = false
}

function resetModal() {
  modalShow.value = false
  selectedFile.value = null
  previewUrl.value = null
  isDragOver.value = false
  if (fileInput.value) fileInput.value.value = ''
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="text-xl flex flex-col md:flex-row items-start md:items-center gap-2 mb-4">
        <div class="flex items-center gap-2">
          <File class="text-blue-600 w-5 h-5" />
          <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('layout.documents_and_files') }}</span>
        </div>
        <Button type="button" class="mt-2 md:mt-0 md:ml-auto w-full md:w-auto" @click="modalShow = true">{{ $t('layout.add_file') }}</Button>
      </CardTitle>
    </CardHeader>

    <CardContent>
      <DataTable
        v-if="files"
        :items="files"
        :columns="columns"
        :inlineActions="['delete']"
        :deleteRouteParams="(payload: any) => route('files.destroy', { file: payload.id, resource: props.resource, id: props.parentId })"
        resource="files"
      >
        <template #inlineActions="{ row }">
          <a
            :href="route('files.download', { file: row.id, resource: props.resource, id: props.parentId })"
            class="text-primary hover:underline"
            download
          >
            <Download class="size-5" />
          </a>
        </template>
        <template #empty>
          <div class="text-center text-gray-500">{{ $t('layout.no_results_found') }}</div>
        </template>
      </DataTable>

      <Dialog v-model:open="modalShow" @update:open="resetModal">
        <DialogContent class="sm:max-w-3xl" :showCloseButton="true">
          <Form
            method="post"
            :action="route('files.store', { resource: props.resource, id: props.parentId })"
            enctype="multipart/form-data"
            reset-on-success
            :options="{
              preserveScroll: true
            }"
            class="space-y-8"
            v-slot="{ errors, processing, clearErrors }"
            @success="() => { resetModal(); emit('saved') }"
          >
            <DialogHeader>
              <DialogTitle class="text-lg font-medium leading-6 text-gray-900">
                {{ $t('layout.upload_file') }}
              </DialogTitle>
            </DialogHeader>

            <DialogDescription>
              <div class="col-span-full">
                <div
                  class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10 transition-colors"
                  :class="isDragOver ? 'bg-indigo-50 border-indigo-400' : ''"
                  @dragover="onDragOver"
                  @dragleave="onDragLeave"
                  @drop="onDrop"
                >
                  <div class="text-center">
                    <Camera class="mx-auto size-12 text-gray-300" />
                    <div class="mt-4 flex text-sm text-gray-600 justify-center">
                      <label
                        for="file"
                        class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 hover:text-indigo-500"
                      >
                        <span>{{ $t('layout.select_file') }}</span>
                        <input
                          id="file"
                          ref="fileInput"
                          name="file"
                          type="file"
                          class="sr-only"
                          @change="onFileChange"
                        />
                      </label>
                      <p class="pl-1">{{ $t('layout.or_drag_here') }}</p>
                    </div>
                    <p class="text-xs text-gray-600">{{ $t('layout.file_size_limit') }}</p>

                    <div v-if="selectedFile" class="mt-6 text-sm text-gray-700">
                      <p class="font-semibold">{{ $t('layout.selected_file') }}</p>
                      <p>{{ selectedFile.name }}</p>
                      <div v-if="previewUrl" class="mt-3">
                        <img
                          :src="previewUrl"
                          alt="Preview"
                          class="mx-auto max-h-48 rounded-md border"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <p v-if="errors" class="mt-3 text-sm text-red-600">{{ errors.file }}</p>
            </DialogDescription>

            <DialogFooter>
              <DialogClose asChild>
                <Button variant="outline" type="button" @click="resetModal(); clearErrors();">{{ $t('layout.cancel') }}</Button>
              </DialogClose>
              <Button type="submit" :disabled="processing">
                {{ processing ? $t('layout.saving') : $t('layout.save') }}
              </Button>
            </DialogFooter>
          </Form>
        </DialogContent>
      </Dialog>
    </CardContent>
  </Card>
</template>
