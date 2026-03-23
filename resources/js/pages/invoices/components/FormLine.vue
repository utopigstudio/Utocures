<script setup lang="ts">
import { InputText, InputNumber, Select } from '@/components/ui/input'
import { useI18n } from 'vue-i18n'
import type { FieldBinding } from '@/types'

interface Props {
  field: (key: string) => FieldBinding
  index: number
}

const props = defineProps<Props>()
const { t } = useI18n()
const tax_types = [
  { label: t('invoices.vat_21'), value: '21' },
  { label: t('invoices.vat_10'), value: '10' },
  { label: t('invoices.vat_4'), value: '4' },
  { label: t('invoices.no_tax'), value: '0' },
]
</script>

<template>
  <InputText v-bind="props.field('concept')" :placeholder="t('invoices.concept')" labelClass="leading-5 font-semibold text-blue-gray-900 w-full" containerClass="w-auto lg:w-100" :label="props.index === 0 ? t('invoices.concept') : ''" />
  <InputNumber v-bind="props.field('quantity')" :placeholder="t('invoices.quantity')" :label="props.index === 0 ? t('invoices.quantity') : ''" />
  <InputNumber v-bind="props.field('price')" :placeholder="t('invoices.price')" :label="props.index === 0 ? t('invoices.price') : ''" />
  <InputNumber v-bind="props.field('discount')" :placeholder="t('invoices.discount')" :label="props.index === 0 ? t('invoices.discount') : ''" />
  <Select v-bind="props.field('tax_type')" :placeholder="t('invoices.tax_type')" containerClass="w-auto lg:w-100" :label="props.index === 0 ? t('invoices.tax_type') : ''" :options="tax_types" />
</template>
