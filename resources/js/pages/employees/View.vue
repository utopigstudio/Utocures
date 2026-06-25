<script setup lang="ts">
import { computed, ref } from 'vue';
import { AppLayout, LayoutBasic } from '@/layouts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Link } from '@inertiajs/vue3'
import { Button, ButtonDelete } from '@/components/ui/button';
import { LabelData } from '@/components/ui/basic';
import { Trash2, Pencil, UserRound, Phone, BadgeCheck, Star, Clock3 } from 'lucide-vue-next'
import { EmployeeStatusPeriods, Files, ModalAssignedHour, Notes } from '@/components/blocks'
import { router } from '@inertiajs/vue3'
import WeekCalendar from '@/components/ui/calendar/WeekCalendar.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { useI18n } from 'vue-i18n';
import type { AssignedHour, BreadcrumbItem, Employee, EmployeeStatus, EmployeeStatusPeriod, File as FileType } from '@/types'

interface Props {
  employee: Employee
  files: FileType[]
  hours: AssignedHour[]
  status_periods: EmployeeStatusPeriod[]
  current_status: EmployeeStatus
  active_status_period?: EmployeeStatusPeriod | null
  next_status_period?: EmployeeStatusPeriod | null
}

const props = defineProps<Props>()

const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('employees.title'), href: '/employees/view' },
]

const { getInitials } = useInitials();
const assignedHourModalOpen = ref(false)
const selectedAssignedHour = ref<AssignedHour | null>(null)

type CalendarItem =
  | { kind: 'assigned_hour'; data: AssignedHour }
  | { kind: 'status_period'; data: EmployeeStatusPeriod }

function reloadFiles() {
  router.reload({ only: ['files'] });
}

function reloadNotes() {
  router.reload({ only: ['notes'] });
}

function reloadHours() {
  router.reload({ only: ['hours'] });
}

const toISO = (d: string) => {
  const [day, month, year] = d.split('/');
  return `${year}-${month}-${day}`;
};

const calendarItems = computed<CalendarItem[]>(() => [
  ...props.hours.map((hour) => ({ kind: 'assigned_hour' as const, data: hour })),
  ...props.status_periods.map((period) => ({ kind: 'status_period' as const, data: period })),
])

const formatCallback = (events: any[]) => {
  if (!events) return []

  return events.map((event: CalendarItem) => {
    if (event.kind === 'status_period') {
      const palette = {
        vacation: 'rgba(16, 185, 129, 0.16)',
        sick_leave: 'rgba(244, 63, 94, 0.16)',
        absence: 'rgba(100, 116, 139, 0.18)',
        permission: 'rgba(245, 158, 11, 0.18)',
      }

      return {
        id: `status-${event.data.id}`,
        title: event.data.label,
        start: event.data.start_at_input,
        end: event.data.end_at_input,
        display: 'background',
        backgroundColor: palette[event.data.type],
        extendedProps: event.data,
      }
    }

    const hour = event.data

    return {
      id: hour.id,
      title: hour.service.name + '<br>' + hour.client.name,
      start: `${toISO(hour.date)}T${hour.time_start}`,
      end: `${toISO(hour.date)}T${hour.time_end}`,
      backgroundColor: 'rgba(209, 250, 229, 1)',
      borderColor: 'rgb(114 201 156)',
      textColor: '#065F46',
      extendedProps: hour
    }
  })
}

const grouped_characteristics = computed(() => {
  const groups: { [key: string]: string[] } = {}

  props.employee.assigned_characteristics?.forEach((option) => {
    const characteristicName = option.characteristic?.name || 'Other'
    if (!groups[characteristicName]) {
      groups[characteristicName] = []
    }
    groups[characteristicName].push(option.name)
  })

  return groups
})

