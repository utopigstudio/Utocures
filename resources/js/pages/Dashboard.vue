<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Datepicker, Select } from '@/components/ui/input';
import { AppLayout } from '@/layouts';
import type { Announcement } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, Clock3, Megaphone, UserRoundCheck, UsersRound } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

type HoursPeriod = 'current_month' | 'previous_month' | 'current_quarter' | 'current_year' | 'previous_year' | 'month' | 'year' | 'custom';

interface Props {
    clients_count: number;
    employees_count: number;
    announcements: Announcement[];
    hours_stats: {
        worked_minutes: number;
    };
    hours_filter: {
        period: HoursPeriod;
        year: number;
        month: number;
        from: string;
        to: string;
        min_year: number;
        max_year: number;
    };
}

const props = defineProps<Props>();
const { locale, t } = useI18n();

const selectedPeriod = ref<HoursPeriod>(props.hours_filter.period);
const selectedYear = ref(String(props.hours_filter.year));
const selectedMonth = ref(String(props.hours_filter.month));
const customFrom = ref<string | null>(props.hours_filter.from);
const customTo = ref<string | null>(props.hours_filter.to);
const isLoadingHours = ref(false);
const selectedAnnouncement = ref<Announcement | null>(null);
const detailOpen = ref(false);

const localeCode = computed(() => (locale.value === 'ca' ? 'ca-ES' : 'es-ES'));

const periodOptions = computed(() => [
    { value: 'current_month', label: t('dashboard.period_current_month') },
    { value: 'previous_month', label: t('dashboard.period_previous_month') },
    { value: 'current_quarter', label: t('dashboard.period_current_quarter') },
    { value: 'current_year', label: t('dashboard.period_current_year') },
    { value: 'previous_year', label: t('dashboard.period_previous_year') },
    { value: 'month', label: t('dashboard.period_month') },
    { value: 'year', label: t('dashboard.period_year') },
    { value: 'custom', label: t('dashboard.period_custom') },
]);

const yearOptions = computed(() =>
    Array.from({ length: props.hours_filter.max_year - props.hours_filter.min_year + 1 }, (_, index) => {
        const year = props.hours_filter.max_year - index;

        return { value: String(year), label: String(year) };
    }),
);

const monthOptions = computed(() =>
    Array.from({ length: 12 }, (_, index) => ({
        value: String(index + 1),
        label: new Intl.DateTimeFormat(localeCode.value, { month: 'long' }).format(new Date(2024, index, 1)),
    })),
);

const periodLabel = computed(() => {
    const from = formatDate(props.hours_filter.from);
    const to = formatDate(props.hours_filter.to);

    return from === to ? from : `${from} – ${to}`;
});

const customRangeIsValid = computed(() => Boolean(customFrom.value && customTo.value) && customFrom.value! <= customTo.value!);

function formatNumber(value: number) {
    return new Intl.NumberFormat(localeCode.value).format(value);
}

function formatWorkedMinutes(totalMinutes: number) {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    if (hours === 0 && minutes === 0) {
        return `0 ${t('dashboard.hours_short')}`;
    }

    return minutes === 0
        ? `${formatNumber(hours)} ${t('dashboard.hours_short')}`
        : `${formatNumber(hours)} ${t('dashboard.hours_short')} ${minutes} ${t('dashboard.minutes_short')}`;
}

function formatDate(value: string) {
    const [year, month, day] = value.split('-').map(Number);

    return new Intl.DateTimeFormat(localeCode.value, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(year, month - 1, day));
}

