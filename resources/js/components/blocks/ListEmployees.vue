<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import InputError from '@/components/ui/input/InputError.vue';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useInitials } from '@/composables/useInitials';
import type { Employee } from '@/types';
import { useVModel } from '@vueuse/core';
import { AlertTriangle, ArrowUpRight, CalendarDays, ChevronDown, Clock3 } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

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
const { t } = useI18n();
const dayKeys = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
const unavailableDayNames = (employee: EmployeeOption) => (employee.unavailable_days ?? []).map((day) => t(`layout.${dayKeys[day]}`)).join(', ');
interface EmployeeOption extends Employee {
  outside_availability?: boolean;
  unavailable_days?: number[];
  conflicts?: {
    id: string;
    type: string;
    client_id: string | null;
    client_name: string | null;
    service_name: string | null;
    date: string;
    time_start: string;
    time_end: string;
  }[];
}
const employees = ref<EmployeeOption[]>([]);
const unavailableEmployees = ref<EmployeeOption[]>([]);
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
  unavailableEmployees.value = [];
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
  const previousSelection = modelValue.value;
  loading.value = true;
  employees.value = [];
  unavailableEmployees.value = [];
  modelValue.value = '';

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
    unavailableEmployees.value = data.unavailable || [];

    if (employees.value.some((employee) => employee.id === previousSelection && employee.is_selectable !== false)) {
      modelValue.value = previousSelection;
    }
  } catch (err) {
    if (requestId !== fetchRequestId) {
      return;
    }

    console.error('Error fetching employees:', err);
    employees.value = [];
    unavailableEmployees.value = [];
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
  <div>
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
    <details
      v-if="unavailableEmployees.length"
      class="group mt-6 overflow-hidden rounded-xl border border-blue-gray-200 bg-white"
      :open="employees.length === 0"
    >
      <summary
        class="flex cursor-pointer list-none items-center gap-3 px-4 py-4 transition-colors hover:bg-blue-gray-50 focus-visible:outline-2 focus-visible:outline-blue-600 [&::-webkit-details-marker]:hidden"
      >
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
          <AlertTriangle class="size-4" aria-hidden="true" />
        </span>
        <span class="min-w-0 flex-1 text-sm font-semibold text-blue-gray-900">{{ $t('clients.unavailable_employees') }}</span>
        <span class="rounded-full bg-blue-gray-100 px-2.5 py-0.5 text-xs font-semibold text-blue-gray-600">{{ unavailableEmployees.length }}</span>
        <ChevronDown class="text-blue-gray-400 size-4 shrink-0 transition-transform group-open:rotate-180" aria-hidden="true" />
      </summary>
      <div class="border-t border-blue-gray-100">
        <p class="px-4 pt-3 pb-1 text-xs leading-5 text-blue-gray-500">{{ $t('clients.unavailable_employees_help') }}</p>
        <ul class="max-h-96 divide-y divide-blue-gray-100 overflow-y-auto">
          <li v-for="employee in unavailableEmployees" :key="employee.id" class="px-4 py-4">
            <div class="flex items-start gap-3">
              <Avatar class="size-9 shrink-0 overflow-hidden rounded-full">
                <AvatarImage v-if="employee.user.avatar" :src="employee.user.avatar" />
                <AvatarFallback class="bg-blue-gray-100 text-xs font-semibold text-blue-gray-600">{{
                  getInitials(employee.user.name)
                }}</AvatarFallback>
              </Avatar>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                  <p class="text-sm font-semibold break-words text-blue-gray-900">{{ employee.user.name }}</p>
                  <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                    {{ $t(employee.conflicts?.length ? 'clients.employee_schedule_conflict_label' : 'clients.employee_outside_availability_label') }}
                  </span>
                </div>
                <div v-if="employee.outside_availability" class="mt-2 flex items-start gap-2 text-sm leading-6 text-blue-gray-600">
                  <Clock3 class="text-blue-gray-400 mt-1 size-4 shrink-0" aria-hidden="true" />
                  <p>
                    {{
                      $t('clients.employee_outside_requested_hours', {
                        days: unavailableDayNames(employee),
                        start: props.timeStart,
                        end: props.timeEnd,
                      })
                    }}
                  </p>
                </div>
                <ul v-if="employee.conflicts?.length" class="mt-3 space-y-2">
                  <li
                    v-for="conflict in employee.conflicts"
                    :key="`${conflict.type}-${conflict.id}`"
                    class="rounded-lg border border-blue-gray-100 bg-blue-gray-50/50 p-3"
                  >
                    <div class="flex flex-wrap items-start justify-between gap-2">
                      <div class="min-w-0">
                        <p class="text-sm font-medium break-words text-blue-gray-900">{{ conflict.service_name }}</p>
                        <p class="mt-0.5 text-sm break-words text-blue-gray-500">{{ conflict.client_name }}</p>
                      </div>
                      <a
                        v-if="conflict.client_id"
                        :href="route('clients.show', conflict.client_id)"
                        target="_blank"
                        rel="noopener noreferrer"
                        :aria-label="
                          $t('clients.employee_schedule_conflict', {
                            service: conflict.service_name,
                            client: conflict.client_name,
                            date: conflict.date.split('-').reverse().join('/'),
                            start: conflict.time_start,
                            end: conflict.time_end,
                          }) +
                          ' ' +
                          $t('clients.view_conflicting_service')
                        "
                        class="inline-flex shrink-0 items-center gap-1 rounded-md px-1 py-0.5 text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline focus-visible:outline-2 focus-visible:outline-blue-600"
                      >
                        {{ $t('clients.view_conflicting_service') }}
                        <ArrowUpRight class="size-3.5" aria-hidden="true" />
                      </a>
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-blue-gray-600">
                      <span class="inline-flex items-center gap-1.5"
                        ><CalendarDays class="text-blue-gray-400 size-3.5" aria-hidden="true" /><time :datetime="conflict.date">{{
                          conflict.date.split('-').reverse().join('/')
                        }}</time></span
                      >
                      <span class="inline-flex items-center gap-1.5"
                        ><Clock3 class="text-blue-gray-400 size-3.5" aria-hidden="true" />{{ conflict.time_start }} &ndash;
                        {{ conflict.time_end }}</span
                      >
                    </div>
                  </li>
                </ul>
                <p v-if="employee.unavailability_reason" class="mt-2 text-xs leading-5 text-amber-700">{{ employee.unavailability_reason }}</p>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </details>
    <InputError :message="props.error" />
  </div>
</template>
