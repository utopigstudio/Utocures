<script setup lang="ts">
import { watch } from 'vue'
import { AppLayout, LayoutBasic } from '@/layouts';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card'
import { Form, Link, useForm } from '@inertiajs/vue3'
import { InputText } from '@/components/ui/input'
import { Button } from '@/components/ui/button';
import { Repeatable } from '@/components/blocks'
import Option from './components/Option.vue'
import { BadgeCheck, X, Save } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n';
import type { BreadcrumbItem, Characteristic, CharacteristicOption } from '@/types'

interface Props {
  characteristic?: Characteristic
}

const props = defineProps<Props>()

const { t } = useI18n()

const breadcrumbItems: BreadcrumbItem[] = [
  { title: t('characteristics.create'), href: '/characteristics/create' },
]

const form = useForm({
  name: props.characteristic?.name ?? '',
  options: props.characteristic?.options ?? [],
})

const optionLine = (): CharacteristicOption => ({
  id: '',
  name: '',
})

watch(
  () => props.characteristic?.options,
  (val: CharacteristicOption[] | undefined) => {
    if (val) form.options = [...val]
  },
  { deep: true }
)
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Form :form="form" :method="props.characteristic ? 'put' : 'post'" :action="props.characteristic ? route('characteristics.update', props.characteristic.id) : route('characteristics.store')" class="space-y-6" v-slot="{ errors, processing }">
        <div class="grid grid-cols-1">
          <div class="relative lg:col-span-12">
            <Card>
              <CardHeader>
                <CardTitle class="text-xl md:flex md:items-center md:justify-between mb-4">
                  <div class="flex items-center gap-2 mb-4 md:mb-0">
                    <BadgeCheck class="text-blue-600 w-5 h-5" />
                    <span class="text-gray-900 text-2xl leading-8 font-bold">{{ props.characteristic ? $t('characteristics.edit') : $t('characteristics.create') }}</span>
                  </div>
                  <div class="flex flex-wrap justify-between gap-6">
                    <Link :href="route('characteristics.index')">
                      <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                        <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                      </Button>
                    </Link>
                    <Button type="submit" size="lg" :disabled="processing">
                      <Save class="size-4 text-white" />
                      {{ props.characteristic ? $t('layout.save_changes') : $t('layout.save') }}
                    </Button>
                  </div>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="text-sm mb-10 text-blue-gray-500"><p>{{ $t('characteristics.description') }}</p></div>
                <InputText
                  class="mt-1 block w-full"
                  name="name"
                  :label="t('characteristics.name')"
                  :default-value="props.characteristic?.name"
                  autocomplete="name"
                  :placeholder="t('characteristics.name')"
                  :required="true"
                  :error="errors.name"
                />
                <Repeatable
                  v-model="form.options"
                  name="options"
                  :item-template="optionLine"
                  :errors="errors"
                  :title="t('characteristics.options')"
                  customRemoveClass="my-0"
                >
                  <template #default="{ index, field }">
                    <Option :field="field" :index="index" />
                  </template>
                </Repeatable>
              </CardContent>
              <CardFooter>
                <CardTitle class="text-xl md:flex md:items-center md:justify-end w-full mb-4">
                  <div class="flex flex-wrap justify-between gap-6">
                    <Link :href="route('characteristics.index')">
                      <Button variant="outline" size="lg" class="flex items-center gap-2 text-blue-gray-600 border-2">
                        <X class="size-4" strokeWidth="3" /> {{ $t('layout.cancel') }}
                      </Button>
                    </Link>
                    <Button type="submit" size="lg" :disabled="processing">
                      <Save class="size-4 text-white" />
                      {{ props.characteristic ? $t('layout.save_changes') : $t('layout.save') }}
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
