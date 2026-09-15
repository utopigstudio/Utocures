<script setup lang="ts">
import { useId } from 'vue';

const prefix = useId();
function showTask(index: number) {
  document.getElementById(`${prefix}-task-${index}`)?.scrollIntoView({ block: 'start' });
}

defineProps<{
  steps: string[];
  tasks?: { title: string; steps: string[]; result: string }[];
}>();
</script>

<template>
  <div class="space-y-6">
    <p class="font-semibold text-gray-900">{{ $t('help.reach') }}</p>
    <ol class="list-decimal space-y-4 pl-6 marker:font-bold marker:text-blue-700">
      <li v-for="step in steps" :key="step" class="pl-2">{{ step }}</li>
    </ol>
    <nav v-if="tasks?.length" :aria-label="$t('help.choose_action')" class="rounded-xl bg-gray-50 p-4">
      <p class="mb-3 font-semibold text-gray-900">{{ $t('help.choose_action') }}</p>
      <ul class="space-y-2">
        <li v-for="(task, index) in tasks" :key="task.title">
          <button type="button" class="cursor-pointer text-left font-semibold text-blue-700 underline underline-offset-4" @click="showTask(index)">
            {{ task.title }}
          </button>
        </li>
      </ul>
    </nav>
    <section
      v-for="(task, index) in tasks"
      :id="`${prefix}-task-${index}`"
      :key="task.title"
      class="scroll-mt-28 rounded-xl border border-gray-200 p-5"
    >
      <h4 class="mb-4 text-lg font-bold text-gray-900">{{ task.title }}</h4>
      <ol class="list-decimal space-y-4 pl-6 marker:font-bold marker:text-blue-700">
        <li v-for="step in task.steps" :key="step" class="pl-2">{{ step }}</li>
      </ol>
      <p class="mt-4 rounded-lg bg-blue-50 p-3 text-gray-800">
        <strong>{{ $t('help.result') }}</strong> {{ task.result }}
      </p>
    </section>
  </div>
</template>
