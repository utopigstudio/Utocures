<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3'
import { AppLayout, LayoutBasic } from '@/layouts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Camera, UserRound, Phone, BadgeCheck, Star, Save, X, Clock } from 'lucide-vue-next'
import { Form, Link } from '@inertiajs/vue3'
import { InputText, Select, InputCheckboxGroup, InputCheckbox, Datepicker } from '@/components/ui/input'
import { Button } from '@/components/ui/button';
import { ModalHours } from '@/components/blocks';
import { WeekCalendar } from '@/components/ui/calendar'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { useI18n } from 'vue-i18n';
import type { BreadcrumbItem, Country, Gender, Employee, ServiceOption, AvailableHour, CharacteristicOptionsGrouped } from '@/types'

interface Props {
  employee?: Employee
  countries: Country[]
  genders: Gender[]
  services: ServiceOption[]
  available_hours?: AvailableHour[]
  characteristics: CharacteristicOptionsGrouped[]
}

const props = defineProps<Props>()
const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('employees.create_employee'), href: '/employees/create' },
]

const { getInitials } = useInitials();

const modalHourShow = ref(false);
const hourForModify = ref({} as AvailableHour);
const employeeName = ref(props.employee ? props.employee.user.name : '');
const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const isDragOver = ref(false)

const formatCallback = (events: any[]) => {
  if (!events) return []

  return events.map(hour => ({
    id: hour.id,
    title: hour.time_start + ' - ' + hour.time_end,
    daysOfWeek: [parseInt(hour.day_of_week)],
    startTime: hour.time_start,
    endTime: hour.time_end,
    backgroundColor: 'rgba(209, 250, 229, 1)',
    borderColor: 'rgb(114 201 156)',
    textColor: '#065F46',
    extendedProps: hour
  }))
}

function reloadHours() {
  router.reload({ only: ['available_hours'] });
}

function handleFile(file: File) {
  selectedFile.value = file

  if (file.type.startsWith('image/')) {
    previewUrl.value = URL.createObjectURL(file)
  } else {
    previewUrl.value = null
  }
}

function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) handleFile(file)
}

function onDrop(e: DragEvent) {
  e.preventDefault()
  isDragOver.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) handleFile(file)
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
  isDragOver.value = true
}

