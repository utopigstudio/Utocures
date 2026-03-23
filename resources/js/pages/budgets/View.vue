<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { AppLayout, LayoutBasic } from '@/layouts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button, ButtonDelete } from '@/components/ui/button'
import { LabelData } from '@/components/ui/basic'
import { Trash2, Pencil, FileMinus } from 'lucide-vue-next'
import { Files } from '@/components/blocks'
import { Link } from '@inertiajs/vue3'
import { Repeatable } from '@/components/blocks'
import FormLine from './components/FormLine.vue'
import { useI18n } from 'vue-i18n'
import type { BreadcrumbItem, Budget, BudgetLine, File } from '@/types'

interface Props {
  budget: Budget
  files: File[]
}

const props = defineProps<Props>()
const total_base = ref(0)
const total_tax = ref(0)
const total_discount = ref(0)
const total_budget = ref(0)
const lines = ref<BudgetLine[]>([...props.budget.lines])

const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('budgets.view_budget'), href: '/budgets/view' },
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

function reloadFiles() {
  router.reload({ only: ['files'] });
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Card>
        <CardHeader>
          <CardTitle class="lg:flex lg:items-center lg:justify-between mb-4">
            <div class="flex items-center gap-2">
              <FileMinus class="text-blue-600 w-5 h-5" />
              <span class="text-gray-900 text-xl leading-7 font-semibold">{{ $t('budgets.view_budget') }}</span>
            </div>
            <div class="mt-4 flex lg:mt-0 lg:ml-4 gap-x-6">
              <ButtonDelete :action="route('budgets.destroy', props.budget.id)">
                <Trash2 class="text-white"/>
                {{ $t('layout.delete') }}
              </ButtonDelete>
              <Link :href="route('budgets.edit', props.budget.id)">
                <Button>
                  <Pencil class="text-white"/>
                  {{ $t('layout.edit') }}
                </Button>
              </Link>
            </div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 gap-x-4 lg:grid-cols-12 my-4 pb-4 border-b border-blue-gray-200">
            <div class="relative lg:col-span-12">
              <LabelData :label="$t('budgets.client')" :value="props.budget.client_name"/>
            </div>
            <div class="relative lg:col-span-3">
              <LabelData :label="$t('budgets.date')" :value="props.budget.created_at"/>
            </div>
            <div class="relative lg:col-span-3">
              <LabelData :label="$t('budgets.due_date')" :value="props.budget.due_date"/>
            </div>
            <div class="relative lg:col-span-3">
              <LabelData :label="$t('budgets.status')" :value="props.budget.status_name"/>
            </div>
          </div>
          <div class="w-full my-20">
            <div class="ProseMirror prose max-w-none focus:outline-none [&_h1]:text-4xl [&_h2]:text-3xl [&_h3]:text-2xl" v-html="props.budget.content"></div>
          </div>
          <Repeatable
            v-model="lines"
            name="lines"
            :item-template="budgetLine"
            :disable-actions="true"
          >
            <template #default="{ index, model }">
              <FormLine :line="model" :index="index" />
            </template>
          </Repeatable>
          <div class="mt-8">
            <h3 class="text-xl leading-7 font-bold text-blue-gray-900 pb-4 mb-4 border-b border-blue-gray-200">{{
              $t('budgets.total_budget') }}</h3>
            <div class="space-y-4 mb-2 border-b border-blue-gray-200 pb-2">
              <div class="flex items-center justify-between">
                <span class="text-sm leading-5 font-normal text-blue-gray-600">{{ $t('budgets.taxable_base')
                  }}:</span>
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

      <Files :files="props.files" resource="budget" :parent-id="props.budget.id" @saved="reloadFiles" />
    </LayoutBasic>
  </AppLayout>
</template>
