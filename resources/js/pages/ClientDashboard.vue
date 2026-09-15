<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { AppLayoutClient } from '@/layouts';
import type { AppPageProps, User } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Megaphone, MessageCircle } from 'lucide-vue-next';

const page = usePage<AppPageProps>();
const user = page.props.auth.user as User;
</script>

<template>
  <AppLayoutClient>
    <main class="min-h-[calc(100vh-5rem)] bg-gray-50 px-4 py-8 sm:px-8 sm:py-12">
      <div class="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <section>
          <p class="text-sm font-semibold text-blue-700">{{ $t('dashboard.client_area') }}</p>
          <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">
            {{ $t('dashboard.client_welcome', { name: user.name }) }}
          </h1>
          <p class="mt-3 max-w-2xl text-base text-gray-600">
            {{ $t('dashboard.client_welcome_description') }}
          </p>
        </section>

        <div class="grid gap-6 lg:grid-cols-5">

          <Card class="bg-white lg:col-span-3">
            <CardContent class="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center">
              <div class="flex size-14 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                <MessageCircle class="size-7" />
              </div>
              <h2 class="mt-5 text-xl font-bold text-gray-900">
                {{ $t('dashboard.client_messages_title') }}
              </h2>
              <p class="mt-3 max-w-md text-sm leading-6 text-gray-600">
                {{ $t('dashboard.client_messages_description') }}
              </p>
              <Link
                :href="route('client.messages.index')"
                class="mt-6 inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
              >
                {{ $t('dashboard.client_messages_action') }}
                <ArrowRight class="size-4" />
              </Link>
            </CardContent>
          </Card>
        </div>

        <div class="flex justify-center">
          <Link
            :href="route('client.announcements')"
            class="flex h-12 w-full flex-row items-center justify-center gap-5 rounded-lg border-2 border-blue-800 py-2 text-blue-800 hover:border-blue-700"
          >
            <Megaphone class="size-5" />
            <span class="text-sm leading-5 font-semibold">{{ $t('announcements.board_button') }}</span>
          </Link>
        </div>
      </div>
    </main>
  </AppLayoutClient>
</template>
