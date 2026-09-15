<script setup lang="ts">
import { UserInfo } from '@/components/blocks';
import {
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Languages, LogOut, Settings } from 'lucide-vue-next';
import type { AppPageProps, User } from '@/types';
import { computed } from 'vue';

interface Props {
  user: User;
}

const props = defineProps<Props>();
const page = usePage<AppPageProps>();
const isEmployee = computed(() => page.props.auth.employee);
const isAdmin = computed(() => page.props.auth.role === 'admin');

const selectedLocale = computed(() => page.props.locale || props.user.locale || 'es');
const availableLocales = computed(() => page.props.available_locales || [
  { value: 'es', label: 'Español' },
  { value: 'ca', label: 'Català' },
]);

const handleLogout = () => {
  router.flushAll();
};

const updateLocale = (locale: any) => {
  if (locale === selectedLocale.value) {
    return;
  }

  router.patch(route('user.locale.update'), { locale }, {
    preserveScroll: true,
    onSuccess: () => window.location.reload(),
  });
};
</script>

<template>
  <DropdownMenuLabel v-if="!isEmployee" class="p-0 font-normal">
    <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
      <UserInfo :user="user" :show-email="true" />
    </div>
  </DropdownMenuLabel>
  <DropdownMenuItem v-if="isAdmin" :as-child="true" class="cursor-pointer">
    <Link class="block w-full" :href="route('users.edit', user.id)" as="button">
      <Settings class="mr-2 h-4 w-4" />
      {{ $t('layout.update_profile') }}
    </Link>
  </DropdownMenuItem>
  <DropdownMenuSeparator />
  <DropdownMenuLabel class="flex items-center gap-2 px-2 py-1.5 text-xs font-medium">
    <Languages class="h-4 w-4" />
    {{ $t('layout.language') }}
  </DropdownMenuLabel>
  <DropdownMenuRadioGroup :model-value="selectedLocale" @update:model-value="updateLocale">
    <DropdownMenuRadioItem
      v-for="locale in availableLocales"
      :key="locale.value"
      :value="locale.value"
      class="cursor-pointer"
    >
      {{ locale.label }}
    </DropdownMenuRadioItem>
  </DropdownMenuRadioGroup>
  <DropdownMenuSeparator />
  <DropdownMenuItem :as-child="true" class="cursor-pointer">
    <Link class="block w-full" method="post" :href="route('logout')" @click.prevent="handleLogout" as="button">
      <LogOut class="mr-2 h-4 w-4" />
      {{ $t('layout.logout') }}
    </Link>
  </DropdownMenuItem>
</template>
