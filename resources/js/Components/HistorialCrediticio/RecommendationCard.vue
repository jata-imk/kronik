<script setup>
import { ref, reactive, computed } from "vue";

// type Recommendation = {
//   id: string;
//   title: string;
//   description: string;
//   icon: React.ReactNode;
//   impact: 'high' | 'medium' | 'low';
// };

const recommendations = reactive([
    {
        id: "lower-utilization",
        title: "Reduzca su utilización del crédito",
        description:
            "Intente utilizar menos del 30% de su crédito disponible para mejorar su puntuación.",
        icon: "pi-chart-line",
        iconClass: "text-indigo-600",
        impact: "alto",
    },
    {
        id: "dispute-errors",
        title: "Disputar información inexacta",
        description:
            "Encontramos un error potencial en su informe que podría estar afectando su puntuación.",
        icon: "pi-shield",
        iconClass: "text-orange-600",
        impact: "alto",
    },
    {
        id: "diversify-credit",
        title: "Diversifique su combinación de créditos",
        description:
            "Tener diferentes tipos de cuentas de crédito puede mejorar su puntuación con el tiempo.",
        icon: "pi-dollar",
        iconClass: "text-emerald-600",
        impact: "medio",
    },
    {
        id: "authorized-user",
        title: "Conviértete en un usuario autorizado",
        description:
            "Agregarse a la tarjeta de crédito de alguien con buen historial puede mejorar su puntaje.",
        icon: "pi-verified",
        iconClass: "text-blue-600",
        impact: "bajo",
    },
]);

const getImpactBadgeClass = (impact) => {
    switch (impact) {
        case "alto":
            return "bg-orange-100 dark:bg-orange-400 text-orange-800 dark:text-orange-200";
        case "medio":
            return "bg-blue-100 dark:bg-blue-400 text-blue-800 dark:text-blue-200";
        case "bajo":
            return "bg-gray-100 dark:bg-gray-400 text-gray-800 dark:text-gray-200";
        default:
            return "bg-gray-100 dark:bg-gray-400 text-gray-800 dark:text-gray-200";
    }
};
</script>

<template>
  <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-6">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-6">Recomendaciones para mejorar tu puntuación</h2>

    <div class="space-y-4">
      <div v-for="recommendation in recommendations"
        class="border border-gray-100 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200">
        <div class="flex items-start">
          <div class="flex-shrink-0 mr-3 mt-1">
            <span :class="'pi ' + recommendation.icon + ' ' + recommendation.iconClass"></span>
          </div>
          <div class="flex-1">
            <div class="flex justify-between items-start">
              <h3 class="text-base font-medium text-gray-900 dark:text-gray-200">{{recommendation.title}}</h3>
              <span :class="getImpactBadgeClass(recommendation.impact)"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                impacto {{recommendation.impact}}
              </span>
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{recommendation.description}}</p>
            <button
              class="mt-2 flex items-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:text-indigo-400 transition-colors">
              Más información <span class="ml-1 pi pi-arrow-right"></span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>