<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { AppPageProps, Work } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Clock } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    work: Work;
    hasActiveWork?: boolean;
    size?: 'small' | 'large';
}

const props = defineProps<Props>();
const page = usePage<AppPageProps>();
const { t } = useI18n();

const emit = defineEmits(['recording-finished']);

const isRecording = ref(false);
const elapsed = ref(0);
const isSubmitting = ref(false);
const locationError = ref<string | null>(null);
const workId = props.size === 'small' ? props.work.assigned_hour?.id : props.work.id;
let interval: ReturnType<typeof setInterval> | null = null;

const startTimeKey = `work_start_time_${workId}`;
const locationRequired = computed(() => page.props.app_configuration?.require_time_record_location === true);

const formattedTime = computed(() => {
    const h = String(Math.floor(elapsed.value / 3600)).padStart(2, '0');
    const m = String(Math.floor((elapsed.value % 3600) / 60)).padStart(2, '0');
    const s = String(elapsed.value % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
});

function getCurrentLocation(): Promise<GeolocationPosition> {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error(t('employee_time_records.location_not_supported')));
            return;
        }

        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0,
        });
    });
}

function locationErrorMessage(error: unknown) {
    if (typeof error === 'object' && error && 'code' in error) {
        const code = Number(error.code);

        if (code === 1) {
            return t('employee_time_records.location_permission_required');
        }

        if (code === 2) {
            return t('employee_time_records.location_unavailable');
        }

        if (code === 3) {
            return t('employee_time_records.location_timeout');
        }
    }

    if (error instanceof Error) {
        return error.message;
    }

    return t('employee_time_records.location_error');
}

async function resolveLocation() {
    if (!locationRequired.value) {
        return null;
    }

    const position = await getCurrentLocation();

    return {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy_meters: position.coords.accuracy,
        position_captured_at: new Date(position.timestamp).toISOString(),
    };
}

async function checkTimeRecord() {
    try {
        const res = await fetch(route('employee.time-record.check', { work: workId }));
        const data = await res.json();

        if (!data.time_record) {
            resetTimer();
        } else if (!isRecording.value) {
            runTimer(normalizeTimeRecordDateTime(data.time_record.date_in, data.time_record.time_in).getTime());
        }
    } catch (err) {
        console.error('Error fetching clients:', err);
    }
}

async function sendTimeRecord() {
    if (isSubmitting.value) return;

    isSubmitting.value = true;
    locationError.value = null;

    let location = null;

    try {
        location = await resolveLocation();
    } catch (err) {
        locationError.value = locationErrorMessage(err);
        isSubmitting.value = false;
        return;
    }

    router.post(
        route('employee.time-record.store', { work: workId }),
        {
            location,
        },
        {
            onSuccess: () => {
                if (isRecording.value) {
                    resetTimer();
                    emit('recording-finished');
                } else {
                    checkTimeRecord();
                }
            },
            onError: (errors) => {
                console.error('Failed to log work session', errors);
                locationError.value = Object.values(errors)[0] ?? t('employee_time_records.time_record_save_error');
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

function normalizeTimeRecordDateTime(date: string, time: string) {
    const [hour, minute, second = 0] = time.split(':').map(Number);

    if (date.includes('-')) {
        const parts = date.split('-').map(Number);

        if (parts[0] > 31) {
            const [year, month, day] = parts;

            return new Date(year, month - 1, day, hour, minute, second);
        }

        const [day, month, year] = parts;

        return new Date(year, month - 1, day, hour, minute, second);
    }

    const [day, month, year] = date.split('/').map(Number);

    return new Date(year, month - 1, day, hour, minute, second);
}

function resetTimer() {
    if (interval !== null) {
        clearInterval(interval);
        interval = null;
    }

    isRecording.value = false;
    localStorage.removeItem(startTimeKey);
    localStorage.removeItem(`${startTimeKey}_elapsed`);
    elapsed.value = 0;
}

function runTimer(start: number) {
    if (interval !== null) {
        clearInterval(interval);
    }

    localStorage.setItem(startTimeKey, start.toString());
    isRecording.value = true;

    const updateElapsed = () => {
        const newElapsed = Math.max(0, Math.floor((Date.now() - start) / 1000));
        elapsed.value = newElapsed;
        localStorage.setItem(`${startTimeKey}_elapsed`, newElapsed.toString());
    };

    updateElapsed();
    interval = setInterval(updateElapsed, 1000);
}

function toggleRecording() {
    sendTimeRecord();
}

onMounted(() => {
    checkTimeRecord();
});

onUnmounted(() => {
    if (interval !== null) {
        clearInterval(interval);
    }
});
</script>

<template>
    <div v-if="size === 'small'" class="mx-auto my-4 text-center">
        <div class="mb-2 text-xs">{{ work.assigned_hour?.service.name }} - {{ work.assigned_hour?.client.name }}</div>
        <div class="flex flex-row items-center justify-center gap-4">
            <Button variant="destructive" size="sm" :disabled="isSubmitting" @click.prevent="toggleRecording">
                <Clock class="size-4" />
                <span class="text-xs">{{ isSubmitting ? $t('employee_time_records.getting_location') : $t('employee_time_records.finish') }}</span>
            </Button>
            <div class="text-red-600">
                {{ formattedTime }}
            </div>
        </div>
        <p v-if="locationError" class="mt-2 text-xs text-red-600">{{ locationError }}</p>
    </div>
    <div v-else class="container mx-auto flex flex-col items-center gap-11 bg-white px-4 pt-6 pb-4">
        <div class="text-3xl leading-9 font-bold text-blue-gray-500">
            {{ formattedTime }}
        </div>

        <div v-if="!isRecording" class="w-full">
            <Button size="lg" class="w-full" @click.prevent="toggleRecording" :disabled="props.hasActiveWork || isSubmitting">
                <Clock class="size-4 font-semibold" />
                <span class="text-sm leading-5 font-semibold">{{ isSubmitting ? $t('employee_time_records.getting_location') : $t('employee_time_records.start_record') }}</span>
            </Button>
            <div v-if="props.hasActiveWork" class="mt-2 text-center text-sm leading-5 font-medium text-gray-600">
                {{ $t('employee_time_records.active_record_warning') }}
            </div>
            <p v-if="locationError" class="mt-2 text-center text-sm font-medium text-red-600">{{ locationError }}</p>
        </div>
        <div v-else class="w-full">
            <Button variant="destructive" size="lg" class="w-full" :disabled="isSubmitting" @click.prevent="toggleRecording">
                <Clock class="size-4 font-semibold" />
                <span class="text-sm leading-5 font-semibold">{{ isSubmitting ? $t('employee_time_records.getting_location') : $t('employee_time_records.finish_record') }}</span>
            </Button>
            <p v-if="locationError" class="mt-2 text-center text-sm font-medium text-red-600">{{ locationError }}</p>
        </div>
    </div>
</template>
