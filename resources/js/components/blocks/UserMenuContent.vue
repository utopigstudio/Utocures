<script setup lang="ts">
import { UserInfo } from '@/components/blocks';
import { DropdownMenuItem, DropdownMenuLabel } from '@/components/ui/dropdown-menu';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';
import type { User, Employee } from '@/types';
import { usePage } from '@inertiajs/vue3';

interface Props {
  user: User;
}

const page = usePage();
const employee = page.props.auth.employee as Employee;

const handleLogout = () => {
  router.flushAll();
};

defineProps<Props>();
</script>

<template>
  <DropdownMenuLabel v-if="!employee" class="p-0 font-normal">
    <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
      <UserInfo :user="user" :show-email="true" />
    </div>
  </DropdownMenuLabel>
  <DropdownMenuItem v-if="!user.employee" :as-child="true" class="cursor-pointer">
    <Link class="block w-full" :href="route('users.edit', user.id)" as="button">
      <Settings class="mr-2 h-4 w-4" />
      {{ $t('layout.update_profile') }}
    </Link>
  </DropdownMenuItem>
  <DropdownMenuItem :as-child="true" class="cursor-pointer">
    <Link class="block w-full" method="post" :href="route('logout')" @click.prevent="handleLogout" as="button">
      <LogOut class="mr-2 h-4 w-4" />
      {{ $t('layout.logout') }}
    </Link>
  </DropdownMenuItem>
</template>
