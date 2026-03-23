<script setup lang="ts">
import { AppLayout, LayoutBasic } from '@/layouts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useI18n } from 'vue-i18n'
import { Link } from '@inertiajs/vue3'
import { X } from 'lucide-vue-next'
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, Audit } from '@/types'

interface Props {
  audit: Audit
}

defineProps<Props>()

const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('audit.view'), href: '/audits/view' },
]

const fullList = (arr: any[]) => {
  if (arr.length === 0) {
    return ''
  }

  const hasNames = arr[0] && typeof arr[0] === 'object' && ('id' in arr[0] || 'name' in arr[0])

  if (hasNames) {
    return arr.map(item => item.name ?? 'sin-nombre').join('\n')
  }

  return arr
}

const pretty = (value: any) => {
  if (!Array.isArray(value)) {
    return typeof value === 'object'
      ? JSON.stringify(value, null, 2)
      : String(value)
  }

  return `${value.length} ${t('audit.items')}`
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">

      <Card class="w-full mb-6">
        <CardHeader :border="false">
          <CardTitle class="text-xl flex justify-between items-center">
            {{ $t('audit.general_information') }}
            <Link :href="route('audits.index')">
              <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
              </Button>
            </Link>
          </CardTitle>
        </CardHeader>

        <CardContent class="space-y-3">
          <div><strong>{{ $t('audit.action') }}:</strong> {{ audit.action }}</div>
          <div><strong>{{ $t('audit.resource') }}:</strong> {{ audit.resource }}</div>
          <div><strong>{{ $t('audit.resource_name') }}:</strong> {{ audit.resource_name }}</div>
          <div><strong>{{ $t('audit.user') }}:</strong> {{ audit.user?.name }}</div>
          <div><strong>{{ $t('audit.date') }}:</strong> {{ audit.created_at_formatted }}</div>
        </CardContent>
      </Card>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle class="text-xl">{{ $t('audit.before') }}</CardTitle>
          </CardHeader>

          <CardContent>
            <div v-if="Object.keys(audit.old_values || {}).length === 0" class="text-gray-500">
              {{ $t('audit.no_previous_data') }}
            </div>
            <div v-for="(value, field) in audit.old_values" :key="field" class="mb-4">
              <div>
                <strong class="block mb-1">{{ $t(audit.trans_prefix + 's.' + field) }}</strong>
                <details v-if="Array.isArray(value)" class="bg-gray-50 rounded p-2">
                  <summary class="cursor-pointer text-sm text-gray-700">
                    {{ pretty(value) }}
                  </summary>
                  <pre class="mt-2 bg-gray-100 p-3 rounded text-sm whitespace-pre-wrap">{{ fullList(value) }}</pre>
                </details>
                <pre v-else class="mt-2 bg-gray-100 p-3 rounded text-sm whitespace-pre-wrap">{{ typeof value === 'object' ? JSON.stringify(value, null, 2) : value }}</pre>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-xl">{{ $t('audit.after') }}</CardTitle>
          </CardHeader>

          <CardContent>
            <div v-if="Object.keys(audit.new_values || {}).length === 0" class="text-gray-500">
              {{ $t('audit.no_new_data') }}
            </div>
            <div v-for="(value, field) in audit.new_values" :key="field" class="mb-4">
              <div>
                <strong class="block mb-1">{{ $t(audit.trans_prefix + 's.' + field) }}</strong>
                <details v-if="Array.isArray(value)" class="bg-gray-50 rounded p-2">
                  <summary class="cursor-pointer text-sm text-gray-700">
                    {{ pretty(value) }}
                  </summary>
                  <pre class="mt-2 bg-gray-100 p-3 rounded text-sm whitespace-pre-wrap">{{ fullList(value) }}</pre>
                </details>
                <pre v-else class="mt-2 bg-gray-100 p-3 rounded text-sm whitespace-pre-wrap">{{ typeof value === 'object' ? JSON.stringify(value, null, 2) : value }}</pre>
              </div>
            </div>
          </CardContent>
        </Card>

      </div>
    </LayoutBasic>
  </AppLayout>
</template>
