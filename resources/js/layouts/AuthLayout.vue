<script setup lang="ts">
import { AppLogoHorizontal } from '@/components/app';
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
  <div class="flex min-h-svh flex-col items-center justify-center bg-sidebar px-4 py-10">
    <div class="flex w-full max-w-[34.375rem] flex-col">
      <h1 :class="cn('mb-8 w-80 max-w-full self-center', classLogoHeader)">
        <AppLogoHorizontal class="w-full" />
      </h1>

      <div class="flex flex-col gap-6">
        <Card class="py-16">
          <CardHeader :border="false" class="px-6 md:px-10 py-0 text-center">
            <CardTitle :class="cn('text-4xl md:text-5xl leading-10 md:leading-none font-bold', classCardTitle)">{{ title }}</CardTitle>
            <div class="w-full border-t border-blue-gray-200 mt-6" aria-hidden="true" />
            <CardDescription v-if="description" class="text-base leading-6 text-gray-800 mt-4">
              {{ description }}
            </CardDescription>
          </CardHeader>
          <CardContent :class="cn('px-6 md:px-10 py-6 text-sm leading-5 text-gray-500', classCardContent)">
            <slot />
            <Notification />
          </CardContent>
          <div class="px-6 md:px-10 text-xs leading-5 text-gray-500 text-center">
            <p>{{ $t('layout.fctc_support') }}</p>
            <img class="mx-auto mt-2" src="/images/fctc.png" :alt="$t('layout.fctc_logo_alt')" width="200" height="60" />
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>
