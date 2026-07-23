<script setup lang="ts">
import { AppLayout, LayoutBasic } from '@/layouts';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card'
import { Form, Link } from '@inertiajs/vue3'
import { InputText, Wysiwyg } from '@/components/ui/input'
import { Button } from '@/components/ui/button';
import { BadgeCheck, X, Save } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n';
import type { BreadcrumbItem, BudgetTemplate } from '@/types'

interface Props {
  budget_template?: BudgetTemplate
}

const props = defineProps<Props>()

const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('budget_templates.create'), href: '/budget-templates/create' },
]
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Form :method="props.budget_template ? 'put' : 'post'" :action="props.budget_template ? route('budget-templates.update', props.budget_template.id) : route('budget-templates.store')" class="space-y-6" v-slot="{ errors, processing }">
        <div class="grid grid-cols-1">
          <div class="relative lg:col-span-12">
            <Card>
              <CardHeader>
                <CardTitle class="text-xl lg:flex lg:items-center lg:justify-between mb-4">
                  <div class="flex items-center gap-2 mb-4 lg:mb-0">
                    <BadgeCheck class="text-blue-600 w-5 h-5" />
                    <span class="text-gray-900 text-2xl leading-8 font-bold">{{ props.budget_template ? $t('budget_templates.edit') : $t('budget_templates.create') }}</span>
                  </div>
                  <div class="flex flex-wrap justify-between gap-6">
                    <Link :href="route('budget-templates.index')">
                      <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                        <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                      </Button>
                    </Link>
                    <Button type="submit" size="lg" :disabled="processing">
                      <Save class="size-4 text-white" />
                      {{ props.budget_template ? $t('layout.save_changes') : $t('layout.save') }}
                    </Button>
                  </div>
                </CardTitle>
              </CardHeader>

              <CardContent>

                <InputText
                  class="mt-1 block w-full mb-4 lg:mb-0"
                  name="name"
                  :label="t('budget_templates.name')"
                  :default-value="props.budget_template?.name"
                  autocomplete="name"
                  :placeholder="t('budget_templates.name')"
                  :required="true"
                  :error="errors.name"
                />

                <Wysiwyg
                  class="mt-1 block w-full mb-4 lg:mb-0"
                  name="content"
                  :label="t('budget_templates.content')"
                  :default-value="props.budget_template?.content"
                  :required="true"
                  :error="errors.content"
                />

              </CardContent>
              <CardFooter>
                <CardTitle class="text-xl lg:flex lg:items-center lg:justify-end w-full mb-4">
                  <div class="flex flex-wrap justify-between gap-6">
                    <Link :href="route('budget-templates.index')">
                      <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                        <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                      </Button>
                    </Link>
                    <Button type="submit" size="lg" :disabled="processing">
                      <Save class="size-4 text-white" />
                      {{ props.budget_template ? $t('layout.save_changes') : $t('layout.save') }}
                    </Button>
                  </div>
                </CardTitle>
              </CardFooter>
            </Card>
          </div>
        </div>
      </Form>
    </LayoutBasic>
  </AppLayout>
</template>