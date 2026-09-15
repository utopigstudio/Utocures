<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { RefreshCw, WifiOff } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const isBlocked = ref(false);
const connectionRestored = ref(false);
const dialog = ref<HTMLElement | null>(null);

let previousBodyOverflow = '';

function blockApplication(): void {
    connectionRestored.value = false;

    if (isBlocked.value) {
        return;
    }

    isBlocked.value = true;
    document.getElementById('app')?.setAttribute('inert', '');
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    void nextTick(() => dialog.value?.focus());
}

function handleOnline(): void {
    if (isBlocked.value) {
        connectionRestored.value = true;
    }
}

function reloadApplication(): void {
    window.location.reload();
}

function restoreDocument(): void {
    document.getElementById('app')?.removeAttribute('inert');
    document.body.style.overflow = previousBodyOverflow;
}

onMounted(() => {
    window.addEventListener('offline', blockApplication);
    window.addEventListener('online', handleOnline);

    if (!navigator.onLine) {
        blockApplication();
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('offline', blockApplication);
    window.removeEventListener('online', handleOnline);
    restoreDocument();
});
</script>

<template>
    <Teleport to="body">
        <div v-if="isBlocked" class="fixed inset-0 z-[100] flex items-center justify-center bg-blue-900/95 p-4 backdrop-blur-sm">
            <section
                ref="dialog"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="offline-title"
                aria-describedby="offline-description"
                tabindex="-1"
                class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl outline-none sm:p-8"
            >
                <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-blue-50 text-blue-900">
                    <WifiOff class="size-7" aria-hidden="true" />
                </div>

                <h1 id="offline-title" class="mt-4 text-xl font-bold text-gray-800">
                    {{ $t(connectionRestored ? 'pwa.offline.restored_title' : 'pwa.offline.title') }}
                </h1>
                <p id="offline-description" class="mt-2 text-sm leading-6 text-gray-500">
                    <template v-if="connectionRestored">
                        {{ $t('pwa.offline.restored_description') }}
                    </template>
                    <template v-else>
                        {{ $t('pwa.offline.description') }}
                    </template>
                </p>

                <Button v-if="connectionRestored" type="button" class="mt-6 w-full" @click="reloadApplication">
                    <RefreshCw aria-hidden="true" />
                    {{ $t('pwa.offline.reload') }}
                </Button>
                <p v-else class="mt-6 text-xs font-medium text-blue-gray-500">{{ $t('pwa.offline.waiting') }}</p>
            </section>
        </div>
    </Teleport>
</template>
