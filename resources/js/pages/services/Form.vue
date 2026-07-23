<script setup lang="ts">
import { ref, watch } from 'vue';
import { AppLayout, LayoutBasic } from '@/layouts';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card'
import { Form, Link } from '@inertiajs/vue3'
import { InputText, Select, InputTextarea, SelectIcons, InputNumber } from '@/components/ui/input'
import { Button, ButtonDelete } from '@/components/ui/button';
import { darken } from '@/lib/utils'
import { Repeatable } from '@/components/blocks'
import Task from './components/Task.vue'
import { Trash2, Euro, Percent, Save, X, BadgeCheck } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n';
import type { BreadcrumbItem, Service, Color, Task as TaskType } from '@/types'

interface Props {
  service?: Service
  colors: Color[]
}

const props = defineProps<Props>()
const tasks = ref<TaskType[]>(props.service?.tasks ?? [])

const { t } = useI18n();

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('services.create'), href: '/services/create' },
]

const serviceTask = (): TaskType => ({
  id: '',
  name: '',
})

watch(
  () => props.service?.tasks,
  (val: TaskType[] | undefined) => {
    if (val) tasks.value = [...val]
  },
  { deep: true }
)
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Form :method="props.service ? 'put' : 'post'" :action="props.service ? route('services.update', props.service.id) : route('services.store')" class="space-y-6" v-slot="{ errors, processing }">
        <div class="grid grid-cols-1">
          <div class="relative lg:col-span-12">
            <Card>
              <CardHeader>
                <CardTitle class="text-xl md:flex md:items-center md:justify-between mb-4">
                  <div class="flex items-center gap-2 mb-4 md:mb-0">
                    <BadgeCheck class="text-blue-600 w-5 h-5" />
                    <span class="text-gray-900 text-2xl leading-8 font-bold">{{ props.service ? $t('services.edit_service') : $t('services.new_service') }}</span>
                  </div>
                  <div class="flex flex-wrap justify-between gap-6">
                    <Link :href="route('services.index')">
                      <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                        <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                      </Button>
                    </Link>
                    <Button type="submit" size="lg" :disabled="processing">
                      <Save class="size-4 text-white" />
                      {{ props.service ? $t('layout.save_changes') : $t('layout.save') }}
                    </Button>
                    <ButtonDelete size="lg" v-if="props.service" :action="route('services.destroy', props.service.id)">
                      <Trash2 class="text-white"/>
                      {{ $t('layout.delete') }}
                    </ButtonDelete>
                  </div>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                  <div class="relative lg:col-span-6">
                    <InputText
                      class="mt-1 block w-full"
                      name="name"
                      :label="t('services.name')"
                      :default-value="props.service?.name"
                      autocomplete="name"
                      :placeholder="t('services.name')"
                      :required="true"
                      :error="errors.name"
                    />
                  </div>
                  <div class="relative lg:col-span-3">
                    <SelectIcons
                      name="icon_slug"
                      :label="t('services.icon')"
                      class="mt-1 w-full"
                      :default-value="props.service?.icon_slug"
                      :required="true"
                      :error="errors.icon_slug"
                    />
                  </div>
                  <div class="relative lg:col-span-3">
                    <Select
                      name="color"
                      :label="t('services.color')"
                      class="mt-1 w-full mb-4"
                      :default-value="props.service?.color"
                      :options="props.colors.map(c => ({ label: c.name, value: c.id }))"
                      :required="true"
                      :error="errors.color"
                    >
                      <template #valueEnd="{ value = '' }">
                        <span
                          class="inline-block w-4 h-4 rounded-full align-middle border border-gray-300"
                          :style="{ backgroundColor: value, borderColor: darken(value, 20) }"
                        ></span>
                      </template>
                      <template #valueOptionEnd="{ value = '' }">
                        <span
                          class="inline-block w-4 h-4 rounded-full align-middle border border-gray-300"
                          :style="{ backgroundColor: value, borderColor: darken(value, 20) }"
                        ></span>
                      </template>
                    </Select>
                  </div>
                </div>

                <InputTextarea
                  class="mt-1 block w-full"
                  name="description"
                  :label="t('services.description')"
                  :default-value="props.service?.description"
                  autocomplete="description"
                  :placeholder="t('services.description')"
                  :required="false"
                  :error="errors.description"
                />
                
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                  <div class="relative lg:col-span-6">
                    <InputNumber
                      class="mt-1 block w-full"
                      name="price"
                      :label="t('services.price')"
                      :default-value="props.service?.price"
                      autocomplete="price"
                      :placeholder="t('services.price')"
                      :required="true"
                      :error="errors.price"
                      :icon="Euro"
                    />
                  </div>
                  <div class="relative lg:col-span-6">
                    <InputNumber
                      class="mt-1 block w-full"
                      name="discount_partner"
                      :label="t('services.discount_partner')"
                      :default-value="props.service?.discount_partner"
                      autocomplete="discount_partner"
                      :placeholder="t('services.discount_partner')"
                      :required="false"
                      :error="errors.discount_partner"
                      :icon="Percent"
                    />
                  </div>
                  <div class="relative lg:col-span-12">
                    <Repeatable
                      v-model="tasks"
                      name="tasks"
                      :item-template="serviceTask"
                      :errors="errors"
                      :title="t('services.service_tasks')"
                    >
                      <template #default="{ index, field }">
                        <Task :field="field" :index="index" />
                      </template>
                    </Repeatable>
                  </div>
                </div>
              </CardContent>
              <CardFooter>
                <CardTitle class="text-xl md:flex md:items-center md:justify-end w-full mb-4">
                  <div class="flex flex-wrap justify-between gap-6">
                    <Link :href="route('services.index')">
                      <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                        <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                      </Button>
                    </Link>
                    <Button type="submit" size="lg" :disabled="processing">
                      <Save class="size-4 text-white" />
                      {{ props.service ? $t('layout.save_changes') : $t('layout.save') }}
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
