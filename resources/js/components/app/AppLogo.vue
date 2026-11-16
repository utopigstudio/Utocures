<script setup lang="ts">
import AppLogoIcon from './AppLogoIcon.vue';
import AppLogoHorizontal from './AppLogoHorizontal.vue';
import { useSidebar } from '../ui/sidebar/utils';

const { state } = useSidebar();
</script>

<template>
  <AppLogoHorizontal v-if="state === 'expanded'" class="w-[90%]" />
  <AppLogoIcon v-else />
</template>
