<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'
import type { Work } from '@/types'
import { darken } from '@/lib/utils'

interface Props {
  works?: Work[]
}

const props = defineProps<Props>()

const currentWorks = computed(() => {
  return props.works?.filter(work => {
    const now = new Date()
    const [startHour, startMinute] = work.time_start.split(':').map(Number)
    const [endHour, endMinute] = work.time_end.split(':').map(Number)
    const startTime = new Date()
    startTime.setHours(startHour, startMinute, 0)
    const endTime = new Date()
    endTime.setHours(endHour, endMinute, 0)
    return now >= startTime && now <= endTime
  }) || []
})
</script>

<template>
  <ul role="list" class="mt-3 flex flex-col gap-5">
    <li v-for="work in works" :key="work.id" class="flex rounded-md shadow-xs">
      <Link :href="route('employee.work.show', work.id)" class="flex w-full items-center">
        <div class="flex h-full w-16 shrink-0 items-center justify-center rounded-l-md font-medium text-white" :style="{ backgroundColor: work.service.color }">
          <span
            v-html="work.service.icon"
            class="[&_svg]:w-8 [&_svg]:h-8 [&_path]:![stroke:var(--icon-color)]"
          :style="{ '--icon-color': darken(work.service.color, 25) }"
          ></span>
        </div>
        <div class="flex flex-1 items-center justify-between truncate rounded-r-md border-t border-r border-b border-gray-200 bg-white">
          <div class="flex-1 truncate px-4 py-2 text-sm">
            <div class="font-medium text-gray-900 hover:text-gray-600">{{ work.client.name }} <span v-if="currentWorks.includes(work)">- Actual</span></div>
            <p class="text-gray-500">{{ work.time_start }} - {{ work.time_end }}</p>
            <p class="text-gray-500">{{ work.service.name }}</p>
          </div>
          <div class="flex items-center justify-center pr-2 flex-shrink-0">
            <ChevronRight class="size-6 text-blue-gray-600" />
          </div>
        </div>
      </Link>
    </li>
  </ul>
</template>