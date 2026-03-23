<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { AppLayout, LayoutBasic } from '@/layouts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button, ButtonDelete } from '@/components/ui/button'
import { LabelData } from '@/components/ui/basic'
import { Trash2, Pencil, FileMinus } from 'lucide-vue-next'
import { Files } from '@/components/blocks'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import type { BreadcrumbItem, Contract, File } from '@/types'

interface Props {
  contract: Contract
  files: File[]
}

const props = defineProps<Props>()
const { t } = useI18n()
const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('contracts.view_contract'), href: '/contracts/view' },
]

function reloadFiles() {
  router.reload({ only: ['files'] });
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Card>
        <CardHeader>
          <CardTitle class="lg:flex lg:items-center lg:justify-between">
            <div class="flex items-center gap-2">
              <FileMinus class="text-blue-600 w-5 h-5" />
              <span class="text-gray-900 text-xl leading-7 font-semibold">{{ props.contract.title }}</span>
            </div>
            <div class="mt-4 flex lg:mt-0 lg:ml-4 gap-x-6">
              <ButtonDelete :action="route('contracts.destroy', props.contract.id)">
                <Trash2 class="text-white"/>
                {{ $t('layout.delete') }}
              </ButtonDelete>
              <Link :href="route('contracts.edit', props.contract.id)">
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
            <div class="relative lg:col-span-6">
              <LabelData :label="$t('contracts.title_label')" :value="props.contract.title"/>
            </div>
            <div class="relative lg:col-span-6">
              <LabelData :label="$t('contracts.client')" :value="props.contract.client.name"/>
            </div>
            <div class="relative lg:col-span-3">
              <LabelData :label="$t('contracts.start_date')" :value="props.contract.date_start"/>
            </div>
            <div class="relative lg:col-span-3">
              <LabelData :label="$t('contracts.end_date')" :value="props.contract.date_end"/>
            </div>
            <div class="relative lg:col-span-3">
              <LabelData :label="$t('contracts.status')" :value="props.contract.status_name"/>
            </div>
          </div>
          <div class="w-full my-20">
            <div class="ProseMirror prose max-w-none focus:outline-none [&_h1]:text-4xl [&_h2]:text-3xl [&_h3]:text-2xl" v-html="props.contract.content"></div>
          </div>
        </CardContent>
      </Card>

      <Files :files="props.files" resource="contract" :parent-id="props.contract.id" @saved="reloadFiles" />
    </LayoutBasic>
  </AppLayout>
</template>
