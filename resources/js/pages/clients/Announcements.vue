<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AppLayoutClient } from '@/layouts';
import type { Announcement } from '@/types';
import { Link } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, Megaphone } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
  announcements: Announcement[];
}

const props = defineProps<Props>();
const { locale, t } = useI18n();
const selectedAnnouncement = ref<Announcement | null>(null);
const detailOpen = ref(false);

const localeCode = computed(() => (locale.value === 'ca' ? 'ca-ES' : 'es-ES'));

function openDetail(announcement: Announcement) {
  selectedAnnouncement.value = announcement;
  detailOpen.value = true;
}

function closeDetail() {
  detailOpen.value = false;
  selectedAnnouncement.value = null;
}

function plainExcerpt(content: string) {
  return content
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function truncate(text: string, length = 180) {
  if (text.length <= length) {
    return text;
  }

  return `${text.slice(0, length).trim()}…`;
}

function formatDate(date?: string) {
  if (!date) {
    return '';
  }

  return new Intl.DateTimeFormat(localeCode.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(new Date(date));
}
</script>

<template>
  <AppLayoutClient>
    <div class="container mx-auto flex items-center gap-2 bg-gray-50 px-4 pt-0 pb-4">
      <Link :href="route('dashboard')" class="flex h-auto cursor-pointer items-center justify-start gap-2 p-0">
        <ChevronLeft class="size-5" />
        <Megaphone class="size-5 text-blue-600" />
        <h1 class="text-lg leading-7 font-bold text-gray-800">{{ t('announcements.board_title') }}</h1>
      </Link>
    </div>

    <div class="container mx-auto space-y-4 px-4 pb-6">
      <div v-if="props.announcements.length" class="space-y-4">
        <article
          v-for="announcement in props.announcements"
          :key="announcement.id"
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
          <div class="flex flex-col gap-0 lg:flex-row">
            <img
              v-if="announcement.image"
              :src="announcement.image"
              :alt="announcement.title"
              class="h-48 w-full object-cover lg:h-auto lg:w-64 lg:shrink-0"
            />

            <div class="flex flex-1 flex-col justify-between p-5 sm:p-6">
              <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                  <CalendarDays class="size-4 text-blue-600" />
                  <span>{{ t('announcements.published_at') }}: {{ formatDate(announcement.created_at) }}</span>
                </div>

                <h2 class="text-lg font-semibold text-slate-900">{{ announcement.title }}</h2>

                <p class="text-sm leading-6 text-slate-600">
                  {{ truncate(plainExcerpt(announcement.content)) }}
                </p>
              </div>

              <div class="mt-4">
                <Button type="button" @click="openDetail(announcement)">
                  {{ t('announcements.open_detail') }}
                </Button>
              </div>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
        {{ t('announcements.empty_board') }}
      </div>
    </div>

    <Dialog
      v-model:open="detailOpen"
      @update:open="
        (open) => {
          if (!open) closeDetail();
        }
      "
    >
      <DialogContent class="max-h-[88vh] w-full max-w-4xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle class="text-xl font-bold text-slate-900">
            {{ selectedAnnouncement?.title }}
          </DialogTitle>
          <DialogDescription class="text-sm leading-6 text-slate-600">
            {{ t('announcements.detail_description') }}
          </DialogDescription>
        </DialogHeader>

        <div v-if="selectedAnnouncement" class="space-y-4">
          <div class="flex items-center gap-2 text-sm text-slate-500">
            <CalendarDays class="size-4 text-blue-600" />
            <span>{{ t('announcements.published_at') }}: {{ formatDate(selectedAnnouncement.created_at) }}</span>
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
            <Button type="button">{{ t('announcements.close_detail') }}</Button>
          </DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayoutClient>
</template>
