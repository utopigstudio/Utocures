<script setup lang="ts">
import { ref } from 'vue'
import { AppLayout, LayoutBasic } from '@/layouts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Camera, Save, X, UserRound } from 'lucide-vue-next'
import { Form, Link, usePage } from '@inertiajs/vue3'
import { InputText } from '@/components/ui/input'
import { Button } from '@/components/ui/button';
import { useI18n } from 'vue-i18n';
import { useInitials } from '@/composables/useInitials';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import InputCheckbox from '@/components/ui/input/InputCheckbox.vue';
import type { BreadcrumbItem, User } from '@/types'

const page = usePage();
const current_user = page.props.auth.user as User;

const { t } = useI18n();

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('users.create'), href: '/users/create' },
]

interface Props {
  user?: User
}

const props = defineProps<Props>()
const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const isDragOver = ref(false)
const userName = ref(props.user?.name ?? '');
const { getInitials } = useInitials();

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
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Form
        as="form"
        enctype="multipart/form-data"
        method="POST"
        :action="props.user ? route('users.update', props.user.id) : route('users.store')"
        class="space-y-6"
        v-slot="{ errors, processing }"
      >
        <Card>
          <CardContent class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex">
              <Avatar v-if="props.user" class="h-8 w-8 overflow-hidden rounded-full mr-4">
                <AvatarImage v-if="props.user.avatar" :src="props.user.avatar" :alt="props.user.name">
                  {{ props.user.name }}
                </AvatarImage>
                <AvatarFallback v-else class="rounded-full text-black bg-blue-gray-100 text-xs font-bold leading-4">
                  {{ getInitials(props.user.name) }}
                </AvatarFallback>
              </Avatar>
              <h2 class="text-2xl leading-8 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">{{ userName }}</h2>
            </div>
            <div :class="`${props.user ? 'mt-4 md:mt-0' : ''} flex md:ml-4 gap-6`">
              <Link :href="route('users.index')">
                <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                  <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                </Button>
              </Link>
              <Button :disabled="processing" size="lg" class="flex items-center gap-2">
                <Save class="size-4" />
                {{ props.user ? $t('layout.save_changes') : $t('layout.save') }}
              </Button>
              <input v-if="props.user" type="hidden" name="_method" value="PUT">
            </div>
          </CardContent>
        </Card>

        <div class="grid grid-cols-1">
          <div class="relative lg:col-span-12">
            <Card>
              <CardHeader>
                <CardTitle class="text-xl flex items-center gap-2 mb-4">
                  <UserRound class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('users.user_information') }}</span>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="grid grid-cols-4 mb-6">
                  <div class="col-span-12">
                    <label class="block text-sm font-medium text-gray-700 mt-2">
                      {{ t('users.avatar') }}
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
                            for="file"
                            class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 hover:text-indigo-500"
                          >
                            <span>{{ $t('layout.select_file') }}</span>
                            <input
                              id="file"
                              ref="fileInput"
                              name="avatar"
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
                <div v-if="current_user.id !== props.user?.id" class="grid grid-cols-1">
                  <InputCheckbox
                    name="is_active"
                    :label="t('users.active')"
                    :default-value="Boolean(props.user?.is_active) ?? true"
                    :error="errors.is_active"
                  />
                </div>
                <div class="grid grid-cols-1">
                  <div class="relative lg:col-span-6">
                    <InputText
                      class="mt-1 block w-full"
                      name="name"
                      :label="t('users.name')"
                      :default-value="props.user?.name"
                      :required="true"
                      @update:modelValue="userName = $event.toString()"
                      :error="errors.name"
                    />
                  </div>
                  <div class="relative lg:col-span-6">
                    <InputText
                      class="mt-1 block w-full"
                      name="email"
                      :label="t('users.email')"
                      :default-value="props.user?.email"
                      :required="true"
                      :error="errors.email"
                    />
                  </div>
                </div>
                <div v-if="current_user.id === props.user?.id" class="grid grid-cols-1">
                  <div class="relative lg:col-span-6">
                    <InputText
                      class="mt-1 block w-full"
                      type="password"
                      name="password"
                      :label="t('users.password')"
                      :default-value="props.user?.password"
                      :required="props.user ? false : true"
                      :error="errors.password"
                    />
                  </div>
                  <div class="relative lg:col-span-6">
                    <InputText
                      class="mt-1 block w-full"
                      type="password"
                      name="password_confirmation"
                      :label="t('users.password_confirmation')"
                      :default-value="props.user?.password_confirmation"
                      :required="props.user ? false : true"
                    />
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

      </Form>
    </LayoutBasic>
  </AppLayout>
</template>
