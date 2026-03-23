<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useVModel } from '@vueuse/core'
import { Button } from '@/components/ui/button'
import InputError from '@/components/ui/input/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { Employee } from '@/types'

interface Props {
  name: string
  label?: string
  defaultValue?: string
  modelValue?: string
  eventId?: string
  clientId?: string
  serviceId?: string | undefined
  timeStart?: string
  timeEnd?: string
  daysOfWeek?: (string | number)[]
  recurrency: string | number
  dateStart?: string
  dateEnd?: string
  date?: string
  error?: string
}

const props = defineProps<Props>()
const employees = ref<Employee[]>([])
const loading = ref(false)
const { getInitials } = useInitials();

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue
})

onMounted(() => {
  if (props.timeStart && props.timeEnd && props.serviceId && (props.date || props.daysOfWeek?.length)) {
    fetchEmployees()
  }
})

function cleanEmployees() {
  employees.value = []
}

const toApiDate = (value: string | Date) => {
  if (value instanceof Date) {
    return value.toISOString().slice(0, 10)
  }

  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return value
  }

  if (/^\d{2}\/\d{2}\/\d{4}$/.test(value)) {
    const [d, m, y] = value.split('/')
    return `${y}-${m}-${d}`
  }

  throw new Error('Formato de fecha no válido')
}

async function fetchEmployees() {
  loading.value = true
  try {
    const queryParams = new URLSearchParams()
    if (props.eventId) queryParams.append('event_id', props.eventId.toString())
    if (props.timeStart) queryParams.append('time_start', props.timeStart)
    if (props.timeEnd) queryParams.append('time_end', props.timeEnd)
    if (props.date) queryParams.append('date', toApiDate(props.date))
    if (props.dateStart) queryParams.append('date_start', toApiDate(props.dateStart))
    if (props.dateEnd) queryParams.append('date_end', toApiDate(props.dateEnd))
    if (props.serviceId) queryParams.append('service_id', props.serviceId.toString())
    queryParams.append('recurrency', props.recurrency.toString())

    if (props.daysOfWeek && props.daysOfWeek.length > 0) {
      props.daysOfWeek.forEach(day => {
        queryParams.append('days_of_week[]', day.toString())
      })
    }

    const url = `/api/employees/options${queryParams.toString() ? '?' + queryParams.toString() : ''}`
    
    const res = await fetch(url, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (!res.ok) {
      throw new Error(`HTTP error! status: ${res.status}`)
    }

    const data = await res.json()
    employees.value = data.data || []

    if (employees.value.find(e => e.id === modelValue.value) === undefined) {
      modelValue.value = ''
    }
  } catch (err) {
    console.error('Error fetching employees:', err)
    employees.value = []
  } finally {
    loading.value = false
  }
}

watch(
  () => [
    props.timeStart,
    props.timeEnd,
    props.recurrency,
    props.daysOfWeek,
    props.serviceId,
    props.date,
    props.dateStart,
    props.dateEnd
  ],
  () => {
    const allFilled = [
      props.timeStart,
      props.timeEnd,
      props.serviceId
    ].every(v => {
      if (Array.isArray(v)) return v.length > 0
      return v !== null && v !== undefined && v !== ''
    })
    if (allFilled && (props.date || props.daysOfWeek?.length)) {
      fetchEmployees()
    } else {
      cleanEmployees()
    }
  },
  { deep: true }
)
</script>

<template>
  <label v-if="props.label" class="text-lg mr-4">{{ props.label }}</label>
  <div class="mt-4">
    <div class="bg-white overflow-y-auto max-h-55">
      <table class="mt-6 w-full text-left whitespace-nowrap text-sm leading-5 font-normal text-blue-gray-800">
        <tbody v-if="employees.length" class="divide-y divide-blue-gray-200">
          <tr v-for="item in employees" :key="item.id">
            <td class="py-4 pr-4 pl-0">
              <div class="flex items-center gap-x-4">
                <Avatar class="h-8 w-8 overflow-hidden rounded-full">
                  <AvatarImage v-if="item.user.avatar" :src="item.user.avatar"></AvatarImage>
                  <AvatarFallback v-else class="rounded-full text-black bg-blue-gray-100 text-xs font-bold leading-4">
                    {{ getInitials(item.user.name) }}
                  </AvatarFallback>
                </Avatar>
                <div class="truncate">{{ item.user.name }}</div>
              </div>
            </td>
            <td class="hidden py-4 pr-4 pl-0 sm:table-cell">
              <div class="flex gap-x-3">
                <div class="truncate">643 420 994</div>
              </div>
            </td>
            <td class="hidden py-4 pr-4 pl-0 sm:table-cell">
              <div class="flex gap-x-3">
                <div class="truncate">{{ item.user.email }}</div>
              </div>
            </td>
            <td class="py-4 pr-0 pl-0 text-right">
              <Button
                variant="secondary"
                size="xs"
                type="button"
                @click="modelValue = item.id"
                class="font-medium"
                :class="modelValue === item.id ? 'bg-blue-gray-600 text-white hover:bg-blue-gray-600/80' : ''"
              >
                {{ modelValue === item.id ? $t('clients.service_assigned') : $t('clients.assign_service') }}
              </Button>
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr>
            <td>
              <div class="flex items-center">
                <div>
                  <span v-if="loading">{{ $t('clients.loading_employees') }}...</span>
                  <span v-else>{{ $t('clients.no_employees_available') }}</span>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <InputError :message="props.error" />
</template>