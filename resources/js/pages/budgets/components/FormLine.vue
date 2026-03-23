<script setup lang="ts">
import { ref } from 'vue'
import { InputText, Select, InputNumber } from '@/components/ui/input'
import { useI18n } from 'vue-i18n'
import type { FieldBinder, BudgetLine, AnyItem } from '@/types'

interface Props {
  field?: FieldBinder
  index: number
  line?: BudgetLine | AnyItem
}

defineProps<Props>()

const { t } = useI18n()

const tax_types = ref([
  { label: t('invoices.vat_21'), value: '21' },
  { label: t('invoices.vat_10'), value: '10' },
  { label: t('invoices.vat_4'), value: '4' },
  { label: t('invoices.no_tax'), value: '0' },
])
</script>

<template>
  <template v-if="field">
    <InputText v-bind="field?.('concept')" :placeholder="$t('budgets.concept')" labelClass="text-sm leading-5 font-semibold text-blue-gray-900" containerClass="w-auto lg:w-100" :label="index === 0 ? $t('budgets.concept') : ''" />
    <InputNumber v-bind="field?.('quantity')" :placeholder="$t('budgets.quantity')" :label="index === 0 ? $t('budgets.quantity') : ''" />
    <InputNumber v-bind="field?.('price')" :placeholder="$t('budgets.price')" :label="index === 0 ? $t('budgets.price') : ''" />
    <InputNumber v-bind="field?.('discount')" :placeholder="$t('budgets.discount')" :label="index === 0 ? $t('budgets.discount') : ''" />
    <Select v-bind="field?.('tax_type')" :options="tax_types" :placeholder="$t('budgets.tax_type')" :label="index === 0 ? $t('budgets.tax_type') : ''" />
  </template>
  <template v-else>
    <InputText :default-value="line?.concept" labelClass="text-sm leading-5 font-semibold text-blue-gray-900" containerClass="w-auto lg:w-100" :label="index === 0 ? $t('budgets.concept') : ''" :disabled="true" />
    <InputNumber :default-value="line?.quantity" :placeholder="$t('budgets.quantity')" :label="index === 0 ? $t('budgets.quantity') : ''" :disabled="true" />
    <InputNumber :default-value="line?.price" :placeholder="$t('budgets.price')" :label="index === 0 ? $t('budgets.price') : ''" :disabled="true" />
    <InputNumber :default-value="line?.discount" :placeholder="$t('budgets.discount')" :label="index === 0 ? $t('budgets.discount') : ''" :disabled="true" />
    <InputText :default-value="line?.tax_type" :placeholder="$t('budgets.tax_type')" :label="index === 0 ? $t('budgets.tax_type') : ''" :disabled="true" />
  </template>
</template>
