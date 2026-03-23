<script setup lang="ts">
import { ref } from 'vue';
import { AuthLayout } from '@/layouts';
import { Button } from '@/components/ui/button';
import { InputText, Select } from '@/components/ui/input';
import { Form } from '@inertiajs/vue3';
import { Camera, LoaderCircle } from 'lucide-vue-next';
import type { Country } from '@/types';

defineProps<{
  existsUser: boolean;
  existsConfig: boolean;
  countries: Country[];
}>();

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
  <AuthLayout :title="$t('setup.title')" class-card-title="text-3xl md:text-3xl" class-card-content="pt-0 pb-0" class-logo-header="md:mb-19">

    <div class="space-y-6">
      <Form method="post" :action="route('setup.store')" v-slot="{ errors, processing }">
        <div v-if="!existsUser" class="grid gap-2 mb-8">
          <h2 class="text-2xl font-bold mb-4">{{ $t('setup.user.title') }}</h2>
          <InputText
            class="mt-1 block w-full"
            name="user_name"
            :label="$t('setup.user.name')"
            autocomplete="off"
            :placeholder="$t('setup.user.name')"
            :required="true"
            :error="errors.user_name"
          />
          <InputText
            type="email"
            name="user_email"
            autocomplete="email"
            :autofocus="true"
            placeholder="email@example.com"
            :label="$t('setup.user.email')"
            :required="true"
            :error="errors.user_email"
          />
          <InputText
            type="password"
            name="user_password"
            :label="$t('setup.user.password')"
            :required="true"
            :error="errors.user_password"
          />
          <InputText
            type="password"
            name="user_password_confirmation"
            :label="$t('setup.user.password_confirmation')"
            :required="true"
            :error="errors.user_password_confirmation"
          />
        </div>

        <div v-if="!existsConfig" class="grid gap-2">
          <h2 class="text-2xl font-bold mb-4">{{ $t('setup.config.title') }}</h2>
          <InputText
            class="mt-1 block w-full"
            name="company_name"
            :label="$t('setup.config.company_name')"
            autocomplete="organization"
            :placeholder="$t('setup.config.company_name')"
            :required="true"
            :error="errors.company_name"
          />
          <InputText
            class="mt-1 block w-full"
            name="company_cif_nif"
            autocomplete="company_cif_nif"
            :label="$t('setup.config.company_cif_nif')"
            :placeholder="$t('setup.config.company_cif_nif')"
            :required="true"
            :error="errors.company_cif_nif"
          />
          <InputText
            type="email"
            name="company_email"
            autocomplete="company_email"
            :autofocus="true"
            placeholder="email@example.com"
            :label="$t('setup.config.company_email')"
            :required="true"
            :error="errors.company_email"
          />
          <InputText
            class="mt-1 block w-full"
            name="company_phone"
            :label="$t('setup.config.company_phone')"
            autocomplete="tel"
            :placeholder="$t('setup.config.company_phone')"
            :required="true"
            :error="errors.company_phone"
          />
          <InputText
            class="mt-1 block w-full"
            name="company_address"
            :label="$t('setup.config.company_address')"
            autocomplete="address"
            :placeholder="$t('setup.config.company_address')"
            :error="errors.company_address"
            :required="true"
          />

          <InputText
            class="mt-1 block w-full"
            name="company_city"
            :label="$t('setup.config.company_city')"
            autocomplete="address-level2"
            :placeholder="$t('setup.config.company_city')"
            :error="errors.company_city"
            :required="true"
          />

          <InputText
            class="mt-1 block w-full"
            name="company_zip_code"
            :label="$t('setup.config.company_zip_code')"
            autocomplete="postal-code"
            :placeholder="$t('setup.config.company_zip_code')"
            :required="true"
            :error="errors.company_zip_code"
          />

          <Select
            class="mt-1 w-full"
            name="company_country_id"
            autocomplete="country-name"
            :label="$t('setup.config.company_country')"
            :options="countries.map(ct => ({ label: ct.name, value: ct.id }))"
            :required="true"
            :error="errors.company_country_id"
          />

          <div class="col-span-12 mt-4">
            <label class="block text-sm font-medium text-gray-700 mt-2">
              {{ $t('setup.config.company_image') }}
            </label>
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
            <p v-if="errors.avatar" class="mt-3 text-sm text-red-600">{{ errors.avatar }}</p>
          </div>
        </div>

        <div class="my-6 flex items-center justify-start">
          <Button class="w-full" type="submit" :disabled="processing">
            <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
            {{ $t('setup.button') }}
          </Button>
        </div>
      </Form>
    </div>
  </AuthLayout>
</template>
