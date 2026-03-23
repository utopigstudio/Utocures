<script setup lang="ts">
import { VisuallyHidden } from 'reka-ui';
import { Form } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

interface Props {
  show: boolean
  action: string
  showCloseButton?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showCloseButton: false,
});

const emit = defineEmits(['close','deleted']);
</script>

<template>
  <Dialog :open="show">
    <DialogContent :showCloseButton="showCloseButton">
      <Form
        method="delete"
        :action="props.action"
        reset-on-success
        :options="{
          preserveScroll: true
        }"
        class="space-y-8"
        v-slot="{ processing, reset, clearErrors }"
        @success="() => { emit('deleted') }"
      >
        <DialogHeader class="flex flex-col justify-center items-center space-y-3">
          <VisuallyHidden asChild>
            <DialogTitle />
          </VisuallyHidden>
          <DialogDescription class="flex flex-col justify-center items-center space-y-4 text-center">
            <svg width="74" height="74" viewBox="0 0 74 74" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="74" height="74" rx="37" fill="#FEF2F2"/>
              <path d="M40.6275 33.3728V46.0689M33.3726 33.3728V46.0689M26.1177 26.1179V47.5199C26.1177 49.5513 26.1177 50.567 26.5131 51.3432C26.8608 52.0257 27.4158 52.5807 28.0983 52.9284C28.8727 53.3238 29.8884 53.3238 31.9162 53.3238H42.0839C44.1117 53.3238 45.1255 53.3238 45.9 52.9284C46.5838 52.5802 47.1388 52.0252 47.487 51.3432C47.8824 50.567 47.8824 49.5531 47.8824 47.5253V26.1179M26.1177 26.1179H29.7451M26.1177 26.1179H22.4902M47.8824 26.1179H44.2549M47.8824 26.1179H51.5098M29.7451 26.1179H44.2549M29.7451 26.1179C29.7451 24.4275 29.7451 23.5823 30.0208 22.9167C30.2029 22.4765 30.4699 22.0764 30.8067 21.7394C31.1434 21.4023 31.5432 21.1349 31.9833 20.9524C32.6507 20.6768 33.4959 20.6768 35.1863 20.6768H38.8138C40.5042 20.6768 41.3494 20.6768 42.015 20.9524C42.4554 21.1347 42.8556 21.4021 43.1926 21.7391C43.5296 22.0761 43.797 22.4763 43.9793 22.9167C44.2549 23.5823 44.2549 24.4275 44.2549 26.1179" stroke="#EF4444" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p class="text-blue-gray-900 text-lg leading-7 font-semibold">{{ $t('layout.delete_title') }}</p>
            <p class="text-blue-gray-500 text-base leading-6 font-normal">{{ $t('layout.delete_description') }}</p>
          </DialogDescription>
        </DialogHeader>

        <DialogFooter class="gap-6">
          <DialogClose as-child>
            <Button variant="link" @click="
              () => {
                emit('close');
                clearErrors();
                reset();
              }
            ">
              {{ $t('layout.cancel') }}
            </Button>
          </DialogClose>

          <Button type="submit" :disabled="processing"> {{ $t('layout.delete') }} </Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>
</template>
