<script setup lang="ts">
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3'
import { watchDebounced } from '@vueuse/core'
import { AppLayoutEmployee } from '@/layouts';
import { TimerCounter, Notes } from '@/components/blocks';
import { Button } from '@/components/ui/button';
import { LabelData } from '@/components/ui/basic';
import { UserRound, Phone, ChevronLeft } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useI18n } from 'vue-i18n';
import type { Work, Note } from '@/types';

interface Props {
  work: Work
  notes: Note[]
  hasActiveWork: boolean
  current_time_record_id?: string | null
  filters?: {
    filter_search?: string
  }
}

const props = defineProps<Props>()

const { t } = useI18n()

const activeTab = ref('information');
const tabs = [
  {
    title: t('employees.information'),
    id: 'information',
  },
  {
    title: t('employees.notes'),
    id: 'notes',
  },
  {
    title: t('employees.tasks'),
    id: 'tasks',
  }, 
];

const reloadNotes = () => {
  router.reload({ only: ['notes'] });
};

const searchFilter = ref<string>(props.filters?.filter_search ?? '')

watchDebounced(
  searchFilter,
  (newSearch) => {
    if (newSearch && newSearch.length < 3) newSearch = ''

    router.get(
      route('employee.work.show', props.work.id),
      {
        filter_search: newSearch,
      },
      { preserveState: true, replace: true }
    )
  },
  { debounce: 500, maxWait: 1000 }
)
</script>

<template>
  <AppLayoutEmployee>
    <div class="container mx-auto flex items-center gap-2 px-4 pt-0 pb-4 bg-gray-50">
      <Link :href="route('dashboard')" class="flex items-center gap-2 justify-start p-0 h-auto cursor-pointer">
        <ChevronLeft class="size-5" />
        <h1 class="text-lg leading-7 font-bold text-gray-800">{{ work.client.name }}</h1>
      </Link>
    </div>
    <TimerCounter :work="work" :hasActiveWork="hasActiveWork" />
    <div class="flex flex-col container mx-auto bg-white">
      <aside class="w-full">
        <nav class="flex flex-row">
          <Button
            v-for="item in tabs"
            :key="item.id"
            variant="ghost"
            @click.prevent="activeTab = item.id"
            :class="[
              'justify-start text-sm leading-5 font-medium rounded-none px-4',
              item.id === activeTab
                ? 'bg-blue-gray-600 text-white hover:bg-blue-gray-600 hover:text-white' 
                : 'text-gray-600'
            ]"
          >
            {{ item.title }}
          </Button>
        </nav>
      </aside>

      <div class="flex flex-col lg:flex-row">
        <div class="flex-1">
          <section>
            <div v-if="activeTab === 'information'" class="flex flex-col gap-4">
              <Card class="rounded-none">
                <CardHeader>
                  <CardTitle class="text-xl flex items-center gap-2 mb-4">
                    <UserRound class="text-blue-600 w-5 h-5" />
                    <span class="text-gray-800 text-xl leading-7 font-bold">{{ t('employees.personal_information') }}</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <LabelData :label="t('employees.full_name')" :value="work.client.name"/>
                  <LabelData :label="t('employees.birth_date')" :value="work.client.birth_date"/>
                  <LabelData :label="t('employees.gender')" :value="work.client.gender?.name"/>
                </CardContent>
              </Card>

              <Card class="rounded-none">
                <CardHeader>
                  <CardTitle class="text-xl flex items-center gap-2 mb-4">
                    <Phone class="text-blue-600 w-5 h-5" />
                    <span class="text-gray-800 text-xl leading-7 font-bold">{{ t('employees.contact_data') }}</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <LabelData :label="t('employees.email')" :value="work.client.email"/>
                  <LabelData :label="t('employees.phone')" :value="work.client.phone"/>
                  <LabelData :label="t('employees.phone_2')" :value="work.client.phone_2"/>
                  <LabelData :label="t('employees.address')" :value="work.client.address"/>
                  <LabelData :label="t('employees.city')" :value="work.client.city"/>
                  <LabelData :label="t('employees.zip_code')" :value="work.client.zip_code"/>
                  <LabelData :label="t('employees.country')" :value="work.client.country.name"/>
                </CardContent>
              </Card>
            </div>

            <div v-if="activeTab === 'notes'" class="flex flex-col gap-4">
              <Notes
                :notes="notes"
                resource="client"
                :filter="props.filters?.filter_search"
                :parent-id="work.client.id"
                :actions-enabled="false"
                allow-type-selection
                :employee-time-record-id="props.current_time_record_id"
                @search="searchFilter = $event"
                @saved="reloadNotes"
                class="rounded-none"
                titleClass="hidden"
              />
            </div>

            <div v-if="activeTab === 'tasks'" class="flex flex-col gap-4">
              <Card class="rounded-none">
                <CardContent>
                  <template v-if="props.work.service.tasks">
                    <div
                      v-for="task in props.work.service.tasks"
                      :key="task.id"
                      class="inline-block bg-blue-100 text-blue-800 text-xs px-3 py-2 rounded-full mb-4 w-full">
                      {{ task.name }}
                    </div>
                  </template>
                </CardContent>
              </Card>
            </div>
          </section>
        </div>
      </div>
    </div>
  </AppLayoutEmployee>
</template>