function onDragLeave(e: DragEvent) {
  e.preventDefault()
  isDragOver.value = false
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems" backTo="employees.index">
      <Form
        as="form"
        enctype="multipart/form-data"
        method="POST"
        :action="props.employee ? route('employees.update', props.employee.id) : route('employees.store')"
        class="space-y-6"
        v-slot="{ errors, processing }"
      >
        <Card>
          <CardContent class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex">
              <Avatar v-if="props.employee" class="h-8 w-8 overflow-hidden rounded-full mr-4">
                <AvatarImage v-if="props.employee?.user.avatar" :src="props.employee.user.avatar" :alt="props.employee.user.name">
                  {{ props.employee.user.name }}
                </AvatarImage>
                <AvatarFallback v-else class="rounded-full text-black bg-blue-gray-100 text-xs font-bold leading-4">
                  {{ getInitials(props.employee?.user.name) }}
                </AvatarFallback>
              </Avatar>
              <h2 class="text-2xl leading-8 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">{{ employeeName }}</h2>
            </div>
            <div :class="`${props.employee ? 'mt-4 md:mt-0' : ''} flex md:ml-4 gap-6`">
              <Link :href="props.employee ? route('employees.show', props.employee.id) : route('employees.index')">
                <Button variant="outline" class="flex items-center gap-2 text-blue-gray-600 border-2">
                  <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                </Button>
              </Link>
              <Button :disabled="processing" class="flex items-center gap-2">
                <Save class="size-4" />
                {{ props.employee ? $t('layout.save_changes') : $t('layout.save') }}
              </Button>
              <input v-if="props.employee" type="hidden" name="_method" value="PUT">
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

                <InputCheckbox
                  name="is_active"
                  :label="$t('employees.active')"
                  :default-value="Boolean(props.employee?.user.is_active) ?? true"
                  :error="errors.is_active"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="name"
                  :label="$t('employees.full_name')"
                  :default-value="props.employee?.user.name"
                  autocomplete="name"
                  :placeholder="$t('employees.full_name')"
                  :required="true"
                  :error="errors.name"
                  @update:modelValue="employeeName = $event.toString()"
                />

                <Datepicker
                  class="mt-1 block w-full mb-4"
                  name="birth_date"
                  :label="$t('employees.birth_date')"
                  :default-value="props.employee?.birth_date"
                  autocomplete="birth_date"
                  :error="errors.birth_date"
                />

                <Datepicker
                  class="mt-1 block w-full mb-4"
                  name="hire_date"
                  :label="$t('employees.hire_date')"
                  :default-value="props.employee?.hire_date"
                  autocomplete="hire_date"
                  :error="errors.hire_date"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="nif"
                  :label="$t('employees.nif')"
                  :default-value="props.employee?.nif"
                  autocomplete="nif"
                  :required="true"
                  :error="errors.nif"
                />

                <Select
                  name="gender_id"
                  :label="$t('employees.gender')"
                  class="mt-1 w-full"
                  :default-value="props.employee?.gender_id ?? 2"
                  :options="props.genders.map(g => ({ label: g.name, value: g.id }))"
                  :required="true"
                  :error="errors.gender_id"
                />

                <div class="col-span-1 mt-4">
                  <label class="block text-sm font-medium text-gray-700 mt-2">
                    {{ t('users.avatar') }}
                  </label>
                  <div
                    class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10 transition-colors"
                    :class="isDragOver ? 'bg-indigo-50 border-indigo-400' : ''"
                    @dragover="onDragOver"
                    @dragleave="onDragLeave"
                    @drop="onDrop"
                  >
                    <div class="text-center">
                      <Camera class="mx-auto size-12 text-gray-300" />
                      <div class="mt-4 flex text-sm text-gray-600 justify-center">
                        <label
                          for="file"
                          class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 hover:text-indigo-500"
                        >
                          <span>{{ $t('layout.select_file') }}</span>
                          <input
                            id="file"
                            ref="fileInput"
                            name="avatar"
                            type="file"
                            class="sr-only"
                            @change="onFileChange"
                          />
                        </label>
                        <p class="pl-1">{{ $t('layout.or_drag_here') }}</p>
                      </div>
                      <p class="text-xs text-gray-600">{{ $t('layout.file_size_limit') }}</p>

                      <div v-if="selectedFile" class="mt-6 text-sm text-gray-700">
                        <p class="font-semibold">{{ $t('layout.selected_file') }}</p>
                        <p>{{ selectedFile.name }}</p>
                        <div v-if="previewUrl" class="mt-3">
                          <img
                            :src="previewUrl"
                            alt="Preview"
                            class="mx-auto max-h-48 rounded-md border"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                  <p v-if="errors.avatar" class="mt-3 text-sm text-red-600">{{ errors.avatar }}</p>
                </div>

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

                <InputText
                  class="mt-1 block w-full"
                  name="email"
                  :label="$t('employees.email')"
                  :default-value="props.employee?.user.email"
                  autocomplete="email"
                  :placeholder="$t('employees.email')"
                  :error="errors.email"
                  :required="true"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="phone"
                  :label="$t('employees.phone')"
                  :default-value="props.employee?.phone"
                  autocomplete="phone"
                  :placeholder="$t('employees.phone')"
                  :required="true"
                  :error="errors.phone"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="phone_2"
                  :label="$t('employees.phone_2')"
                  :default-value="props.employee?.phone_2"
                  autocomplete="phone_2"
                  :placeholder="$t('employees.phone_2')"
                  :error="errors.phone_2"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="address"
                  :label="$t('employees.address')"
                  :default-value="props.employee?.address"
                  autocomplete="address"
                  :placeholder="$t('employees.address')"
                  :error="errors.address"
                  :required="true"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="city"
                  :label="$t('employees.city')"
                  :default-value="props.employee?.city"
                  autocomplete="city"
                  :placeholder="$t('employees.city')"
                  :error="errors.city"
                  :required="true"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="zip_code"
                  :label="$t('employees.zip_code')"
                  :default-value="props.employee?.zip_code"
                  autocomplete="zip_code"
                  :placeholder="$t('employees.zip_code')"
                  :required="true"
                  :error="errors.zip_code"
                />

                <Select
                  class="mt-1 w-full"
                  name="country_id"
                  :label="$t('employees.country')"
                  :default-value="props.employee?.country_id || 'es'"
                  :options="props.countries.map(ct => ({ label: ct.name, value: ct.id }))"
                  :required="true"
                  :error="errors.country_id"
                />

              </CardContent>
            </Card>
          </div>
        </div>

        <div class="grid grid-cols-1">
          <div class="relative lg:col-span-12">
            <Card>
              <CardHeader>
                <CardTitle class="text-xl flex items-center gap-2 mb-4">
                  <BadgeCheck class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('employees.assigned_services') }}</span>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="text-sm leading-5 font-normal text-blue-gray-700 mb-4">{{ $t('employees.select_services') }}</div>
                <div class="md:p-[.625rem]">
                  <InputCheckboxGroup
                    variant="flex"
                    variantItem="pilled"
                    name="services[]"
                    :options="props.services.map(s => ({ label: s.name, value: s.id }))"
                    :error="errors.services"
                    :default-value="props.employee?.services?.map(s => s.id) || []"
                  />
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <div class="grid grid-cols-1">
          <div class="relative lg:col-span-1">
            <Card class="gap-2">
              <CardHeader>
                <CardTitle class="text-xl flex items-center gap-6 mb-4">
                  <Star class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('employees.assigned_characteristics') }}</span>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <template v-if="props.characteristics.length">
                  <div v-for="characteristic in props.characteristics" :key="characteristic.name">
                    <div class="py-4">
                      <p class="text-base leading-6 font-medium text-blue-gray-800 mb-3">{{ characteristic.name }}</p>
                      <InputCheckboxGroup
                        labelClass="mb-0"
                        name="characteristics[]"
                        :options="characteristic.options.map(s => ({ label: s.name, value: s.id }))"
                        :error="errors.characteristics"
                        :default-value="props.employee?.assigned_characteristics
                          ?.filter(ac => characteristic.options.some(co => co.id === ac.id))
                          .map(s => s.id) || []"
                      />
                    </div>
                    <div class="w-full border-t border-gray-200" aria-hidden="true" />
                  </div>
                </template>
                <span v-else class="text-center text-gray-500">{{ $t('layout.no_results_found') }}</span>
              </CardContent>
            </Card>
          </div>
        </div>

        <div v-if="props.employee" class="grid grid-cols-1">
          <div class="relative lg:col-span-12">
            <Card>
              <CardHeader>
                <CardTitle class="text-xl flex items-center gap-6 mb-4">
                  <Clock class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('employees.required_hours') }}</span>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <WeekCalendar
                  :events="props.available_hours || []"
                  :formatCallback="formatCallback"
                  :select="(start: any, end: any, day_of_week: any) => {
                    modalHourShow = true; hourForModify = { time_start: start, time_end: end, day_of_week, id: '' }
                  }"
                  :eventClick="(start: any, end: any, day_of_week: any, id: any) => {
                    modalHourShow = true; hourForModify = { time_start: start, time_end: end, day_of_week, id }
                  }"
                >
                  <ModalHours
                    @close="modalHourShow = false"
                    @saved="reloadHours"
                    @deleted="reloadHours"
                    :show="modalHourShow"
                    :hour="hourForModify"
                    :parentId="props.employee.id"
                  />
                </WeekCalendar>
              </CardContent>
            </Card>
          </div>
        </div>
      </Form>
    </LayoutBasic>
  </AppLayout>
</template>
