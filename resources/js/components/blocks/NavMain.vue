<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItemsGroup } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { useSidebar } from '../ui/sidebar/utils'

defineProps<{
  itemsGrouped: NavItemsGroup[];
}>();

const page = usePage();
const { state } = useSidebar()
</script>

<template>
  <SidebarGroup v-for="group in itemsGrouped" :key="group.title" class="py-0" :class="state === 'expanded' ? 'px-6' : 'px-4'">
    <SidebarGroupLabel v-if="!group.href">{{ group.title }}</SidebarGroupLabel>
    <SidebarMenu>
      <SidebarMenuItem v-if="group.href" :key="group.title">
        <SidebarMenuButton as-child :is-active="(page.url.startsWith(group.href))" :tooltip="group.title">
          <Link :href="group.href">
            <component :is="group.icon" />
            <span>{{ group.title }}</span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>
      <SidebarMenuItem v-for="item in group.items" :key="item.title">
        <SidebarMenuButton as-child :is-active="(page.url.startsWith(item.href))" :tooltip="item.title">
          <Link :href="item.href">
            <component :is="item.icon" />
            <span>{{ item.title }}</span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>
    </SidebarMenu>
  </SidebarGroup>
</template>
