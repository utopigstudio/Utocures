<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3'
import { AppLayout, LayoutTabs } from '@/layouts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Files } from '@/components/blocks'
import { LabelData, BooleanData } from '@/components/ui/basic'
import { Trash2, Pencil, UserRound, Phone, FileText, Clock3, Star, BadgeCheck, Users } from 'lucide-vue-next'
import { ButtonDelete, Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3'
import { ModalCalendar, ListEmployeesAssigned } from '@/components/blocks';
import { WeekCalendar } from '@/components/ui/calendar';
import { useI18n } from 'vue-i18n'
import type { BreadcrumbItem, TabItem, Client, ServiceOption, AssignedHourTemplate, File as FileType } from '@/types'

interface Props {
  client: Client
  assigned_hours_templates: AssignedHourTemplate[]
  services: ServiceOption[]
  files: FileType[]
}

const props = defineProps<Props>()

const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('clients.show'), href: '/clients/view' },
]

const modalShow = ref(false);
const templateModify = ref<AssignedHourTemplate | null>(null);
const tabItems: TabItem[] = [
  { title: t('clients.personal_data'), href: route('clients.show', props.client.id) },
  { title: t('clients.invoices'), href: route('clients.invoices', props.client.id) },
  { title: t('clients.contracts'), href: route('clients.contracts', props.client.id) },
  { title: t('clients.budgets'), href: route('clients.budgets', props.client.id) },
  { title: t('clients.notes'), href: route('clients.notes', props.client.id) }
]

const formatCallback = (events: AssignedHourTemplate[] = []) => {
  if (!events?.length) return []

  const formatted: any[] = []

  for (const item of events) {
    const cloned = JSON.parse(JSON.stringify(item))
    const days = cloned.days_of_week

    if (days) {
      for (const day of days) {
        formatted.push({
          id: `${item.id}-${day}`,
          title: cloned.employee.user.name + '<br>' + (cloned.service?.name || t('clients.no_service')),
          daysOfWeek: [Number(day)],
          startTime: cloned.time_start,
          endTime: cloned.time_end,
          backgroundColor: 'rgba(209, 250, 229, 1)',
          borderColor: 'rgb(114 201 156)',
          textColor: '#065F46',
          extendedProps: { ...cloned, original_id: cloned.id },
        })
      }
    } else {
      const dayOfWeek = getDayOfWeekFromDDMMYYYY(cloned.date)
      formatted.push({
        id: `${item.id}-${dayOfWeek}`,
        title: cloned.employee.user.name + '<br>' + (cloned.service?.name || t('clients.no_service')),
        daysOfWeek: [Number(dayOfWeek)],
        startTime: cloned.time_start,
        endTime: cloned.time_end,
        display: 'background',
        backgroundColor: 'rgba(209, 250, 229, 1)',
        textColor: '#065F46',
        extendedProps: { ...cloned, original_id: cloned.id },
      })
    }
  }

  return formatted
}

function getDayOfWeekFromDDMMYYYY(str: string): number {
  const [day, month, year] = str.split('/').map(Number)
  const d = new Date(year, month - 1, day)
  return d.getDay()
}

function reloadHourTemplates() {
  router.reload({ only: ['assigned_hours_templates'] });
  templateModify.value = null;
}

function reloadFiles() {
  router.reload({ only: ['files'] });
}

const grouped_characteristics = computed(() => {
  const groups: { [key: string]: string[] } = {}

  props.client.assigned_characteristics?.forEach((option) => {
    const characteristicName = option.characteristic?.name || 'Other'
    if (!groups[characteristicName]) {
      groups[characteristicName] = []
    }
    groups[characteristicName].push(option.name)
  })

  return groups
})
</script>

