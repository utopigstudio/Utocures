<script setup lang="ts">
import { ref } from 'vue'
import { AppLayout, LayoutBasic } from '@/layouts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Form, Link } from '@inertiajs/vue3'
import { SelectClients, Select, Wysiwyg, SelectTemplate, InputText, Datepicker } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { FileMinus, Save, X } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import type { BreadcrumbItem, Contract, Status } from '@/types'

interface Props {
  contract?: Contract
  statuses: Status[]
}

const props = defineProps<Props>()
const { t } = useI18n()
const breadcrumbItems: BreadcrumbItem[] = [
  { title: props.contract ? t('contracts.edit_contract') : t('contracts.create_contract'), href: '/contracts/create' },
]
const selectedTemplateId = ref('')
const templateContent = ref('')
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Form
        :method="props.contract ? 'put' : 'post'"
        :action="props.contract ? route('contracts.update', props.contract.id) : route('contracts.store')"
        class="space-y-6"
        v-slot="{ errors, processing }"
      >
        <div class="grid grid-cols-1">
          <div class="relative">
            <Card>
              <CardHeader>
                <CardTitle class="text-xl lg:flex lg:items-center lg:justify-between mb-4">
                  <div class="flex items-center gap-2 mb-4 lg:mb-0">
                    <FileMinus class="text-blue-600 w-5 h-5" />
                    <span class="text-gray-900 text-xl leading-7 font-semibold">{{ props.contract ? $t('contracts.edit_contract') : $t('contracts.create_contract') }}</span>
                  </div>
                  <div class="flex flex-wrap justify-between gap-6">
                    <Link :href="props.contract ? route('contracts.show', props.contract.id) : route('contracts.index')">
                      <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                        <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                      </Button>
                    </Link>
                    <Button type="submit" size="lg" :disabled="processing">
                      <Save class="size-4 text-white" />
                      {{ props.contract ? $t('layout.save_changes') : $t('layout.save') }}
                    </Button>
                  </div>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="grid grid-cols-1 gap-x-4 lg:grid-cols-12 my-4">
                   <div class="relative lg:col-span-6">
                    <InputText
                      name="title"
                      :label="$t('contracts.title_label')"
                      class="mt-1 w-full"
                      :default-value="props.contract?.title"
                      :required="true"
                      :error="errors.title"
                      :free-client="true"
                    />
                  </div>
                  <div class="relative lg:col-span-6">
                    <SelectClients
                      name="client_id"
                      :label="$t('contracts.client')"
                      class="mt-1 w-full mb-4 lg:mb-0"
                      :default-value="props.contract?.client?.id ?? props.contract?.client_id"
                      :required="true"
                      :error="errors.client_id"
                    />
                  </div>
                  <div class="relative lg:col-span-3">
                    <SelectTemplate
                      type="contracts"
                      :label="$t('contracts.contract_template')"
                      v-model="selectedTemplateId"
                      @template-selected="templateContent = $event"
                      class="mb-4 lg:mb-0"
                    />
                  </div>
                  <div class="relative lg:col-span-3">
                    <Select
                      name="status"
                      :label="$t('contracts.status')"
                      :default-value="props.contract?.status"
                      :options="props.statuses.map(m => ({ label: m.name, value: m.id }))"
                      :placeholder="$t('contracts.status')"
                      :required="true"
                      :error="errors.status"
                      class="mb-4 lg:mb-0"
                    />
                  </div>
                  <div class="relative lg:col-span-3">
                    <Datepicker
                      name="date_start"
                      :label="$t('contracts.start_date')"
                      :default-value="props.contract?.date_start"
                      :placeholder="$t('contracts.start_date')"
                      :required="true"
                      :minDate="new Date(new Date().getFullYear() - 1, 0, 1).toISOString().split('T')[0]"
                      :maxDate="new Date(new Date().getFullYear() + 10, 11, 31).toISOString().split('T')[0]"
                      :error="errors.date_start"
                      class="mb-4 lg:mb-0"
                    />
                  </div>
                  <div class="relative lg:col-span-3">
                    <Datepicker
                      name="date_end"
                      :label="$t('contracts.end_date')"
                      :default-value="props.contract?.date_end"
                      :placeholder="$t('contracts.end_date')"
                      :required="true"
                      :minDate="new Date(new Date().getFullYear() - 1, 0, 1).toISOString().split('T')[0]"
                      :maxDate="new Date(new Date().getFullYear() + 10, 11, 31).toISOString().split('T')[0]"
                      :error="errors.date_end"
                      class="mb-4 lg:mb-0"
                    />
                  </div>
                  <div class="relative lg:col-span-12 mt-2 lg:mt-4">
                    <Wysiwyg
                      class="mt-1 block w-full"
                      name="content"
                      :label="$t('contracts.content')"
                      :default-value="props.contract?.content"
                      v-model="templateContent"
                      :required="true"
                      :error="errors.content"
                    />
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </Form>
    </LayoutBasic>
  </AppLayout>
</template>
