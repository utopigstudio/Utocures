<script setup lang="ts">
import { ref, watch } from 'vue'
import { AppLayout, LayoutBasic } from '@/layouts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Form, Link } from '@inertiajs/vue3'
import { Datepicker, SelectClients } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Repeatable } from '@/components/blocks'
import FormLine from './components/FormLine.vue'
import { FileMinus, X, Save } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n';
import type { BreadcrumbItem, Invoice, InvoiceLine } from '@/types'

interface Props {
  invoice?: Invoice
  client_id?: string
}
const props = defineProps<Props>()
const lines = ref<InvoiceLine[]>(props.invoice?.lines ?? [])
const total_base = ref(0)
const total_tax = ref(0)
const total_discount = ref(0)
const total_invoice = ref(0)

const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('invoices.create_invoice'), href: '/invoices/create' },
]

const invoiceLine = (): InvoiceLine => ({
  concept: '',
  price: 0,
  quantity: 0,
  discount: 0,
  tax_type: '21',
  subtotal: 0,
})

watch(
  () => props.invoice?.lines,
  (val: InvoiceLine[] | undefined) => {
    if (val) lines.value = [...val]
  },
  { deep: true }
)

watch(
  () => lines.value,
  (val: InvoiceLine[]) => {
    let base = 0
    let tax = 0
    let discount = 0
    val.forEach((line) => {
      const lineBase = line.price * line.quantity
      const lineDiscount = lineBase * (line.discount / 100)
      const lineTax = (lineBase - lineDiscount) * (parseFloat(line.tax_type) / 100)
      base += lineBase
      discount += lineDiscount
      tax += lineTax
    })
    total_base.value = parseFloat(base.toFixed(2))
    total_discount.value = parseFloat(discount.toFixed(2))
    total_tax.value = parseFloat(tax.toFixed(2))
    total_invoice.value = parseFloat((base - discount + tax).toFixed(2))
  },
  { deep: true, immediate: true }
)
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Form
        :method="props.invoice ? 'put' : 'post'"
        :action="props.invoice ? route('invoices.update', props.invoice.id) : route('invoices.store')"
        class="space-y-6"
        v-slot="{ errors, processing }"
      >
        <div class="grid grid-cols-1">
          <div class="relative lg:col-span-12">
            <Card>
              <CardHeader>
                <CardTitle class="text-xl lg:flex lg:items-center lg:justify-between mb-4">
                  <div class="flex items-center gap-2 mb-4 lg:mb-0">
                    <FileMinus class="text-blue-600 w-5 h-5" />
                    <span class="text-gray-900 text-xl leading-7 font-semibold">{{ props.invoice ? $t('invoices.edit_invoice') : $t('invoices.create_invoice') }}</span>
                  </div>
                  <div class="flex flex-wrap justify-between gap-6">
                    <Link :href="route('invoices.index')">
                      <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                        <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                      </Button>
                    </Link>
                    <Button type="submit" size="lg" :disabled="processing">
                      <Save class="size-4 text-white" />
                      {{ props.invoice ? $t('layout.save_changes') : $t('layout.save') }}
                    </Button>
                  </div>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                  <div class="relative lg:col-span-4">
                    <SelectClients
                      name="client_id"
                      :label="$t('invoices.client')"
                      class="mt-1 w-full"
                      :default-value="props.client_id ?? props.invoice?.client.id"
                      :required="true"
                      :error="errors.client_id"
                    />
                  </div>
                  <div class="relative lg:col-span-4">
                    <Datepicker
                      class="mt-1 block w-full"
                      name="date"
                      :label="$t('invoices.date')"
                      :default-value="props.invoice?.date"
                      :required="true"
                      :minDate="new Date(new Date().getFullYear() - 1, 0, 1).toISOString().split('T')[0]"
                      :maxDate="new Date(new Date().getFullYear() + 1, 11, 31).toISOString().split('T')[0]"
                      :error="errors.date"
                    />
                  </div>
                  <div class="relative lg:col-span-4 mb-4">
                    <Datepicker
                      class="mt-1 block w-full"
                      name="due_date"
                      :label="$t('invoices.due_date')"
                      :default-value="props.invoice?.due_date"
                      :required="true"
                      :minDate="new Date(new Date().getFullYear() - 1, 0, 1).toISOString().split('T')[0]"
                      :maxDate="new Date(new Date().getFullYear() + 1, 11, 31).toISOString().split('T')[0]"
                      :error="errors.due_date"
                    />
                  </div>
                </div>
                <Repeatable
                  v-model="lines"
                  name="lines"
                  :item-template="invoiceLine"
                  :errors="errors"
                >
                  <template #default="{ index, field }">
                    <FormLine :field="field" :index="index" />
                  </template>
                </Repeatable>
                <div class="mt-8">
                  <h3 class="text-xl leading-7 font-bold text-blue-gray-900 pb-4 mb-4 border-b border-blue-gray-200">{{ $t('invoices.total_invoice') }}</h3>
                  <div class="space-y-4 mb-2 border-b border-blue-gray-200 pb-2">
                    <div class="flex items-center justify-between">
                      <span class="text-sm leading-5 font-normal text-blue-gray-600">{{ $t('invoices.taxable_base') }}:</span>
                      <span class="text-sm leading-5 font-normal text-blue-gray-900">{{ total_base }} €</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-sm leading-5 font-normal text-blue-gray-600">{{ $t('invoices.discount') }}:</span>
                      <span class="text-sm leading-5 font-normal text-blue-gray-900">{{ total_discount }} €</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-sm leading-5 font-normal text-blue-gray-600">{{ $t('invoices.vat') }}:</span>
                      <span class="text-sm leading-5 font-normal text-blue-gray-900">{{ total_tax }} €</span>
                    </div>
                  </div>
                  <div class="flex items-center justify-between mb-6">
                    <span class="text-sm leading-5 font-bold text-blue-gray-900">{{ $t('layout.total') }}:</span>
                    <span class="text-sm leading-5 font-bold text-blue-gray-900">{{ total_invoice }} €</span>
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
