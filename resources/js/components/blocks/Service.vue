<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Link } from '@inertiajs/vue3'
import { PencilLine  } from 'lucide-vue-next'
import type { Service } from '@/types'
import { darken } from '@/lib/utils'

interface Props {
  service: Service
}

defineProps<Props>();
</script>

<template>
  <Card class="py-0 gap-3">
    <CardHeader class="py-3 rounded-t-xl [&>.border-t]:border-none gap-0 px-6" :style="{ backgroundColor: service.color }">
      <CardTitle class="text-xl flex items-center">
        <span
          v-if="service.icon"
          v-html="service.icon"
          class="
            inline-block
            mr-4
            [&_svg]:w-6
            [&_svg]:h-6 
            [&_path]:![stroke:var(--icon-color)]
          "
          :style="{ '--icon-color': darken(service.color, 25) }"
        ></span>
        {{ service.name }}
      </CardTitle>
    </CardHeader>
    <CardContent class="pb-4 px-0 flex flex-col gap-3">
        <p class="text-sm leading-5 text-blue-gray-600 pb-[.625rem] border-b border-b-blue-gray-200 px-4">{{ service.description }}</p>
        <p class="text-base leading-6 font-bold pb-[.625rem] border-b border-b-blue-gray-200 px-4">{{ $t('services.price') }}: {{ service.price }} €</p>
        <p class="text-base leading-6 text-gray-800 pb-[.625rem] border-b border-b-blue-gray-200 px-4">{{ $t('services.discount_partner') }}: {{ service.discount_partner }} %</p>
        <Link :href="`/services/${service.id}/edit`" class="px-4 md:px-0">
          <Button size="lg" variant="secondary" class="w-full">
            <PencilLine class="size-4" />{{ $t('services.edit') }}
          </Button>
        </Link>
    </CardContent>
  </Card>
</template>