<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import InputError from '@/components/ui/input/InputError.vue';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useInitials } from '@/composables/useInitials';
import type { Employee } from '@/types';
import { useVModel } from '@vueuse/core';
import { AlertTriangle } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';

interface Props {
    name: string;
    assignedHourId?: string;
    label?: string;
    defaultValue?: string;
    modelValue?: string;
    eventId?: string;
    clientId?: string;
    serviceId?: string | undefined;
    timeStart?: string;
    timeEnd?: string;
    daysOfWeek?: (string | number)[];
    recurrency: string | number;
    dateStart?: string;
    dateEnd?: string;
    date?: string;
    error?: string;
}

const props = defineProps<Props>();
const employees = ref<Employee[]>([]);
const loading = ref(false);
const { getInitials } = useInitials();
let fetchRequestId = 0;

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});

onMounted(() => {
    if (canFetchEmployees()) {
        void fetchEmployees();
    }
});

onUnmounted(() => {
    fetchRequestId++;
});

function cleanEmployees() {
    fetchRequestId++;
    employees.value = [];
    loading.value = false;
}

const toApiDate = (value: string | Date) => {
    if (value instanceof Date) {
        return value.toISOString().slice(0, 10);
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return value;
    }

    if (/^\d{2}\/\d{2}\/\d{4}$/.test(value)) {
        const [d, m, y] = value.split('/');
        return `${y}-${m}-${d}`;
    }

    throw new Error('Formato de fecha no válido');
};

async function fetchEmployees() {
    const requestId = ++fetchRequestId;
    loading.value = true;

    try {
        const queryParams = new URLSearchParams();
        if (props.assignedHourId) queryParams.append('assigned_hour_id', props.assignedHourId.toString());
        if (props.eventId) queryParams.append('event_id', props.eventId.toString());
        if (props.timeStart) queryParams.append('time_start', props.timeStart);
        if (props.timeEnd) queryParams.append('time_end', props.timeEnd);
        if (props.date) queryParams.append('date', toApiDate(props.date));
        if (props.dateStart) queryParams.append('date_start', toApiDate(props.dateStart));
        if (props.dateEnd) queryParams.append('date_end', toApiDate(props.dateEnd));
        if (props.serviceId) queryParams.append('service_id', props.serviceId.toString());
        queryParams.append('recurrency', props.recurrency.toString());

        if (props.daysOfWeek && props.daysOfWeek.length > 0) {
            props.daysOfWeek.forEach((day) => {
                queryParams.append('days_of_week[]', day.toString());
            });
        }

        const queryString = queryParams.toString();
        const url = `/api/employees/options${queryString ? `?${queryString}` : ''}`;

        const res = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();

        if (requestId !== fetchRequestId) {
            return;
        }

        employees.value = data.data || [];

        if (employees.value.find((e) => e.id === modelValue.value) === undefined) {
            modelValue.value = '';
        }
    } catch (err) {
        if (requestId !== fetchRequestId) {
            return;
        }

        console.error('Error fetching employees:', err);
        employees.value = [];
    } finally {
        if (requestId === fetchRequestId) {
            loading.value = false;
        }
    }
}

function canFetchEmployees() {
    const allFilled = [props.timeStart, props.timeEnd, props.serviceId].every((v) => {
        if (Array.isArray(v)) return v.length > 0;

        return v !== null && v !== undefined && v !== '';
    });

    return allFilled && !!(props.date || props.daysOfWeek?.length);
}

watch(
    () => [
        props.timeStart,
        props.timeEnd,
        props.recurrency,
        props.assignedHourId,
        props.eventId,
        props.daysOfWeek,
        props.serviceId,
        props.date,
        props.dateStart,
        props.dateEnd,
    ],
    () => {
        if (canFetchEmployees()) {
            void fetchEmployees();
        } else {
            cleanEmployees();
        }
    },
    { deep: true },
);
</script>

<template>
    <label v-if="props.label" class="mr-4 text-lg">{{ props.label }}</label>
    <div class="mt-4">
        <div class="max-h-100 overflow-y-auto bg-white">
            <table class="mt-6 w-full text-left text-sm leading-5 font-normal whitespace-nowrap text-blue-gray-800">
                <tbody v-if="employees.length" class="divide-y divide-blue-gray-200">
                    <tr v-for="item in employees" :key="item.id">
                        <td class="py-4 pr-4 pl-0">
                            <div class="flex items-center gap-x-4">
                                <Avatar class="h-8 w-8 overflow-hidden rounded-full">
                                    <AvatarImage v-if="item.user.avatar" :src="item.user.avatar"></AvatarImage>
                                    <AvatarFallback v-else class="rounded-full bg-blue-gray-100 text-xs leading-4 font-bold text-black">
                                        {{ getInitials(item.user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="min-w-0">
                                    <div class="truncate">{{ item.user.name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="hidden py-4 pr-4 pl-0 sm:table-cell">
                            <div class="flex gap-x-3">
                                <div class="truncate">{{ item.phone }}</div>
                            </div>
                        </td>
                        <td class="py-4 pr-0 pl-0 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Tooltip v-if="item.unavailability_reason">
                                    <TooltipTrigger as-child>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-600 transition-colors hover:bg-amber-100 focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 focus-visible:outline-none"
                                            :aria-label="item.unavailability_reason"
                                        >
                                            <AlertTriangle class="size-4" />
                                        </button>
                                    </TooltipTrigger>
                                    <TooltipContent side="top" align="end" class="max-w-64 text-sm leading-5">
                                        {{ item.unavailability_reason }}
                                    </TooltipContent>
                                </Tooltip>

                                <Button
                                    variant="secondary"
                                    size="xs"
                                    type="button"
                                    @click="modelValue = item.id"
                                    class="font-medium"
                                    :class="[modelValue === item.id ? 'bg-blue-gray-600 text-white hover:bg-blue-gray-600/80' : '']"
                                >
                                    {{ modelValue === item.id ? $t('clients.service_assigned') : $t('clients.assign_service') }}
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td>
                            <div class="flex items-center">
                                <div>
                                    <span v-if="loading">{{ $t('clients.loading_employees') }}...</span>
                                    <span v-else>{{ $t('clients.no_employees_available') }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <InputError :message="props.error" />
</template>
