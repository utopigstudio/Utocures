<script setup lang="ts">
import { computed } from 'vue';
import { darken } from '@/lib/utils'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { AssignedHourTemplate } from '@/types'

interface Props {
  items?: AssignedHourTemplate[]
}

const props = defineProps<Props>()
const { getInitials } = useInitials();

const groupedEmployees = computed(() => {
  return props.items?.reduce((acc: any, item: any) => {
    const id = item.service_id

    if (!acc[id]) {
      acc[id] = {
        service: item.service,
        items: []
      }
    }

    acc[id].items.push(item)
    return acc
  }, {})
})

</script>

<template>
  <div class="bg-white">
    <div v-for="(group, serviceId) in groupedEmployees" :key="serviceId">
      <div class="flex justify-start items-start gap-4 px-4 py-3 -mx-4 md:mx-0" :style="{ backgroundColor: group.service.color }">
        <span
          v-if="group.service.icon"
          v-html="group.service.icon"
          class="
            inline-block
            [&_svg]:w-7
            [&_svg]:h-7
            [&_path]:![stroke:var(--icon-color)]
          "
          :style="{ '--icon-color': darken(group.service.color, 25) }"
        ></span>
        <h3 class="text-lg leading-7 text-blue-gray-900 m-0 text-center lg:text-left">
          <span class="font-semibold">{{ $t('clients.service_label') }}: </span>{{ group.service.name }}
        </h3>
      </div>

      <div
        v-for="item in group.items"
        :key="item.id"
        class="border-b border-gray-200 px-0 lg:px-4 py-4 hover:bg-gray-50 transition-colors last:border-b-0"
      >
        <div class="lg:hidden">
          <div class="flex items-center gap-3 mb-4">
            <Avatar class="h-8 w-8 overflow-hidden rounded-full">
              <AvatarImage v-if="item.employee.user.avatar" :src="item.employee.user.avatar"></AvatarImage>
              <AvatarFallback v-else class="rounded-full text-black bg-blue-gray-100 text-xs font-bold leading-4">
                {{ getInitials(item.employee.user.name) }}
              </AvatarFallback>
            </Avatar>
            <div class="flex-1 min-w-0">
              <p class="text-sm leading-5 font-medium text-blue-gray-900 truncate">{{ item.employee.user.name }}</p>
            </div>
          </div>
          
          <div class="space-y-3">
            <div>
              <p class="text-xs font-semibold">{{ $t('clients.days_label') }}</p>
              <p class="text-sm leading-5 font-normal text-blue-gray-800 mt-1">{{ item.days_names ? item.days_names.join(', ') : item.date }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold">{{ $t('clients.phone') }}</p>
              <p class="text-sm leading-5 font-normal text-blue-gray-800 mt-1 truncate">{{ item.employee.phone }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold">Email</p>
              <p class="text-sm leading-5 font-normal text-blue-gray-800 mt-1 truncate">{{ item.employee.email }}</p>
            </div>
          </div>
        </div>

        <div class="hidden lg:flex items-center justify-between gap-4">
          <div class="flex items-center gap-4 flex-1 min-w-0">
            <Avatar class="h-10 w-10 overflow-hidden rounded-full">
              <AvatarImage v-if="item.employee.user.avatar" :src="item.employee.user.avatar"></AvatarImage>
              <AvatarFallback v-else class="rounded-full text-black bg-blue-gray-100 text-xs font-bold leading-4">
                {{ getInitials(item.employee.user.name) }}
              </AvatarFallback>
            </Avatar>
            <div class="flex-1 min-w-0">
              <p class="text-sm leading-5 font-normal text-blue-gray-800 truncate">{{ item.employee.user.name }}</p>
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm leading-5 font-normal text-blue-gray-800 truncate">{{ item.days_names ? item.days_names.join(', ') : item.date }}</p>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm leading-5 font-normal text-blue-gray-800 truncate">{{ item.employee.phone }}</p>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm leading-5 font-normal text-blue-gray-800 truncate">{{ item.employee.user.email }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>