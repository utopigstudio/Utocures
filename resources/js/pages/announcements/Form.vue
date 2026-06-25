<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { InputText, Wysiwyg } from '@/components/ui/input';
import { AppLayout, LayoutBasic } from '@/layouts';
import type { Announcement, BreadcrumbItem } from '@/types';
import { Form, Link } from '@inertiajs/vue3';
import { Camera, Megaphone, Save, X } from 'lucide-vue-next';
import { onBeforeUnmount, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    announcement?: Announcement;
}

const props = defineProps<Props>();
const { t } = useI18n();

const selectedFile = ref<File | null>(null);
const previewUrl = ref<string | null>(null);
const isDragOver = ref(false);

const breadcrumbItems: BreadcrumbItem[] = [{ title: t('announcements.create'), href: '/announcements/create' }];

function revokePreview() {
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }
}

function handleFile(file: File) {
    revokePreview();
    selectedFile.value = file;
    previewUrl.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
}

function onFileChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (file) {
        handleFile(file);
    }
}

function onDrop(event: DragEvent) {
    event.preventDefault();
    isDragOver.value = false;

    const file = event.dataTransfer?.files?.[0];

    if (file) {
        handleFile(file);
    }
}

function onDragOver(event: DragEvent) {
    event.preventDefault();
    isDragOver.value = true;
}

function onDragLeave(event: DragEvent) {
    event.preventDefault();
    isDragOver.value = false;
}

onBeforeUnmount(() => {
    revokePreview();
});
</script>

<template>
    <AppLayout>
        <LayoutBasic :breadcrumbs="breadcrumbItems">
            <Form
                as="form"
                enctype="multipart/form-data"
                method="post"
                :action="props.announcement ? route('announcements.update', props.announcement.id) : route('announcements.store')"
                class="space-y-6"
                @success="
                    () => {
                        selectedFile = null;
                        revokePreview();
                        previewUrl = null;
                    }
                "
                v-slot="{ errors, processing }"
            >
                <div class="grid grid-cols-1">
                    <div class="relative lg:col-span-12">
                        <Card>
                            <CardHeader>
                                <CardTitle class="mb-4 text-xl lg:flex lg:items-center lg:justify-between">
                                    <div class="mb-4 flex items-center gap-2 lg:mb-0">
                                        <Megaphone class="h-5 w-5 text-blue-600" />
                                        <span class="text-2xl leading-8 font-bold text-gray-900">{{
                                            props.announcement ? $t('announcements.edit') : $t('announcements.create')
                                        }}</span>
                                    </div>
                                    <div class="flex flex-wrap justify-between gap-6">
                                        <Link :href="route('announcements.index')">
                                            <Button variant="outline" size="lg" class="flex items-center gap-2 border-2 text-blue-gray-600">
                                                <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                                            </Button>
                                        </Link>
                                        <Button type="submit" size="lg" :disabled="processing">
                                            <Save class="size-4 text-white" />
                                            {{ props.announcement ? $t('layout.save_changes') : $t('layout.save') }}
                                        </Button>
                                    </div>
                                </CardTitle>
                            </CardHeader>

                            <CardContent>
                                <input v-if="props.announcement" type="hidden" name="_method" value="PUT" />

                                <InputText
                                    class="mt-1 mb-4 block w-full lg:mb-0"
                                    name="title"
                                    :label="t('announcements.title_field')"
                                    :default-value="props.announcement?.title"
                                    autocomplete="off"
                                    :placeholder="t('announcements.title_field')"
                                    :required="true"
                                    :error="errors.title"
                                />

                                <div class="mt-4 grid grid-cols-4 gap-4">
                                    <label class="col-span-4 mt-2 block text-sm font-medium text-gray-700">
                                        {{ $t('announcements.image') }}
                                    </label>

                                    <img
                                        v-if="previewUrl || props.announcement?.image"
                                        :src="previewUrl ?? props.announcement?.image ?? undefined"
                                        :alt="props.announcement?.title ?? t('announcements.image')"
                                        class="col-span-4 mt-2 mb-4 max-h-72 w-full rounded-lg object-cover lg:col-span-2"
                                    />

                                    <div
                                        class="col-span-4 mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10 transition-colors lg:col-span-2"
                                        :class="isDragOver ? 'border-indigo-400 bg-indigo-50' : ''"
                                        @dragover="onDragOver"
                                        @dragleave="onDragLeave"
                                        @drop="onDrop"
                                    >
                                        <div class="text-center">
                                            <Camera class="mx-auto size-12 text-gray-300" />
                                            <div class="mt-4 flex justify-center text-sm text-gray-600">
                                                <label
                                                    for="image"
                                                    class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 hover:text-indigo-500"
                                                >
                                                    <span>{{ $t('layout.select_file') }}</span>
                                                    <input
                                                        id="image"
                                                        name="image"
                                                        type="file"
                                                        accept="image/jpeg,image/png,image/jpg,image/webp"
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
                                            </div>
                                        </div>
                                    </div>

                                    <p v-if="errors.image" class="col-span-4 mt-3 text-sm text-red-600">{{ errors.image }}</p>
                                </div>

                                <Wysiwyg
                                    class="mt-4 mb-4 block w-full lg:mb-0"
                                    name="content"
                                    :label="t('announcements.content')"
                                    :default-value="props.announcement?.content"
                                    :required="true"
                                    :error="errors.content"
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </Form>
        </LayoutBasic>
    </AppLayout>
</template>
