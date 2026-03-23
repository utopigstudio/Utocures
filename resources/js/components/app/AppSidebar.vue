<script setup lang="ts">
import { NavMain } from '@/components/blocks';
import { AppLogo } from '@/components/app';
import { Sidebar, SidebarContent, SidebarHeader } from '@/components/ui/sidebar';
import { LayoutGrid, Users, UserRound, BadgeCheck, FileMinus, Clock3, History, Settings, Star, UsersRound } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n'
import { type NavItem, type NavItemsGroup } from '@/types';

const { t } = useI18n();

const mainNavItemsGestion: NavItem[] = [
  {
    title: t('sidebar.clients'),
    href: '/clients',
    icon: Users,
  },
  {
    title: t('sidebar.employees'),
    href: '/employees',
    icon: UserRound,
  },
  {
    title: t('sidebar.services'),
    href: '/services',
    icon: BadgeCheck,
  },
  {
    title: t('sidebar.characteristics'),
    href: '/characteristics',
    icon: Star,
  },
];

const mainNavItemsTime: NavItem[] = [
  {
    title: t('sidebar.time_records'),
    href: '/employee-time-records',
    icon: Clock3,
  },
];

const mainNavItemsDocuments: NavItem[] = [
  {
    title: t('sidebar.contracts'),
    href: '/contracts',
    icon: FileMinus,
  },
  {
    title: t('sidebar.budgets'),
    href: '/budgets',
    icon: FileMinus,
  },
  {
    title: t('sidebar.invoices'),
    href: '/invoices',
    icon: FileMinus,
  },
  {
    title: t('sidebar.contract_templates'),
    href: '/contract-templates',
    icon: Settings,
  },
  {
    title: t('sidebar.budget_templates'),
    href: '/budget-templates',
    icon: Settings,
  },
];

const mainNavItemsConfig: NavItem[] = [
  {
    title: t('sidebar.users'),
    href: '/users',
    icon: UsersRound,
  },
  {
    title: t('sidebar.global_configuration'),
    href: '/configuration',
    icon: Settings,
  },
];

const mainNavItemsReports: NavItem[] = [
  {
    title: t('sidebar.actions_log'),
    href: '/audits',
    icon: History,
  },
];

const mainNavItemsGrouped: NavItemsGroup[] = [
  {
    title: t('sidebar.dashboard'),
    href: '/dashboard',
    icon: LayoutGrid,
  },
  {
    title: t('sidebar.management'),
    items: mainNavItemsGestion,
  },
  {
    title: t('sidebar.documents'),
    items: mainNavItemsDocuments,
  },
  {
    title: t('sidebar.time'),
    items: mainNavItemsTime,
  },
  {
    title: t('sidebar.configuration'),
    items: mainNavItemsConfig,
  },
  {
    title: t('sidebar.reports'),
    items: mainNavItemsReports,
  },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarContent>
      <SidebarHeader>
        <AppLogo />
      </SidebarHeader>
      <NavMain :itemsGrouped="mainNavItemsGrouped" />
    </SidebarContent>
  </Sidebar>
  <slot />
</template>
