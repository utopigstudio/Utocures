<script setup lang="ts">
import { computed, watch } from 'vue';
import { ArrowLeft, ChevronRight, Folder } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import type { DocumentFolder } from '@/types';

const props = defineProps<{
    enabled?: boolean;
    folders: DocumentFolder[];
    items: { employee_document_folder_id?: string | null }[];
}>();
const selected = defineModel<string>({ default: '' });
const { t } = useI18n();
const current = computed(() => props.folders.find(folder => folder.id === selected.value));
const counts = computed(() => {
    const result: Record<string, number> = {};
    for (const item of props.items) {
        if (item.employee_document_folder_id) {
            result[item.employee_document_folder_id] = (result[item.employee_document_folder_id] ?? 0) + 1;
        }
    }
    return result;
});
watch(() => props.folders, folders => {
    if (selected.value && !folders.some(folder => folder.id === selected.value)) selected.value = '';
});
</script>

<template>
    <div>
        <nav v-if="enabled !== false" :aria-label="t('employee_documents.folder_navigation')" class="mb-4 flex items-center gap-2 border-b pb-3 text-sm">
            <button v-if="current" type="button" :aria-label="t('employee_documents.back_to_folders')" class="rounded-md p-2 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-blue-600" @click="selected = ''">
                <ArrowLeft class="size-4" />
            </button>
            <button type="button" class="flex items-center gap-2 rounded px-2 py-1 text-blue-700 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-blue-600" :aria-current="!current ? 'page' : undefined" @click="selected = ''">
                <Folder class="size-4" />
                {{ t('employee_documents.all_folders') }}
            </button>
            <template v-if="current">
                <ChevronRight class="size-4 shrink-0 text-gray-400" />
                <span aria-current="page" class="truncate font-medium text-gray-800">{{ current.name }}</span>
            </template>
        </nav>
        <div v-if="enabled !== false && !current" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <button v-for="folder in folders" :key="folder.id" type="button" class="flex min-w-0 items-center gap-3 rounded-lg border p-4 text-left transition-colors hover:border-blue-300 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-blue-600" @click="selected = folder.id">
                <Folder class="size-9 shrink-0 fill-amber-100 text-amber-500" />
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-medium text-gray-800">{{ folder.name }}</span>
                    <span class="text-xs text-gray-500">{{ t('employee_documents.folder_file_count', { count: counts[folder.id] ?? 0 }) }}</span>
                </span>
                <ChevronRight class="size-4 shrink-0 text-gray-400" />
            </button>
            <p v-if="!folders.length" class="py-6 text-sm text-gray-500">{{ t('employee_documents.no_folders') }}</p>
        </div>
        <slot v-else />
    </div>
</template>
