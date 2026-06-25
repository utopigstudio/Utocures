<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Work } from '@/types';
import { router } from '@inertiajs/vue3';
import { Clock } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    work: Work;
    hasActiveWork?: boolean;
    size?: 'small' | 'large';
}

const props = defineProps<Props>();

const emit = defineEmits(['recording-finished']);

const isRecording = ref(false);
const elapsed = ref(0);
const workId = props.size === 'small' ? props.work.assigned_hour?.id : props.work.id;
let interval: ReturnType<typeof setInterval> | null = null;

const startTimeKey = `work_start_time_${workId}`;

const formattedTime = computed(() => {
    const h = String(Math.floor(elapsed.value / 3600)).padStart(2, '0');
    const m = String(Math.floor((elapsed.value % 3600) / 60)).padStart(2, '0');
    const s = String(elapsed.value % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
});

function formatTime(date: Date) {
    return date.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
}

async function checkTimeRecord() {
    try {
        const res = await fetch(route('employee.time-record.check', { work: workId }));
        const data = await res.json();

        if (!data.time_record) {
            resetTimer();
        } else if (!isRecording.value) {
            const [day, month, year] = data.time_record.date_in.split('-').map(Number);
            const [hour, minute] = data.time_record.time_in.split(':').map(Number);

            runTimer(new Date(year, month - 1, day, hour, minute).getTime());
        }
    } catch (err) {
        console.error('Error fetching clients:', err);
    }
}

function sendTimeRecord(time: number, type: 'in' | 'out' = 'in') {
    let formatted_date_in = null;
    let formatted_date_out = null;
    let formatted_start_time = null;
    let formatted_end_time = null;

    if (time !== null) {
        if (type === 'in') {
            const startDate = new Date(time);
            formatted_start_time = formatTime(startDate);
            formatted_date_in = startDate.toISOString().split('T')[0];
        }

        if (type === 'out') {
            const endDate = new Date(time);
            formatted_end_time = formatTime(endDate);
            formatted_date_out = endDate.toISOString().split('T')[0];
        }

        router.post(
            route('employee.time-record.store', { work: workId }),
            {
                date_in: formatted_date_in,
                date_out: formatted_date_out,
                time_in: formatted_start_time,
                time_out: formatted_end_time,
            },
            {
                onSuccess: () => {
                    if (time !== null) {
                        if (type === 'out') {
                            resetTimer();
                            emit('recording-finished');
                        } else {
                            runTimer(time);
                        }
                    }
                },
                onError: (errors) => {
                    console.error('Failed to log work session', errors);
                },
            },
        );
    }
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
    if (isRecording.value) {
        sendTimeRecord(Date.now(), 'out');
    } else {
        sendTimeRecord(Date.now());
    }
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
            <Button variant="destructive" size="sm" @click.prevent="toggleRecording">
                <Clock class="size-4" />
                <span class="text-xs">{{ 'Finalizar' }}</span>
            </Button>
            <div class="text-red-600">
                {{ formattedTime }}
            </div>
        </div>
    </div>
    <div v-else class="container mx-auto flex flex-col items-center gap-11 bg-white px-4 pt-6 pb-4">
        <div class="text-3xl leading-9 font-bold text-blue-gray-500">
            {{ formattedTime }}
        </div>

        <div v-if="!isRecording" class="w-full">
            <Button size="lg" class="w-full" @click.prevent="toggleRecording" :disabled="props.hasActiveWork">
                <Clock class="size-4 font-semibold" />
                <span class="text-sm leading-5 font-semibold">{{ 'Inicia registro' }}</span>
            </Button>
            <div v-if="props.hasActiveWork" class="mt-2 text-center text-sm leading-5 font-medium text-gray-600">
                {{ 'Ya tienes un registro activo, debes finalizarlo antes de comenzar uno nuevo.' }}
            </div>
        </div>
        <div v-else class="w-full">
            <Button variant="destructive" size="lg" class="w-full" @click.prevent="toggleRecording">
                <Clock class="size-4 font-semibold" />
                <span class="text-sm leading-5 font-semibold">{{ 'Finalizar registro' }}</span>
            </Button>
        </div>
    </div>
</template>
