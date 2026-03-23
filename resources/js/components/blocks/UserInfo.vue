<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useInitials } from '@/composables/useInitials';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import type { User, Employee } from '@/types';

interface Props {
  user: User;
  showEmail?: boolean;
  showOnlyAvatar?: boolean;
}

const page = usePage();
const employee = page.props.auth.employee as Employee; 
const props = withDefaults(defineProps<Props>(), {
  showEmail: false,
});

const { getInitials } = useInitials();
const showAvatar = computed(() => props.user.avatar && props.user.avatar !== '');
</script>

<template>
  <div v-if="!props.showOnlyAvatar && !employee" class="grid flex-1 text-left text-sm leading-tight">
    <span class="truncate font-medium">{{ props.user.name }}</span>
    <span v-if="props.showEmail" class="truncate text-xs text-muted-foreground">{{ props.user.email }}</span>
  </div>
  <Avatar class="h-8 w-8 overflow-hidden rounded-full">
    <AvatarImage v-if="showAvatar" :src="props.user.avatar ?? ''" :alt="props.user.name">
      {{ props.user.name }}
    </AvatarImage>
    <AvatarFallback v-else class="rounded-full text-black bg-blue-gray-100 text-xs font-bold leading-4">
      {{ getInitials(props.user.name) }}
    </AvatarFallback>
  </Avatar>
</template>