<template>
  <AppLayout>
    <LayoutTabs :tabs="tabItems" :breadcrumbs="breadcrumbItems" backTo="clients.index">

      <Card>
        <CardContent class="md:flex md:items-center md:justify-between">
          <div class="min-w-0 flex-1">
            <h2 class="text-2xl leading-8 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">{{ props.client.name }}</h2>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4 gap-x-6">
            <ButtonDelete :action="route('clients.destroy', props.client.id)">
              <Trash2 class="text-white"/>
              {{ $t('layout.delete') }}
            </ButtonDelete>
            <Link :href="route('clients.edit', props.client.id)">
              <Button>
                <Pencil class="text-white"/>
                {{ $t('layout.edit') }}
              </Button>
            </Link>
          </div>
        </CardContent>
      </Card>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-stretch mb-6">
        <div class="relative lg:col-span-4 flex">
          <Card class="flex flex-col flex-1">
              <CardHeader>
                <CardTitle class="text-xl flex items-center gap-2 mb-4">
                  <UserRound class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('clients.personal_information') }}</span>
                </CardTitle>
              </CardHeader>
            <CardContent>

              <LabelData :label="$t('clients.full_name')" :value="props.client.name"/>
              <LabelData :label="$t('clients.birth_date')" :value="props.client.birth_date"/>
              <LabelData :label="$t('clients.cif_nif')" :value="props.client.cif_nif"/>
              <LabelData :label="$t('clients.gender')" :value="props.client.gender?.name"/>

            </CardContent>
          </Card>
        </div>

        <div class="relative lg:col-span-8 flex">
          <Card class="flex flex-col flex-1">
              <CardHeader>
                <CardTitle class="text-xl flex items-center gap-2 mb-4">
                  <Phone class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('clients.contact_data') }}</span>
                </CardTitle>
              </CardHeader>
            <CardContent>

              <LabelData :label="$t('clients.email')" :value="props.client.email"/>
              <LabelData :label="$t('clients.phone')" :value="props.client.phone"/>
              <LabelData :label="$t('clients.phone_2')" :value="props.client.phone_2"/>
              <LabelData :label="$t('clients.address')" :value="props.client.address"/>
              <LabelData :label="$t('clients.city')" :value="props.client.city"/>
              <LabelData :label="$t('clients.zip_code')" :value="props.client.zip_code"/>
              <LabelData :label="$t('clients.country')" :value="props.client.country.name"/>

            </CardContent>
          </Card>
        </div>
      </div>

      <div class="grid grid-cols-1 mb-6">
        <div class="relative lg:col-span-12">
          <Card>
              <CardHeader>
                <CardTitle class="text-xl flex items-center gap-2 mb-4">
                  <FileText class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('clients.billing_data') }}</span>
                </CardTitle>
              </CardHeader>
            <CardContent>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="flex flex-col gap-4">
                  <div>
                    <LabelData :label="$t('clients.bank_name')" :value="props.client.bank_name"/>
                  </div>
                  <div class="flex items-center gap-4">
                    <div>
                      <BooleanData :value="!!props.client.tax_included" :label="$t('clients.tax_included')" />
                    </div>
                    <div>
                      <BooleanData :value="!!props.client.is_partner" :label="$t('clients.is_partner')" />
                    </div>
                  </div>
                </div>
                <div>
                  <LabelData :label="$t('clients.bank_account')" :value="props.client.bank_account"/>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-stretch mb-6">
        <div class="relative lg:col-span-5 flex">
          <Card class="flex flex-col flex-1">
            <CardHeader>
              <CardTitle class="text-xl flex items-center gap-2 mb-4">
                <Star class="text-blue-600 w-5 h-5" />
                <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('layout.assigned_characteristics') }}</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <template v-if="Object.keys(grouped_characteristics).length">
                <div v-for="(options, characteristic) in grouped_characteristics" :key="characteristic" class="">
                  <div class="flex items-start justify-between rounded-full py-4 text-sm leading-5 font-normal text-blue-gray-500 first:pt-0">
                    <span class="pr-2">{{ characteristic }}:</span> <span class="text-base leading-6 text-gray-900">{{ options.join(', ') }}</span>
                  </div>
                </div>
              </template>
              <span v-else class="text-center text-gray-500">{{ $t('layout.no_assigned_characteristics') }}</span>
            </CardContent>
          </Card>
        </div>
        <div class="relative lg:col-span-7 flex">
          <Card class="flex flex-col flex-1">
            <CardHeader>
              <CardTitle class="text-xl flex items-center gap-2 mb-4">
                <BadgeCheck class="text-blue-600 w-5 h-5" />
                <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('layout.services') }}</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <template v-if="props.client.services?.length">
                <span v-for="service in props.client.services" :key="service.id" class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 mr-2 mb-2 text-xs leading-4 font-normal text-blue-600">
                  {{ service.name }}
                </span>
              </template>
              <span v-else class="text-center text-gray-500">{{ $t('layout.no_services_found') }}</span>
            </CardContent>
          </Card>
        </div>
      </div>

      <div class="grid grid-cols-1 mb-6">
        <div class="relative lg:col-span-12">
          <Card>
            <CardHeader>
              <CardTitle class="text-xl flex flex-col md:flex-row items-start md:items-center gap-2 mb-4">
                <div class="flex items-center gap-2">
                  <Clock3 class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('clients.add_task_assignment') }}</span>
                </div>
                <Button v-if="props.client.services?.length" type="button" class="mt-2 md:mt-0 md:ml-auto w-full md:w-auto" @click="modalShow = true" >
                  {{ $t('clients.add_schedule') }}
                </Button>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <WeekCalendar
                v-if="props.client.services?.length"
                :events="props.assigned_hours_templates"
                :formatCallback="formatCallback"
                :eventClick="(start: any, end: any, day_of_week: any, id: any, extendedProps: any) => {
                  modalShow = true
                  const found = props.assigned_hours_templates?.find(t => t.id === extendedProps.original_id)
                  templateModify = found ? JSON.parse(JSON.stringify(found)) : null
                }"
              >
                <ModalCalendar
                  :services="props.client.services?.map(s => ({ id: s.id, name: s.name }))"
                  :show="modalShow"
                  :parent-id="props.client.id"
                  :hour-template="templateModify"
                  @close="modalShow = false; templateModify = null;"
                  @saved="reloadHourTemplates"
                  @deleted="reloadHourTemplates"
                />
              </WeekCalendar>
              <div v-else class="text-center text-gray-500">
                {{ $t('clients.no_services_assigned') }}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <div class="grid grid-cols-1 mb-6">
        <div class="relative lg:col-span-12">
          <Card>
            <CardHeader>
              <CardTitle class="text-xl flex items-center gap-2 mb-4">
                <Users class="text-blue-600 w-5 h-5" />
                <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('clients.assigned_employees') }}</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ListEmployeesAssigned v-if="props.assigned_hours_templates.length" :items="props.assigned_hours_templates" />
              <div v-else class="text-center text-gray-500">
                {{ $t('clients.no_assigned_employees') }}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <Files :files="props.files" resource="client" :parent-id="props.client.id" @saved="reloadFiles" />
    </LayoutTabs>
  </AppLayout>
</template>