function handleCalendarEventClick(
  _start: string,
  _end: string,
  _dayOfWeek: number,
  _id: string,
  extendedProps: AssignedHour | EmployeeStatusPeriod,
) {
  if (!('client' in extendedProps) || !('service' in extendedProps)) {
    return
  }

  selectedAssignedHour.value = extendedProps
  assignedHourModalOpen.value = true
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
        
      <Card>
        <CardContent class="md:flex md:items-center md:justify-between">
          <div class="min-w-0 flex">
            <Avatar class="h-8 w-8 overflow-hidden rounded-full mr-4">
              <AvatarImage v-if="props.employee.user.avatar" :src="props.employee.user.avatar" :alt="props.employee.user.name">
                {{ props.employee.user.name }}
              </AvatarImage>
              <AvatarFallback v-else class="rounded-full text-black bg-blue-gray-100 text-xs font-bold leading-4">
                {{ getInitials(props.employee.user.name) }}
              </AvatarFallback>
            </Avatar>
            <h2 class="text-2xl leading-8 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">{{ props.employee.user.name }}</h2>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4 gap-x-6">
            <ButtonDelete :action="route('employees.destroy', props.employee.id)">
              <Trash2 class="text-white"/>
              {{ $t('layout.delete') }}
            </ButtonDelete>
            <Link :href="route('employees.edit', props.employee.id)">
              <Button>
                <Pencil class="text-white"/>
                {{ $t('layout.edit') }}
              </Button>
            </Link>
          </div>
        </CardContent>
      </Card>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-stretch">
        <div class="relative lg:col-span-4 flex">
          <Card class="flex flex-col flex-1">
              <CardHeader>
                <CardTitle class="text-xl flex items-center gap-2 mb-4">
                  <UserRound class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('employees.personal_information') }}</span>
                </CardTitle>
              </CardHeader>
            <CardContent>

              <LabelData :label="$t('employees.full_name')" :value="props.employee.user.name"/>
              <LabelData :label="$t('employees.birth_date')" :value="props.employee.birth_date"/>
              <LabelData :label="$t('employees.hire_date')" :value="props.employee.hire_date"/>
              <LabelData :label="$t('employees.nif')" :value="props.employee.nif"/>
              <LabelData :label="$t('employees.gender')" :value="props.employee.gender?.name"/>

            </CardContent>
          </Card>
        </div>
        <div class="relative lg:col-span-8 flex">
          <Card class="flex flex-col flex-1">
              <CardHeader>
                <CardTitle class="text-xl flex items-center gap-2 mb-4">
                  <Phone class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('employees.contact_data') }}</span>
                </CardTitle>
              </CardHeader>
            <CardContent>

              <LabelData :label="$t('employees.email')" :value="props.employee.user.email"/>
              <LabelData :label="$t('employees.phone')" :value="props.employee.phone"/>
              <LabelData :label="$t('employees.phone_2')" :value="props.employee.phone_2"/>
              <LabelData :label="$t('employees.address')" :value="props.employee.address"/>
              <LabelData :label="$t('employees.city')" :value="props.employee.city"/>
              <LabelData :label="$t('employees.zip_code')" :value="props.employee.zip_code"/>
              <LabelData :label="$t('employees.country')" :value="props.employee.country?.name"/>

            </CardContent>
          </Card>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-stretch">
        <div class="relative lg:col-span-5 flex">
          <Card class="flex flex-col flex-1">
            <CardHeader>
              <CardTitle class="text-xl flex items-center gap-2 mb-4">
                <Star class="text-blue-600 w-5 h-5" />
                <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('employees.assigned_characteristics') }}</span>
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
                <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('employees.assigned_services') }}</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <template v-if="props.employee.services?.length">
                <span v-for="service in props.employee.services" :key="service.id" class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 mr-2 mb-2 text-xs leading-4 font-normal text-blue-600">
                  {{ service.name }}
                </span>
              </template>
              <span v-else class="text-center text-gray-500">{{ $t('layout.no_services_found') }}</span>
            </CardContent>
          </Card>
        </div>
      </div>

      <div class="grid grid-cols-1">
        <div class="relative lg:col-span-12">
          <Card>
            <CardHeader>
              <CardTitle class="text-xl flex items-center gap-2 mb-4">
                <Clock3 class="text-blue-600 w-5 h-5" />
                <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('employees.assigned_services') }}</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <WeekCalendar
                :events="calendarItems"
                :eventClick="handleCalendarEventClick"
                :formatCallback="formatCallback"
                mode="full"
              >
              </WeekCalendar>
            </CardContent>
          </Card>
        </div>
      </div>

      <EmployeeStatusPeriods
        :employee-id="props.employee.id"
        :status-periods="props.status_periods"
        :current-status="props.current_status"
        :active-status-period="props.active_status_period"
        :next-status-period="props.next_status_period"
      />

      <ModalAssignedHour
        :assigned-hour="selectedAssignedHour"
        :employee-id="props.employee.id"
        :show="assignedHourModalOpen"
        @close="assignedHourModalOpen = false; selectedAssignedHour = null"
        @saved="reloadHours"
      />

      <Files :files="props.files" resource="employee" :parent-id="props.employee.id" @saved="reloadFiles" />

      <Notes :notes="props.employee.notes" resource="employee" :parent-id="props.employee.id" @saved="reloadNotes" />

    </LayoutBasic>
  </AppLayout>
</template>
