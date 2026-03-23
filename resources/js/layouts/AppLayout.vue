<script setup lang="ts">
import { AppContent, AppShell, AppSidebar, AppSidebarHeader } from '@/components/app';
import { Notification } from '@/components/ui/notification';
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar />
    <AppContent variant="sidebar" class="overflow-x-hidden">
      <AppSidebarHeader />
      <slot />
      <Notification />
    </AppContent>
  </AppShell>
</template>
