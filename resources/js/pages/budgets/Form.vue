<script setup lang="ts">
import { ref, watch } from 'vue'
import { AppLayout, LayoutBasic } from '@/layouts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Form, Link } from '@inertiajs/vue3'
import { SelectClients, Select, Datepicker, Wysiwyg, SelectTemplate } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Repeatable } from '@/components/blocks'
import FormLine from './components/FormLine.vue'
import { FileMinus, X, Save } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import type { BreadcrumbItem, Budget, BudgetLine, Status } from '@/types'

interface Props {
  budget?: Budget
  statuses: Status[],
  client_id?: string
}

const props = defineProps<Props>()
const lines = ref<BudgetLine[]>(props.budget?.lines ?? [])
const selectedTemplateId = ref('')
const templateContent = ref('')
const total_base = ref(0)
const total_tax = ref(0)
const total_discount = ref(0)
const total_budget = ref(0)

const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: props.budget ? t('budgets.edit_budget') : t('budgets.new_budget'), href: '/budgets/create' },
]


const budgetLine = (): BudgetLine => ({
  concept: '',
  price: 0,
  quantity: 0,
  discount: 0,
  tax_type: '21',
  subtotal: 0,
})

watch(
  () => props.budget?.lines,
  (val: BudgetLine[] | undefined) => {
    if (val) lines.value = [...val]
  },
  { deep: true }
)

watch(
  () => lines.value,
  (val: BudgetLine[]) => {
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
    total_budget.value = parseFloat((base - discount + tax).toFixed(2))
  },
  { deep: true, immediate: true }
)
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Form
        :method="props.budget ? 'put' : 'post'"
        :action="props.budget ? route('budgets.update', props.budget.id) : route('budgets.store')"
        class="space-y-6"
        v-slot="{ errors, processing }"
      >
        <div class="grid grid-cols-1">
          <div class="relative">
            <Card>
              <CardHeader>
                <CardTitle class="text-xl xl:flex xl:items-center xl:justify-between mb-4">
                  <div class="flex items-center gap-2 mb-4 xl:mb-0">
                    <FileMinus class="text-blue-600 w-5 h-5" />
                    <span class="text-gray-900 text-xl leading-7 font-semibold">{{ props.budget ? $t('budgets.edit_budget') : $t('budgets.new_budget') }}</span>
                  </div>
                  <div class="flex flex-wrap justify-between gap-6">
                    <Link :href="props.budget ? route('budgets.show', props.budget.id) : route('budgets.index')">
                      <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                        <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                      </Button>
                    </Link>
                    <Button type="submit" size="lg" :disabled="processing">
                      <Save class="size-4 text-white" />
                      {{ props.budget ? $t('layout.save_changes') : $t('layout.save') }}
                    </Button>
                  </div>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="grid grid-cols-1 gap-x-4 xl:grid-cols-12 my-4">
                  <div class="relative xl:col-span-3">
                    <SelectClients
                      name="client_id"
                      :label="$t('budgets.client')"
                      class="mt-1 w-full mb-4 xl:mb-0"
                      :default-value="props.client_id ?? (props.budget?.client_id ?? props.budget?.client_name)"
                      :required="true"
                      :error="errors.client_id"
                      :free-client="true"
                    />
                  </div>
                  <div class="relative xl:col-span-3">
                    <SelectTemplate
                      type="budgets"
                      :label="$t('budgets.budget_template')"
                      v-model="selectedTemplateId"
                      @template-selected="templateContent = $event"
                      class="mb-4 xl:mb-0"
                    />
                  </div>
                  <div class="relative xl:col-span-3">
                    <Datepicker
                      class="block w-full mb-4 xl:mb-0"
                      type="date"
                      name="due_date"
                      :label="$t('budgets.due_date')"
                      :default-value="props.budget?.due_date"
                      :placeholder="$t('budgets.due_date')"
                      :required="true"
                      :minDate="new Date(new Date().getFullYear() - 1, 0, 1).toISOString().split('T')[0]"
                      :maxDate="new Date(new Date().getFullYear() + 5, 11, 31).toISOString().split('T')[0]"
                      :error="errors.due_date"
                    />
                  </div>
                  <div class="relative xl:col-span-3">
                    <Select
                      name="status"
                      :label="$t('budgets.status')"
                      :default-value="props.budget?.status"
                      :options="props.statuses.map(m => ({ label: m.name, value: m.id }))"
                      :placeholder="$t('budgets.status')"
                      :required="true"
                      :error="errors.status"
                      class="mb-4 xl:mb-0"
                    />
                  </div>
                  <div class="relative xl:col-span-12 mt-4">
                    <Wysiwyg
                      class="mt-1 block w-full mb-4 xl:mb-0"
                      name="content"
                      :label="$t('budgets.content')"
                      :default-value="props.budget?.content"
                      v-model="templateContent"
                      :required="true"
                      :error="errors.content"
                    />
                  </div>
                </div>
                <Repeatable
                  v-model="lines"
                  name="lines"
                  :item-template="budgetLine"
                  :errors="errors"
                >
                  <template #default="{ index, field }">
                    <FormLine :field="field" :index="index" />
                  </template>
                </Repeatable>

                <div class="mt-8">
                  <h3 class="text-xl leading-7 font-bold text-blue-gray-900 pb-4 mb-4 border-b border-blue-gray-200">{{ $t('budgets.total_budget') }}</h3>
                  <div class="space-y-4 mb-2 border-b border-blue-gray-200 pb-2">
                    <div class="flex items-center justify-between">
                      <span class="text-sm leading-5 font-normal text-blue-gray-600">{{ $t('budgets.taxable_base') }}:</span>
                      <span class="text-sm leading-5 font-normal text-blue-gray-900">{{ total_base }} €</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-sm leading-5 font-normal text-blue-gray-600">{{ $t('budgets.discount') }}:</span>
                      <span class="text-sm leading-5 font-normal text-blue-gray-900">{{ total_discount }} €</span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-sm leading-5 font-normal text-blue-gray-600">{{ $t('budgets.vat') }}:</span>
                      <span class="text-sm leading-5 font-normal text-blue-gray-900">{{ total_tax }} €</span>
                    </div>
                  </div>
                  <div class="flex items-center justify-between mb-6">
                    <span class="text-sm leading-5 font-bold text-blue-gray-900">{{ $t('layout.total') }}:</span>
                    <span class="text-sm leading-5 font-bold text-blue-gray-900">{{ total_budget }} €</span>
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
