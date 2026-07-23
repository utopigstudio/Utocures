<script setup lang="ts">
import { ref } from 'vue'
import { AppLayout, LayoutBasic } from '@/layouts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Camera, Settings } from 'lucide-vue-next'
import { Form } from '@inertiajs/vue3'
import { InputText, Select } from '@/components/ui/input'
import { Button } from '@/components/ui/button';
import type { Country, Configuration } from '@/types';

interface Props {
  configuration: Configuration
  countries: Country[]
}

defineProps<Props>()

const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const isDragOver = ref(false)

function handleFile(file: File) {
  selectedFile.value = file

  if (file.type.startsWith('image/')) {
    previewUrl.value = URL.createObjectURL(file)
  } else {
    previewUrl.value = null
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
</script>

<template>
  <AppLayout>
    <LayoutBasic :icon="Settings" :title="$t('setup.title')">
      <Form
        as="form"
        enctype="multipart/form-data"
        method="POST"
        :action="route('configuration.update', { configuration: configuration.id })"
        class="space-y-6"
        @success="() => { selectedFile = null; previewUrl = null; }"
        v-slot="{ errors, processing }"
      >

        <Card>
          <CardContent class="md:flex md:items-center md:justify-end w-full">
            <div class="flex md:ml-4 gap-6">
              <Button type="submit" :disabled="processing">{{ $t('layout.save') }}</Button>
              <input type="hidden" name="_method" value="PUT" />
            </div>
          </CardContent>
        </Card>

        <div class="grid grid-cols-1">
          <div class="relative lg:col-span-12">
            <Card>
              <CardHeader>
                <CardTitle class="text-xl">{{ $t('setup.config.title') }}</CardTitle>
              </CardHeader>
              <CardContent>
                <InputText
                  class="mt-1 block w-full"
                  name="company_name"
                  :label="$t('setup.config.company_name')"
                  autocomplete="organization"
                  :placeholder="$t('setup.config.company_name')"
                  :default-value="configuration.company_name"
                  :required="true"
                  :error="errors.company_name"
                />
                <InputText
                  class="mt-1 block w-full"
                  name="company_cif_nif"
                  :label="$t('setup.config.company_cif_nif')"
                  :placeholder="$t('setup.config.company_cif_nif')"
                  :default-value="configuration.company_cif_nif"
                  :required="true"
                  :error="errors.company_cif_nif"
                />
                <InputText
                  type="email"
                  name="company_email"
                  :autofocus="true"
                  autocomplete="email"
                  placeholder="email@example.com"
                  :default-value="configuration.company_email"
                  :label="$t('setup.config.company_email')"
                  :required="true"
                  :error="errors.company_email"
                />
                <InputText
                  class="mt-1 block w-full"
                  name="company_phone"
                  :label="$t('setup.config.company_phone')"
                  autocomplete="tel"
                  :default-value="configuration.company_phone"
                  :placeholder="$t('setup.config.company_phone')"
                  :required="true"
                  :error="errors.company_phone"
                />
                <InputText
                  class="mt-1 block w-full"
                  name="company_address"
                  :label="$t('setup.config.company_address')"
                  autocomplete="address"
                  :default-value="configuration.company_address"
                  :placeholder="$t('setup.config.company_address')"
                  :error="errors.company_address"
                  :required="true"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="company_city"
                  :label="$t('setup.config.company_city')"
                  autocomplete="address-level2"
                  :default-value="configuration.company_city"
                  :placeholder="$t('setup.config.company_city')"
                  :error="errors.company_city"
                  :required="true"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="company_zip_code"
                  :label="$t('setup.config.company_zip_code')"
                  autocomplete="postal-code"
                  :default-value="configuration.company_zip_code"
                  :placeholder="$t('setup.config.company_zip_code')"
                  :required="true"
                  :error="errors.company_zip_code"
                />

                <Select
                  class="mt-1 w-full"
                  name="company_country_id"
                  autocomplete="country-name"
                  :label="$t('setup.config.company_country')"
                  :default-value="configuration.company_country_id"
                  :options="countries.map(ct => ({ label: ct.name, value: ct.id }))"
                  :required="true"
                  :error="errors.company_country_id"
                />

                <div class="grid grid-cols-4 gap-4 mt-4">
                  <label class="col-span-4 block text-sm font-medium text-gray-700 mt-2">
                    {{ $t('setup.config.company_image') }}
                  </label>
                  <img v-if="configuration.company_image" :src="configuration.company_image" class="col-span-4 lg:col-span-2 mt-2 mb-4" />
                  <div
                    class="mt-2 col-span-4 lg:col-span-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10 transition-colors"
                    :class="isDragOver ? 'bg-indigo-50 border-indigo-400' : ''"
                    @dragover="onDragOver"
                    @dragleave="onDragLeave"
                    @drop="onDrop"
                  >
                    <div class="text-center">
                      <Camera class="mx-auto size-12 text-gray-300" />
                      <div class="mt-4 flex text-sm text-gray-600 justify-center">
                        <label
                          for="company_image"
                          class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 hover:text-indigo-500"
                        >
                          <span>{{ $t('layout.select_file') }}</span>
                          <input
                            id="company_image"
                            ref="fileInput"
                            name="company_image"
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
                  <p v-if="errors.company_image" class="mt-3 text-sm text-red-600 col-span-4">{{ errors.company_image }}</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <Card>
          <CardContent class="md:flex md:items-center md:justify-end w-full">
            <div class="flex md:ml-4 gap-6">
              <Button type="submit" :disabled="processing">{{ $t('layout.save') }}</Button>
              <input type="hidden" name="_method" value="PUT" />
            </div>
          </CardContent>
        </Card>

      </Form>
    </LayoutBasic>
  </AppLayout>
</template>
