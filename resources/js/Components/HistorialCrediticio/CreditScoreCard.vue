<script setup>
import { ref, computed, onMounted, watch } from "vue";

const props = defineProps({
    score: Number,
    previousScore: Number,
    maxScore: Number,
});

const animatedScore = ref(0);
const percentage = computed(() => (props.score / props.maxScore) * 100);
const change = computed(() => props.score - props.previousScore);
const isPositive = computed(() => change.value >= 0);

const getScoreCategory = computed(() => {
    const score = props.score;
    if (score >= 800) return "Excellent";
    if (score >= 740) return "Very Good";
    if (score >= 670) return "Good";
    if (score >= 580) return "Fair";
    return "Poor";
});

const getCategoryColor = computed(() => {
    const score = props.score;
    if (score >= 800) return "text-emerald-600";
    if (score >= 740) return "text-green-600";
    if (score >= 670) return "text-yellow-600";
    if (score >= 580) return "text-orange-600";
    return "text-red-600";
});

const getRotation = computed(() => {
    return ((props.score - 300) / (props.maxScore - 300)) * 180;
});

const animateScore = () => {
    const duration = 2000;
    const startTime = Date.now();

    const step = () => {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        animatedScore.value = Math.floor(progress * props.score);

        if (progress < 1) {
            requestAnimationFrame(step);
        }
    };

    requestAnimationFrame(step);
};

onMounted(() => {
    animateScore();
});

watch(
    () => props.score,
    () => {
        animateScore();
    },
);
</script>

<template>
  <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-6">
    <div class="flex justify-between items-start mb-6">
      <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Score crediticio</h2>
      <Button icon="pi pi-question-circle" class="p-button-text p-0 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400" />
    </div>

    <div class="flex flex-col items-center mb-8">
      <div class="relative h-44 w-44 mb-4">
        <!-- Gauge background -->
        <div class="absolute inset-0 rounded-full border-[16px] border-gray-100 dark:border-gray-600"></div>

        <!-- Gauge fill -->
        <div
          class="absolute inset-0 rounded-full border-[16px] border-transparent"
          :style="{
            borderTopColor:  'rgb(79 70 229)',
            borderRightColor: 'rgb(79 70 229)',
            transform: `rotate(${getRotation}deg)`,
            transition: 'transform 2s ease-out'
          }"
        ></div>

        <!-- Score display -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
          <div class="text-4xl font-bold text-gray-800 dark:text-gray-200">{{ animatedScore }}</div>
          <div :class="['text-sm font-medium', getCategoryColor]">
            {{ getScoreCategory }}
          </div>
        </div>
      </div>

      <div class="flex items-center">
        <span :class="['inline-flex items-center', isPositive ? 'text-green-600' : 'text-red-600', 'text-sm font-medium']">
          <span class="mr-1 pi pi-arrow-up" :class="{ 'rotate-180': !isPositive }"></span>
          {{ Math.abs(change) }} pts
        </span>
        <span class="text-gray-500 dark:text-gray-400 text-sm ml-1">desde la ultima evaluación</span>
      </div>
    </div>

    <div class="grid grid-cols-5 gap-1 mb-2">
      <div class="h-2 rounded-l-full bg-red-500"></div>
      <div class="h-2 bg-orange-500"></div>
      <div class="h-2 bg-yellow-500"></div>
      <div class="h-2 bg-green-500"></div>
      <div class="h-2 rounded-r-full bg-emerald-500"></div>
    </div>

    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
      <span>300</span>
      <span>Pobre</span>
      <span>Razonable</span>
      <span>Bueno</span>
      <span>Muy bueno</span>
      <span>850</span>
    </div>
  </div>
</template>

<style scoped>
.rotate-180 {
  transform: rotate(180deg);
}
</style>
