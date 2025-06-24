<template>
  <Card class="border border-gray-200 !shadow-none dark:bg-gray-800 dark:border-gray-500">
    <template #content>
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ props.title }}</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-200">{{ props.value }}</p>
          <div v-if="props.change" class="flex items-center mt-2">
            <component :is="props.change.isPositive ? TrendingUp : TrendingDown" class="mr-1"
              :class="props.change.isPositive ? 'text-green-500' : 'text-red-500'" :size="16" />
            <span class="text-sm font-medium" :class="props.change.isPositive ? 'text-green-600' : 'text-red-600'">
              {{ props.change.isPositive ? '+' : '' }}{{ props.change.value }}%
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">{{ props.change.period }}</span>
          </div>
        </div>
        <div :class="['p-3 rounded-full border', colorClasses]">
          <slot name="icon" />
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed } from "vue";
import { TrendingUp, TrendingDown } from "lucide-vue-next";

const props = defineProps({
    title: String,
    value: String,
    change: Object,
});

const colorClasses = computed(() => {
    switch (props.color) {
        case "green":
            return "bg-green-50 text-green-600 border-green-100";
        case "purple":
            return "bg-purple-50 text-purple-600 border-purple-100";
        case "orange":
            return "bg-orange-50 text-orange-600 border-orange-100";
        case "red":
            return "bg-red-50 text-red-600 border-red-100";
        case "indigo":
            return "bg-indigo-50 text-indigo-600 border-indigo-100";
        default:
            return "bg-blue-50 text-blue-600 border-blue-100";
    }
});
</script>