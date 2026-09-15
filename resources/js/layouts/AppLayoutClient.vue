<script setup lang="ts">
import { AppContent, AppShell, AppSidebarHeader } from '@/components/app';
import { Notification } from '@/components/ui/notification';
</script>

<template>
  <AppShell>
    <AppContent variant="sidebar" class="overflow-x-hidden">
      <AppSidebarHeader :has-sidebar="false" />
      <slot />
      <Notification />
    </AppContent>
  </AppShell>
</template>
