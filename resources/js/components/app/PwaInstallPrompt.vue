<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Download, Share, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import { useI18n } from 'vue-i18n';

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{
        outcome: 'accepted' | 'dismissed';
        platform: string;
    }>;
}

type NavigatorWithStandalone = Navigator & {
    standalone?: boolean;
};

const DISMISS_KEY = 'utocures-pwa-install-dismissed-at';
const DISMISS_DURATION = 7 * 24 * 60 * 60 * 1000;

const deferredPrompt = shallowRef<BeforeInstallPromptEvent | null>(null);
const isVisible = ref(false);
const isIos = ref(false);
const { t } = useI18n();

const description = computed(() => t(isIos.value ? 'pwa.install.ios_description' : 'pwa.install.description'));

function isRunningAsApp(): boolean {
    return window.matchMedia('(display-mode: standalone)').matches || (navigator as NavigatorWithStandalone).standalone === true;
}

function isIosDevice(): boolean {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

function wasRecentlyDismissed(): boolean {
    try {
        const dismissedAt = Number.parseInt(localStorage.getItem(DISMISS_KEY) ?? '', 10);

        return Number.isFinite(dismissedAt) && Date.now() - dismissedAt < DISMISS_DURATION;
    } catch {
        return false;
    }
}

function rememberDismissal(): void {
    try {
        localStorage.setItem(DISMISS_KEY, Date.now().toString());
    } catch {
        console.error('No se pudo guardar la clave de localStorage para el aviso de instalación de PWA.');
    }
}

function dismiss(): void {
    rememberDismissal();
    isVisible.value = false;
}

function handleInstallAvailable(event: Event): void {
    event.preventDefault();
    deferredPrompt.value = event as BeforeInstallPromptEvent;

    if (!wasRecentlyDismissed()) {
        isVisible.value = true;
    }
}

function handleInstalled(): void {
    deferredPrompt.value = null;
    isVisible.value = false;

    try {
        localStorage.removeItem(DISMISS_KEY);
    } catch {
        console.error('No se pudo eliminar la clave de localStorage para el aviso de instalación de PWA.');
    }
}

async function install(): Promise<void> {
    if (!deferredPrompt.value) {
        return;
    }

    const prompt = deferredPrompt.value;
    await prompt.prompt();
    const { outcome } = await prompt.userChoice;

    deferredPrompt.value = null;
    isVisible.value = false;

    if (outcome === 'dismissed') {
        rememberDismissal();
    }
}

onMounted(() => {
    if (isRunningAsApp()) {
        return;
    }

    isIos.value = isIosDevice();
    window.addEventListener('beforeinstallprompt', handleInstallAvailable);
    window.addEventListener('appinstalled', handleInstalled);

    if (isIos.value && !wasRecentlyDismissed()) {
        isVisible.value = true;
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeinstallprompt', handleInstallAvailable);
    window.removeEventListener('appinstalled', handleInstalled);
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-500 ease-out"
        enter-from-class="-translate-y-full scale-95 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-300 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-full scale-95 opacity-0"
    >
        <aside
            v-if="isVisible"
            :aria-label="$t('pwa.install.aria_label')"
            aria-live="polite"
            class="fixed top-3 right-3 left-3 z-50 mx-auto max-w-2xl overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-r from-blue-900 to-blue-600 p-4 text-white shadow-2xl ring-1 ring-blue-900/20 sm:top-6 sm:p-5"
        >
            <button
                type="button"
                class="absolute top-3 right-3 cursor-pointer rounded-lg p-1.5 text-white/70 transition-colors hover:bg-white/15 hover:text-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                :aria-label="$t('pwa.install.close_aria_label')"
                @click="dismiss"
            >
                <X class="size-5" aria-hidden="true" />
            </button>

            <div class="flex flex-col gap-4 pr-7 sm:flex-row sm:items-center sm:pr-8">
                <div class="flex min-w-0 flex-1 items-center gap-4">
                    <div class="relative flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-lg">
                        <img src="/pwa-icon-192.png" alt="" class="size-11 rounded-xl" />
                        <span class="absolute -right-1 -bottom-1 flex size-5 items-center justify-center rounded-full bg-blue-600 ring-2 ring-white">
                            <Share v-if="isIos" class="size-3 text-white" aria-hidden="true" />
                            <Download v-else class="size-3 text-white" aria-hidden="true" />
                        </span>
                    </div>
                    <div class="min-w-0">
                        <div class="mb-1 flex items-center gap-2">
                            <span class="size-2 animate-pulse rounded-full bg-green-400" aria-hidden="true"></span>
                            <span class="text-xs font-semibold tracking-wide text-blue-100 uppercase">{{ $t('pwa.install.available') }}</span>
                        </div>
                        <p class="text-lg leading-6 font-bold text-white">{{ $t('pwa.install.title') }}</p>
                        <p class="mt-1 text-sm leading-5 text-blue-100">{{ description }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-2 sm:ml-2">
                    <Button type="button" variant="ghost" size="sm" class="text-white hover:bg-white/15 hover:text-white" @click="dismiss">
                        {{ $t(isIos ? 'pwa.install.ios_dismiss' : 'pwa.install.dismiss') }}
                    </Button>
                    <Button v-if="!isIos" type="button" size="sm" class="bg-white text-blue-900 shadow-md hover:bg-blue-50" @click="install">
                        <Download aria-hidden="true" />
                        {{ $t('pwa.install.action') }}
                    </Button>
                </div>
            </div>
        </aside>
    </Transition>
</template>
