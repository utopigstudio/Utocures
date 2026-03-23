<script setup lang="ts">
import { X, CheckCircleIcon, XCircleIcon, InfoIcon } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { Notification as NotificationType } from '@/types'

const page = usePage()
const notification = computed(() => page.props.flash?.notification as NotificationType | null)
const showNotification = ref(false)

watch(notification, (value) => {
  if (value) {
    showNotification.value = true
  }
},
{ immediate: true })

const icon = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: InfoIcon,
}

const color = {
    success: 'text-green-400',
    error: 'text-red-400',
    warning: 'text-yellow-400',
}
</script>

<template>
  <div v-if="showNotification && notification" aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:p-6">
    <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
      <transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 sm:translate-x-0" leave-active-class="transition ease-in duration-100" leave-from-class="" leave-to-class="opacity-0">
        <div class="pointer-events-auto w-full max-w-sm rounded-lg bg-white shadow-lg outline-1 outline-black/5">
          <div class="p-4">
            <div class="flex items-start">
              <div class="shrink-0">
                <component :is="icon[notification.type]" :class="`${color[notification.type]} size-6`" aria-hidden="true" />
              </div>
              <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="mt-1 text-sm text-gray-500">{{ notification.message }}</p>
              </div>
              <div class="ml-4 flex shrink-0">
                <button type="button" @click="showNotification = false" class="inline-flex rounded-md text-gray-400 hover:text-gray-500 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600">
                  <span class="sr-only">Close</span>
                  <X class="size-5" aria-hidden="true" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>
