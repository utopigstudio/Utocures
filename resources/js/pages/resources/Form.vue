<script setup lang="ts">
import { Button, ButtonDelete } from '@/components/ui/button'
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { InputText, InputTextarea } from '@/components/ui/input'
import { AppLayout, LayoutBasic } from '@/layouts'
import type { BreadcrumbItem, EntityResource } from '@/types'
import { Form, Link } from '@inertiajs/vue3'
import { LocateFixed, MapPinned, Save, Trash2, X } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import ResourceMap from './components/ResourceMap.vue'

interface Props {
  resource?: EntityResource
  types: string[]
}

interface GeocodeResult {
  latitude: number
  longitude: number
  display_name: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const name = ref(props.resource?.name ?? '')
const type = ref(props.resource?.type ?? '')
const address = ref(props.resource?.address ?? '')
const latitude = ref<number | null>(props.resource?.latitude ?? null)
const longitude = ref<number | null>(props.resource?.longitude ?? null)
const geocodedAddress = ref(props.resource?.address ?? '')
const resolvedAddress = ref(props.resource?.address ?? '')
const geocoding = ref(false)
const geocodingError = ref('')

const breadcrumbItems: BreadcrumbItem[] = [
  { title: props.resource ? t('resources.edit') : t('resources.create'), href: props.resource ? `/resources/${props.resource.id}/edit` : '/resources/create' },
]

const previewResource = computed<EntityResource[]>(() => {
  if (latitude.value === null || longitude.value === null) return []

  return [{
    id: props.resource?.id ?? 'preview',
    name: name.value || t('resources.unnamed_resource'),
    type: type.value || t('resources.resource_type'),
    address: resolvedAddress.value || address.value,
    latitude: latitude.value,
    longitude: longitude.value,
  }]
})

watch(address, (value) => {
  if (value.trim() !== geocodedAddress.value.trim()) {
    latitude.value = null
    longitude.value = null
    resolvedAddress.value = ''
    geocodingError.value = ''
  }
})

async function locateAddress(): Promise<void> {
  if (!address.value.trim()) {
    geocodingError.value = t('resources.address_required')
    return
  }

  geocoding.value = true
  geocodingError.value = ''

  try {
    const response = await fetch(route('resources.geocode', { address: address.value }), {
      headers: { Accept: 'application/json' },
    })
    const payload = await response.json()

    if (!response.ok) {
      throw new Error(payload.errors?.address?.[0] ?? t('resources.geocoding_error'))
    }

    const result = payload as GeocodeResult
    latitude.value = result.latitude
    longitude.value = result.longitude
    resolvedAddress.value = result.display_name
    geocodedAddress.value = address.value
  } catch (error) {
    geocodingError.value = error instanceof Error ? error.message : t('resources.geocoding_error')
  } finally {
    geocoding.value = false
  }
}
</script>

<template>
  <AppLayout>
    <LayoutBasic :breadcrumbs="breadcrumbItems">
      <Form
        :method="props.resource ? 'put' : 'post'"
        :action="props.resource ? route('resources.update', props.resource.id) : route('resources.store')"
        class="space-y-6"
        v-slot="{ errors, processing }"
      >
        <Card>
          <CardHeader>
            <CardTitle class="mb-4 text-xl lg:flex lg:items-center lg:justify-between">
              <div class="mb-4 flex items-center gap-2 lg:mb-0">
                <MapPinned class="size-5 text-blue-600" />
                <span class="text-2xl leading-8 font-bold text-gray-900">{{ props.resource ? $t('resources.edit') : $t('resources.new') }}</span>
              </div>
              <div class="flex flex-wrap justify-between gap-3">
                <Link :href="route('resources.index')">
                  <Button variant="outline" size="lg"><X class="size-4" />{{ $t('layout.cancel') }}</Button>
                </Link>
                <Button type="submit" size="lg" :disabled="processing">
                  <Save class="size-4" />{{ props.resource ? $t('layout.save_changes') : $t('layout.save') }}
                </Button>
                <ButtonDelete v-if="props.resource" size="lg" :action="route('resources.destroy', props.resource.id)">
                  <Trash2 class="size-4" />{{ $t('layout.delete') }}
                </ButtonDelete>
              </div>
            </CardTitle>
          </CardHeader>

          <CardContent>
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.85fr)]">
              <div class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                  <InputText
                    v-model="name"
                    name="name"
                    :label="$t('resources.name')"
                    :placeholder="$t('resources.name_placeholder')"
                    :required="true"
                    :error="errors.name"
                  />
                  <div>
                    <InputText
                      v-model="type"
                      name="type"
                      list="resource-types"
                      :label="$t('resources.type')"
                      :placeholder="$t('resources.type_placeholder')"
                      :required="true"
                      :error="errors.type"
                    />
                    <datalist id="resource-types">
                      <option v-for="resourceType in props.types" :key="resourceType" :value="resourceType"></option>
                    </datalist>
                  </div>
                </div>

                <div>
                  <InputText
                    v-model="address"
                    name="address"
                    :label="$t('resources.address')"
                    :placeholder="$t('resources.address_placeholder')"
                    autocomplete="street-address"
                    :required="true"
                    :error="errors.address || geocodingError"
                  />
                  <div class="mt-2 flex flex-col items-start gap-2 sm:flex-row sm:items-center">
                    <Button type="button" variant="secondary" :disabled="geocoding || !address.trim()" @click="locateAddress">
                      <LocateFixed class="size-4" />
                      {{ geocoding ? $t('resources.locating') : $t('resources.locate') }}
                    </Button>
                    <p class="text-xs text-gray-500">{{ $t('resources.geocoding_help') }}</p>
                  </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                  <InputText name="phone" type="tel" :label="$t('resources.phone')" :default-value="props.resource?.phone ?? ''" autocomplete="tel" :error="errors.phone" />
                  <InputText name="email" type="email" :label="$t('resources.email')" :default-value="props.resource?.email ?? ''" autocomplete="email" :error="errors.email" />
                </div>

                <InputTextarea
                  name="description"
                  :label="$t('resources.description')"
                  :default-value="props.resource?.description ?? ''"
                  :placeholder="$t('resources.description_placeholder')"
                  :error="errors.description"
                />
              </div>

              <div class="overflow-hidden rounded-xl border bg-gray-50">
                <div v-if="previewResource.length" class="h-full min-h-[430px]">
                  <ResourceMap :resources="previewResource" :selected-id="previewResource[0].id" />
                </div>
                <div v-else class="flex min-h-[430px] flex-col items-center justify-center p-8 text-center">
                  <div class="mb-4 rounded-full bg-blue-50 p-4">
                    <MapPinned class="size-9 text-blue-600" />
                  </div>
                  <p class="font-semibold text-gray-800">{{ $t('resources.map_preview_empty') }}</p>
                  <p class="mt-2 max-w-sm text-sm leading-6 text-gray-500">{{ $t('resources.map_preview_help') }}</p>
                </div>
              </div>
            </div>
          </CardContent>

          <CardFooter class="justify-end gap-3">
            <Link :href="route('resources.index')">
              <Button variant="outline" size="lg"><X class="size-4" />{{ $t('layout.cancel') }}</Button>
            </Link>
            <Button type="submit" size="lg" :disabled="processing">
              <Save class="size-4" />{{ props.resource ? $t('layout.save_changes') : $t('layout.save') }}
            </Button>
          </CardFooter>
        </Card>
      </Form>
    </LayoutBasic>
  </AppLayout>
</template>