function formatAnnouncementDate(value?: string) {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(localeCode.value, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

function announcementExcerpt(content: string) {
    const plainText = content
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    return plainText.length <= 180 ? plainText : `${plainText.slice(0, 180).trim()}…`;
}

function openAnnouncement(announcement: Announcement) {
    selectedAnnouncement.value = announcement;
    detailOpen.value = true;
}

function closeAnnouncement() {
    detailOpen.value = false;
    selectedAnnouncement.value = null;
}

function loadHours() {
    if (selectedPeriod.value === 'custom' && !customRangeIsValid.value) {
        return;
    }

    const params: Record<string, string> = {
        hours_period: selectedPeriod.value,
    };

    if (selectedPeriod.value === 'month' || selectedPeriod.value === 'year') {
        params.hours_year = selectedYear.value;
    }

    if (selectedPeriod.value === 'month') {
        params.hours_month = selectedMonth.value;
    }

    if (selectedPeriod.value === 'custom') {
        params.hours_from = customFrom.value!;
        params.hours_to = customTo.value!;
    }

    router.get(route('dashboard'), params, {
        only: ['hours_stats', 'hours_filter'],
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onStart: () => {
            isLoadingHours.value = true;
        },
        onFinish: () => {
            isLoadingHours.value = false;
        },
    });
}

function selectPeriod(value: string) {
    selectedPeriod.value = value as HoursPeriod;

    if (value !== 'custom') {
        loadHours();
    }
}

function selectYear(value: string) {
    selectedYear.value = value;
    loadHours();
}

function selectMonth(value: string) {
    selectedMonth.value = value;
    loadHours();
}
</script>

<template>
    <AppLayout>
        <div class="flex h-full min-h-[100vh] flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <div class="grid items-stretch gap-4 md:grid-cols-3">
                <Card class="gap-4 border-blue-200">
                    <CardHeader :border="false" class="grid grid-cols-[1fr_auto] items-center">
                        <CardTitle class="text-sm font-medium text-blue-gray-600">{{ $t('dashboard.clients') }}</CardTitle>
                        <div class="rounded-xl bg-blue-100 p-2.5 text-blue-700">
                            <UsersRound class="size-5" />
                        </div>
                    </CardHeader>
                    <CardContent class="flex-1">
                        <p class="text-4xl font-bold tracking-tight text-blue-gray-900">{{ formatNumber(clients_count) }}</p>
                        <p class="mt-2 text-sm text-blue-gray-500">{{ $t('dashboard.clients_description') }}</p>
                    </CardContent>
                    <CardFooter>
                        <Link
                            :href="route('clients.index')"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-900"
                        >
                            {{ $t('dashboard.view_clients') }}
                            <ArrowRight class="size-4" />
                        </Link>
                    </CardFooter>
                </Card>

                <Card class="gap-4 border-emerald-200">
                    <CardHeader :border="false" class="grid grid-cols-[1fr_auto] items-center">
                        <CardTitle class="text-sm font-medium text-blue-gray-600">{{ $t('dashboard.employees') }}</CardTitle>
                        <div class="rounded-xl bg-emerald-100 p-2.5 text-emerald-700">
                            <UserRoundCheck class="size-5" />
                        </div>
                    </CardHeader>
                    <CardContent class="flex-1">
                        <p class="text-4xl font-bold tracking-tight text-blue-gray-900">{{ formatNumber(employees_count) }}</p>
                        <p class="mt-2 text-sm text-blue-gray-500">{{ $t('dashboard.employees_description') }}</p>
                    </CardContent>
                    <CardFooter>
                        <Link
                            :href="route('employees.index')"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-900"
                        >
                            {{ $t('dashboard.view_employees') }}
                            <ArrowRight class="size-4" />
                        </Link>
                    </CardFooter>
                </Card>

                <Card class="gap-4 border-amber-200">
                    <CardHeader :border="false" class="grid grid-cols-[1fr_auto] items-center">
                        <CardTitle class="text-sm font-medium text-blue-gray-600">{{ $t('dashboard.worked_hours') }}</CardTitle>
                        <div class="rounded-xl bg-amber-100 p-2.5 text-amber-700">
                            <Clock3 class="size-5" />
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col gap-4">
                        <div aria-live="polite" :class="{ 'opacity-50': isLoadingHours }">
                            <p class="text-3xl font-bold tracking-tight text-blue-gray-900">{{ formatWorkedMinutes(hours_stats.worked_minutes) }}</p>
                            <p class="mt-2 text-sm text-blue-gray-500">{{ periodLabel }}</p>
                        </div>

                        <Select
                            name="hours_period"
                            :model-value="selectedPeriod"
                            :options="periodOptions"
                            :label="$t('dashboard.period')"
                            container-class="w-full"
                            class="w-full"
                            @update:model-value="selectPeriod"
                        />

                        <div v-if="selectedPeriod === 'month'" class="grid grid-cols-2 gap-2">
                            <Select
                                name="hours_month"
                                :model-value="selectedMonth"
                                :options="monthOptions"
                                :label="$t('dashboard.month')"
                                container-class="min-w-0"
                                class="w-full"
                                @update:model-value="selectMonth"
                            />
                            <Select
                                name="hours_year"
                                :model-value="selectedYear"
                                :options="yearOptions"
                                :label="$t('dashboard.year')"
                                container-class="min-w-0"
                                class="w-full"
                                @update:model-value="selectYear"
                            />
                        </div>

                        <Select
                            v-else-if="selectedPeriod === 'year'"
                            name="hours_year"
                            :model-value="selectedYear"
                            :options="yearOptions"
                            :label="$t('dashboard.year')"
                            container-class="w-full"
                            class="w-full"
                            @update:model-value="selectYear"
                        />

                        <div v-else-if="selectedPeriod === 'custom'" class="grid gap-3">
                            <div class="grid grid-cols-2 gap-2">
                                <Datepicker v-model="customFrom" name="hours_from" :label="$t('dashboard.from')" class="w-full" />
                                <Datepicker
                                    v-model="customTo"
                                    name="hours_to"
                                    :label="$t('dashboard.to')"
                                    :min-date="customFrom ?? undefined"
                                    class="w-full"
                                />
                            </div>
                            <Button class="w-full" :disabled="!customRangeIsValid || isLoadingHours" @click="loadHours">
                                {{ $t('dashboard.apply_filter') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card class="gap-4 border-violet-200">
                <CardHeader :border="false" class="grid grid-cols-[1fr_auto] items-center gap-4">
                    <div>
                        <CardTitle class="text-lg font-semibold text-blue-gray-900">{{ $t('dashboard.admin_announcements') }}</CardTitle>
                        <p class="mt-1 text-sm text-blue-gray-500">{{ $t('dashboard.admin_announcements_description') }}</p>
                    </div>
                    <div class="rounded-xl bg-violet-100 p-2.5 text-violet-700">
                        <Megaphone class="size-5" />
                    </div>
                </CardHeader>

                <CardContent>
                    <div
                        v-if="announcements.length"
                        class="max-h-96 divide-y divide-slate-200 overflow-x-hidden overflow-y-auto rounded-xl border border-slate-200"
                    >
                        <button
                            v-for="announcement in announcements"
                            :key="announcement.id"
                            type="button"
                            class="grid w-full gap-4 p-4 text-left transition-colors hover:bg-slate-50 sm:grid-cols-[5rem_1fr_auto] sm:items-center"
                            @click="openAnnouncement(announcement)"
                        >
                            <img
                                v-if="announcement.image"
                                :src="announcement.image"
                                :alt="announcement.title"
                                class="h-16 w-20 rounded-lg object-cover"
                            />
                            <div v-else class="flex h-16 w-20 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                <Megaphone class="size-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ announcement.title }}</p>
                                <p class="mt-1 line-clamp-2 text-sm leading-5 text-slate-600">
                                    {{ announcementExcerpt(announcement.content) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 text-xs whitespace-nowrap text-slate-500">
                                <CalendarDays class="size-4 text-violet-600" />
                                {{ formatAnnouncementDate(announcement.created_at) }}
                            </div>
                        </button>
                    </div>

                    <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        {{ $t('dashboard.no_admin_announcements') }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog
            v-model:open="detailOpen"
            @update:open="
                (open) => {
                    if (!open) closeAnnouncement();
                }
            "
        >
            <DialogContent class="max-h-[88vh] w-full max-w-4xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-xl font-bold text-slate-900">
                        {{ selectedAnnouncement?.title }}
                    </DialogTitle>
                    <DialogDescription>{{ $t('announcements.detail_description') }}</DialogDescription>
                </DialogHeader>

                <div v-if="selectedAnnouncement" class="space-y-4">
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <CalendarDays class="size-4 text-violet-600" />
                        <span> {{ $t('announcements.published_at') }}: {{ formatAnnouncementDate(selectedAnnouncement.created_at) }} </span>
                    </div>

                    <img
                        v-if="selectedAnnouncement.image"
                        :src="selectedAnnouncement.image"
                        :alt="selectedAnnouncement.title"
                        class="max-h-80 w-full rounded-2xl object-cover"
                    />

                    <div
                        class="wysiwyg-content prose prose-sm max-w-none text-slate-700"
                        v-html="selectedAnnouncement.content"
                    />
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button">{{ $t('announcements.close_detail') }}</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
