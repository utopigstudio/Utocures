<script setup lang="ts">
import { AppLayout } from '@/layouts';
import { PlaceholderPattern } from '@/components/ui/basic';
</script>

<template>
  <AppLayout>
    <div class="flex h-full min-h-[100vh] flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70">
          <PlaceholderPattern />
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70">
          <PlaceholderPattern />
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70">
          <PlaceholderPattern />
        </div>
      </div>
      <div class="relative flex-1 rounded-xl border border-sidebar-border/70">
        <PlaceholderPattern />
      </div>
    </div>
  </AppLayout>
</template>
