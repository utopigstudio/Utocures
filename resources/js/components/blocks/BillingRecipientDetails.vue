<script setup lang="ts">
import type { BillingRecipient } from '@/types'

defineProps<{
  recipient: BillingRecipient
}>()
</script>

<template>
  <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
    <div>
      <dt class="text-blue-gray-500">{{ $t('clients.billing_name') }}</dt>
      <dd class="mt-1 font-medium text-blue-gray-900">{{ recipient.name }}</dd>
    </div>
    <div>
      <dt class="text-blue-gray-500">{{ $t('clients.cif_nif') }}</dt>
      <dd class="mt-1 text-blue-gray-900">{{ recipient.cif_nif }}</dd>
    </div>
    <div>
      <dt class="text-blue-gray-500">{{ $t('clients.address') }}</dt>
      <dd class="mt-1 text-blue-gray-900">{{ recipient.address }}, {{ recipient.zip_code }} {{ recipient.city }} ({{ recipient.country_id.toUpperCase() }})</dd>
    </div>
    <div v-if="recipient.email">
      <dt class="text-blue-gray-500">{{ $t('clients.billing_email') }}</dt>
      <dd class="mt-1 break-words text-blue-gray-900">{{ recipient.email }}</dd>
    </div>
  </dl>
</template>
