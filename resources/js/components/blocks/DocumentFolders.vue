<script setup lang="ts">
import { ref } from 'vue';
import { Form } from '@inertiajs/vue3';
import { Button, ButtonDelete } from '@/components/ui/button';
import type { DocumentFolder } from '@/types';
import { useI18n } from 'vue-i18n';

defineProps<{ folders: DocumentFolder[] }>();
const { t } = useI18n();
const open = ref(false);
</script>

<template>
    <div class="mb-6 rounded-lg border p-4">
        <Button type="button" variant="outline" @click="open = !open">{{ t('employee_documents.manage_folders') }}</Button>
        <div v-if="open" class="mt-4 space-y-3">
            <p class="text-sm text-gray-500">{{ t('employee_documents.shared_folders') }}</p>
            <Form v-for="folder in folders" :key="folder.id" method="put" :action="route('employee-document-folders.update', folder.id)" :options="{ preserveScroll: true }" v-slot="{ errors, processing }" class="flex flex-wrap items-center gap-2">
                <input name="name" :value="folder.name" :aria-label="t('employee_documents.folder')" required maxlength="100" class="rounded-md border px-3 py-2" />
                <Button type="submit" variant="outline" :disabled="processing">{{ t('employee_documents.rename_folder') }}</Button>
                <ButtonDelete :action="route('employee-document-folders.destroy', folder.id)">{{ t('employee_documents.delete_folder') }}</ButtonDelete>
                <p v-if="errors.name" class="w-full text-sm text-red-600">{{ errors.name }}</p>
            </Form>
            <Form method="post" :action="route('employee-document-folders.store')" :options="{ preserveScroll: true }" reset-on-success v-slot="{ errors, processing }" class="flex flex-wrap items-center gap-2">
                <input name="name" :placeholder="t('employee_documents.folder_name')" :aria-label="t('employee_documents.folder_name')" required maxlength="100" class="rounded-md border px-3 py-2" />
                <Button type="submit" :disabled="processing">{{ t('employee_documents.create_folder') }}</Button>
                <p v-if="errors.name" class="w-full text-sm text-red-600">{{ errors.name }}</p>
            </Form>
        </div>
        <p v-if="$page.props.errors.folder" role="alert" class="mt-2 text-sm text-red-600">{{ $page.props.errors.folder }}</p>
    </div>
</template>
