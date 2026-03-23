<script setup lang="ts">
import { ref } from 'vue';
import { AppLayout, LayoutBasic } from '@/layouts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Form, Link } from '@inertiajs/vue3'
import { InputText, Select, InputCheckbox, InputCheckboxGroup, Datepicker } from '@/components/ui/input'
import { Button } from '@/components/ui/button';
import { UserRound, Phone, FileText, BadgeCheck, Star, Save, X } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import type { BreadcrumbItem, Country, Gender, Client, ServiceOption, CharacteristicOptionsGrouped } from '@/types'

interface Props {
  client?: Client
  countries: Country[]
  genders: Gender[]
  services: ServiceOption[]
  characteristics: CharacteristicOptionsGrouped[]
}

const props = defineProps<Props>()

const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: props.client ? t('clients.edit_client') : t('clients.create_client'), href: '/clients/create' },
]

const clientName = ref(props.client ? props.client.name : '')
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Form :method="props.client ? 'put' : 'post'" :action="props.client ? route('clients.update', props.client.id) : route('clients.store')" class="space-y-6" v-slot="{ errors, processing }">
        <Card>
          <CardContent class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
              <h2 class="text-2xl leading-8 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">{{ clientName }}</h2>
            </div>
            <div :class="`${props.client ? 'mt-4 md:mt-0' : ''} flex md:ml-4 gap-6`">
              <Link :href="props.client ? route('clients.show', props.client.id) : route('clients.index')">
                <Button variant="outline" class="flex items-center gap-2 text-blue-gray-600 border-2">
                  <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                </Button>
              </Link>
              <Button :disabled="processing" class="flex items-center">
                <Save class="size-4" />
                {{ props.client ? $t('layout.save_changes') : $t('layout.save') }}
              </Button>
            </div>
          </CardContent>
        </Card>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-stretch">
          <div class="relative lg:col-span-4 flex">
            <Card class="flex flex-col flex-1">
              <CardHeader>
                <CardTitle class="text-xl flex items-center gap-2 mb-4">
                  <UserRound class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('clients.personal_information') }}</span>
                </CardTitle>
              </CardHeader>
              <CardContent>

                <InputText
                  class="mt-1 block w-full"
                  name="name"
                  :label="$t('clients.full_name')"
                  :default-value="props.client?.name"
                  autocomplete="name"
                  :placeholder="$t('clients.full_name')"
                  :required="true"
                  :error="errors.name"
                  @update:modelValue="clientName = $event.toString()"
                />

                <Datepicker
                  class="mt-1 mb-4 block w-full"
                  name="birth_date"
                  :label="$t('clients.birth_date')"
                  :default-value="props.client?.birth_date"
                  :required="true"
                  :error="errors.birth_date"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="cif_nif"
                  :label="$t('clients.cif_nif')"
                  :default-value="props.client?.cif_nif"
                  autocomplete="cif_nif"
                  :required="true"
                  :error="errors.cif_nif"
                />

                <Select
                  name="gender_id"
                  :label="$t('clients.gender')"
                  class="mt-1 w-full"
                  :default-value="props.client?.gender_id ?? '2'"
                  :options="props.genders.map(g => ({ label: g.name, value: g.id.toString() }))"
                  :required="true"
                  :error="errors.gender_id"
                />

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

                <InputText
                  class="mt-1 block w-full"
                  name="email"
                  :label="$t('clients.email')"
                  :default-value="props.client?.email"
                  autocomplete="email"
                  :placeholder="$t('clients.email')"
                  :required="true"
                  :error="errors.email"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="phone"
                  :label="$t('clients.phone')"
                  :default-value="props.client?.phone"
                  autocomplete="phone"
                  :placeholder="$t('clients.phone')"
                  :required="true"
                  :error="errors.phone"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="phone_2"
                  :label="$t('clients.phone_2')"
                  :default-value="props.client?.phone_2"
                  autocomplete="phone_2"
                  :placeholder="$t('clients.phone_2')"
                  :error="errors.phone_2"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="address"
                  :label="$t('clients.address')"
                  :default-value="props.client?.address"
                  autocomplete="address"
                  :placeholder="$t('clients.address')"
                  :required="true"
                  :error="errors.address"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="city"
                  :label="$t('clients.city')"
                  :default-value="props.client?.city"
                  autocomplete="city"
                  :placeholder="$t('clients.city')"
                  :required="true"
                  :error="errors.city"
                />

                <InputText
                  class="mt-1 block w-full"
                  name="zip_code"
                  :label="$t('clients.zip_code')"
                  :default-value="props.client?.zip_code"
                  autocomplete="zip_code"
                  :placeholder="$t('clients.zip_code')"
                  :required="true"
                  :error="errors.zip_code"
                />

                <Select
                  class="mt-1 w-full"
                  name="country_id"
                  :label="$t('clients.country')"
                  :default-value="props.client?.country_id || 'es'"
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
                  <FileText class="text-blue-600 w-5 h-5" />
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('clients.billing_data') }}</span>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <InputText
                      name="bank_name"
                      :label="$t('clients.bank_name')"
                      :default-value="props.client?.bank_name"
                      :placeholder="$t('clients.bank_name')"
                      :error="errors.bank_name"
                      class="mb-0"
                    />
                  </div>
                  <div>
                    <InputText
                      name="bank_account"
                      :label="$t('clients.bank_account')"
                      :default-value="props.client?.bank_account"
                      placeholder="ES61 1234 5678 90 1234567890"
                      :error="errors.bank_account"
                      class="mb-0"
                    />
                  </div>
                  <div>
                    <Select
                      name="tax_included"
                      :label="$t('clients.tax_included')"
                      :default-value="props.client?.tax_included ? 1 : 0"
                      :options="[{ label: 'Sí', value: '1' }, { label: 'No', value: '0' }]"
                      :error="errors.tax_included"
                    />
                  </div>
                  <div>
                    <Select
                      name="is_partner"
                      :label="$t('clients.is_partner')"
                      :default-value="props.client?.is_partner ? 1 : 0"
                      :options="[{ label: 'Sí', value: '1' }, { label: 'No', value: '0' }]"
                      :error="errors.is_partner"
                    />
                  </div>
                  <div class="flex items-start">
                    <InputCheckbox
                      name="automatic_invoice"
                      :label="$t('clients.automatic_invoice')"
                      :title="$t('clients.automatic_invoice_2')"
                      :default-value="!!props.client?.automatic_invoice"
                      :error="errors.automatic_invoice"
                    />
                  </div>
                </div>
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
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('clients.assigned_services') }}</span>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="text-sm leading-5 font-normal text-blue-gray-700 mb-4">{{ $t('clients.select_services') }}</div>
                <div class="md:p-[.625rem]">
                  <InputCheckboxGroup
                    variant="flex"
                    variantItem="pilled"
                    name="services[]"
                    :options="services.map(s => ({ label: s.name, value: s.id }))"
                    :error="errors.services"
                    :default-value="props.client?.services?.map(s => s.id) || []"
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
                  <span class="text-gray-800 text-xl leading-7 font-bold">{{ $t('clients.assigned_characteristics') }}</span>
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
                        :default-value="props.client?.assigned_characteristics
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

      </Form>
    </LayoutBasic>
  </AppLayout>
</template>
