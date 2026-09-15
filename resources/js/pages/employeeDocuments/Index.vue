<script setup lang="ts">
import { Button, ButtonDelete } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { AppLayout, LayoutBasic } from '@/layouts';
import { Form } from '@inertiajs/vue3';
import { Download, FileText, FileUp, Search, Trash2, UploadCloud, UsersRound, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import type { EmployeeOption, DistributedDocument } from '@/types';

interface Props {
    employees: EmployeeOption[];
    documents: DistributedDocument[];
}

const props = defineProps<Props>();
const { t } = useI18n();

const selectedEmployeeIds = ref<string[]>(props.employees.map((employee) => employee.id));
const selectedFiles = ref<File[]>([]);
const search = ref('');
const isDragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const allEmployeesSelected = computed({
    get: () => props.employees.length > 0 && selectedEmployeeIds.value.length === props.employees.length,
    set: (selected: boolean) => {
        selectedEmployeeIds.value = selected ? props.employees.map((employee) => employee.id) : [];
    },
});

const filteredEmployees = computed(() => {
    const term = search.value.trim().toLocaleLowerCase();

    if (!term) {
        return props.employees;
    }

    return props.employees.filter((employee) => `${employee.name} ${employee.email}`.toLocaleLowerCase().includes(term));
});

function syncFileInput() {
    if (!fileInput.value) {
        return;
    }

    const transfer = new DataTransfer();
    selectedFiles.value.forEach((file) => transfer.items.add(file));
    fileInput.value.files = transfer.files;
}

function addFiles(files: FileList | File[]) {
    const existing = new Set(selectedFiles.value.map((file) => `${file.name}:${file.size}:${file.lastModified}`));

    for (const file of Array.from(files)) {
        const key = `${file.name}:${file.size}:${file.lastModified}`;

        if (!existing.has(key) && selectedFiles.value.length < 20) {
            selectedFiles.value.push(file);
            existing.add(key);
        }
    }

    syncFileInput();
}

function onFileChange(event: Event) {
    const files = (event.target as HTMLInputElement).files;

    if (files) {
        addFiles(files);
    }
}

function onDrop(event: DragEvent) {
    event.preventDefault();
    isDragOver.value = false;

    if (event.dataTransfer?.files) {
        addFiles(event.dataTransfer.files);
    }
}

function removeFile(index: number) {
    selectedFiles.value.splice(index, 1);
    syncFileInput();
}

function clearFiles() {
    selectedFiles.value = [];

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function documentError(errors: Record<string, string>): string | undefined {
    return errors.documents ?? Object.entries(errors).find(([key]) => key.startsWith('documents.'))?.[1];
}
</script>

<template>
    <AppLayout>
        <LayoutBasic :title="t('employee_documents.title')" :icon="FileUp">
            <p class="-mt-4 mb-6 px-4 text-sm text-gray-500 md:px-0">{{ t('employee_documents.description') }}</p>

            <Form
                method="post"
                :action="route('employee-documents.store')"
                enctype="multipart/form-data"
                :options="{ preserveScroll: true }"
                v-slot="{ errors, processing }"
                @success="clearFiles"
            >
                <input v-for="employeeId in selectedEmployeeIds" :key="employeeId" type="hidden" name="employee_ids[]" :value="employeeId" />

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-xl">
                                <FileText class="size-5 text-blue-600" />
                                {{ t('employee_documents.documents') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="flex min-h-56 justify-center rounded-lg border-2 border-dashed px-6 py-10 transition-colors"
                                :class="isDragOver ? 'border-blue-600 bg-blue-50' : 'border-gray-200'"
                                @dragover.prevent="isDragOver = true"
                                @dragleave.prevent="isDragOver = false"
                                @drop="onDrop"
                            >
                                <div class="text-center">
                                    <UploadCloud class="mx-auto size-12 text-gray-300" />
                                    <div class="mt-4 flex flex-wrap justify-center text-sm text-gray-600">
                                        <label for="employee-documents" class="cursor-pointer font-semibold text-blue-600 hover:text-blue-700">
                                            {{ t('employee_documents.select_files') }}
                                            <input
                                                id="employee-documents"
                                                ref="fileInput"
                                                class="sr-only"
                                                type="file"
                                                name="documents[]"
                                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                                multiple
                                                @change="onFileChange"
                                            />
                                        </label>
                                        <span class="pl-1">{{ t('employee_documents.or_drag_here') }}</span>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500">{{ t('employee_documents.documents_help') }}</p>
                                </div>
                            </div>

                            <p v-if="documentError(errors)" class="mt-3 text-sm text-red-600">{{ documentError(errors) }}</p>

                            <div v-if="selectedFiles.length" class="mt-5">
                                <p class="mb-2 text-sm font-semibold text-gray-800">{{ t('employee_documents.selected_files') }}</p>
                                <ul class="space-y-2">
                                    <li
                                        v-for="(file, index) in selectedFiles"
                                        :key="`${file.name}-${file.lastModified}`"
                                        class="flex items-center gap-3 rounded-lg bg-gray-50 px-3 py-2 text-sm"
                                    >
                                        <FileText class="size-4 shrink-0 text-blue-600" />
                                        <span class="min-w-0 flex-1 truncate text-gray-800">{{ file.name }}</span>
                                        <span class="shrink-0 text-xs text-gray-500">{{ (file.size / 1024 / 1024).toFixed(2) }} MB</span>
                                        <button
                                            type="button"
                                            class="rounded p-1 text-gray-500 hover:bg-gray-200 hover:text-red-700"
                                            :aria-label="t('employee_documents.remove_file', { name: file.name })"
                                            @click="removeFile(index)"
                                        >
                                            <X class="size-4" />
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex flex-wrap items-center gap-2 text-xl">
                                <UsersRound class="size-5 text-blue-600" />
                                <span>{{ t('employee_documents.employees') }}</span>
                                <span class="ml-auto text-sm font-normal text-gray-500">
                                    {{ t('employee_documents.selected_count', { selected: selectedEmployeeIds.length, total: employees.length }) }}
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <label class="mb-4 flex cursor-pointer items-center gap-3 rounded-lg bg-blue-50 px-4 py-3 font-medium text-gray-800">
                                <input v-model="allEmployeesSelected" type="checkbox" class="size-4 rounded border-gray-300 text-blue-600" />
                                {{ t('employee_documents.select_all') }}
                            </label>

                            <div class="relative mb-3">
                                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-500" />
                                <input
                                    v-model="search"
                                    type="search"
                                    class="h-10 w-full rounded-lg border border-gray-200 bg-white pr-3 pl-10 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                                    :placeholder="t('employee_documents.search_employee')"
                                />
                            </div>

                            <div v-if="employees.length" class="max-h-72 space-y-1 overflow-y-auto pr-1">
                                <label
                                    v-for="employee in filteredEmployees"
                                    :key="employee.id"
                                    class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-50"
                                >
                                    <input
                                        v-model="selectedEmployeeIds"
                                        type="checkbox"
                                        :value="employee.id"
                                        class="size-4 rounded border-gray-300 text-blue-600"
                                    />
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-gray-800">{{ employee.name }}</span>
                                        <span class="block truncate text-xs text-gray-500">{{ employee.email }}</span>
                                    </span>
                                </label>
                                <p v-if="!filteredEmployees.length" class="py-8 text-center text-sm text-gray-500">
                                    {{ t('employee_documents.no_employee_results') }}
                                </p>
                            </div>
                            <p v-else class="py-8 text-center text-sm text-gray-500">{{ t('employee_documents.no_employees') }}</p>

                            <p v-if="errors.employee_ids" class="mt-3 text-sm text-red-600">{{ errors.employee_ids }}</p>
                        </CardContent>
                    </Card>
                </div>

                <div class="mt-6 flex justify-end">
                    <Button type="submit" size="lg" :disabled="processing || !selectedFiles.length || !selectedEmployeeIds.length">
                        <FileUp class="size-4" />
                        {{ processing ? t('employee_documents.distributing') : t('employee_documents.distribute') }}
                    </Button>
                </div>
            </Form>

            <Card class="mt-8">
                <CardHeader>
                    <CardTitle class="text-xl">{{ t('employee_documents.history') }}</CardTitle>
                    <p class="text-sm text-gray-500">{{ t('employee_documents.history_description') }}</p>
                </CardHeader>
                <CardContent class="overflow-x-auto px-0 md:px-6">
                    <table v-if="documents.length" class="w-full min-w-2xl text-left text-sm">
                        <thead class="border-b bg-gray-50 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-3">{{ t('employee_documents.name') }}</th>
                                <th class="px-4 py-3">{{ t('employee_documents.date') }}</th>
                                <th class="px-4 py-3 text-center">{{ t('employee_documents.recipients') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('employee_documents.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="document in documents" :key="document.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">
                                    <span class="flex items-center gap-2"><FileText class="size-4 text-blue-600" />{{ document.name }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ document.created_at }}</td>
                                <td class="px-4 py-3 text-center text-gray-800">{{ document.employee_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a
                                            :href="route('employee-documents.download', document.id)"
                                            class="inline-flex size-9 items-center justify-center rounded-md text-blue-600 hover:bg-blue-50"
                                            :aria-label="t('employee_documents.download', { name: document.name })"
                                            download
                                        >
                                            <Download class="size-4" />
                                        </a>
                                        <ButtonDelete
                                            :action="route('employee-documents.destroy', document.id)"
                                            variant="icon"
                                            size="icon"
                                            class="size-9 text-red-700 hover:bg-red-50"
                                            :aria-label="t('employee_documents.delete', { name: document.name })"
                                        >
                                            <Trash2 class="size-4" />
                                        </ButtonDelete>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="py-12 text-center text-sm text-gray-500">
                        <FileText class="mx-auto mb-3 size-10 text-gray-300" />
                        {{ t('employee_documents.empty') }}
                    </div>
                </CardContent>
            </Card>
        </LayoutBasic>
    </AppLayout>
</template>
