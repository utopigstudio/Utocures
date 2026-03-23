<script setup lang="ts">
import { AppLogoIcon } from '@/components/app';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Notification } from '@/components/ui/notification';
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

defineProps<{
  title?: string;
  description?: string;
  classCardTitle?: HTMLAttributes['class'];
  classCardContent?: HTMLAttributes['class'];
  classLogoHeader?: HTMLAttributes['class'];
}>();

</script>

<template>
  <div class="flex min-h-svh flex-col items-center justify-center bg-blue-900 px-4 py-10">
    <div class="flex w-full max-w-[34.375rem] flex-col">
      <div :class="cn('flex items-center gap-6 self-center mb-16 md:mb-0', classLogoHeader)">
        <div class="flex h-12 w-12 items-center justify-center">
          <AppLogoIcon size="size-12" />
        </div>
        <h1 class="text-[2.9375rem] text-white font-bold">Utocures</h1>
      </div>

      <div class="flex flex-col gap-6">
        <Card class="py-16">
          <CardHeader :border="false" class="px-6 md:px-10 py-0 text-center">
            <CardTitle :class="cn('text-4xl md:text-5xl leading-10 md:leading-none font-bold', classCardTitle)">{{ title }}</CardTitle>
            <div class="w-full border-t border-blue-gray-200 mt-6" aria-hidden="true" />
            <CardDescription v-if="description" class="text-base leading-6 text-gray-800 mt-6">
              {{ description }}
            </CardDescription>
          </CardHeader>
          <CardContent :class="cn('px-6 md:px-10 py-6 text-sm leading-5 text-gray-500', classCardContent)">
            <slot />
            <Notification />
          </CardContent>
          <div class="px-6 md:px-10 text-xs leading-5 text-gray-500 text-center">
            <p>Utocures se ha desarrollado gracias a la subvención L’Empenta, con el apoyo de la Federació de Cooperatives de Treball de Catalunya (FCTC)</p>
            <img class="mx-auto mt-2" src="/images/fctc.png" alt="FCTC Logo" width="200" height="60" />
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>
