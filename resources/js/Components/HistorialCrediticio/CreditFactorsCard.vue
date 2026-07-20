<template>
    <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-6 lg:col-span-2">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-6">Factores de Crédito</h2>

        <ul class="space-y-4">
            <li v-for="factor in factors" class="flex items-start p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex-shrink-0 mr-3">
                    <span :class="factor.icon + ' ' + factor.iconClass" />
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-200">{{factor.name}}</p>
                        <div class="flex space-x-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                :class="getImpactBadge(factor.impact)">
                                impacto {{factor.impact}}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                :class="getStatusColor(factor.status)">
                                {{factor.status}}
                            </span>
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{getFactorDescription(factor.id)}}
                    </p>
                </div>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { ref } from "vue";

// interface Factor {
//   id: string;
//   name: string;
//   impact: 'high' | 'medium' | 'low';
//   status: 'excellent' | 'good' | 'fair' | 'poor';
//   icon: string;
// }

const factors = ref([
    {
        id: "payment-history",
        name: "Historial de Pagos",
        impact: "alto",
        status: "excelente",
        icon: "pi pi-check-circle",
        iconClass: "text-emerald-500",
    },
    {
        id: "credit-usage",
        name: "Uso de Crédito",
        impact: "alto",
        status: "bueno",
        icon: "pi pi-credit-card",
        iconClass: "text-green-500",
    },
    {
        id: "credit-age",
        name: "Antigüedad de Crédito",
        impact: "medio",
        status: "razonable",
        icon: "pi pi-clock",
        iconClass: "text-yellow-500",
    },
    {
        id: "recent-inquiries",
        name: "Consultas recientes",
        impact: "bajo",
        status: "pobre",
        icon: "pi pi-exclamation-triangle",
        iconClass: "text-red-500",
    },
]);

const getStatusColor = (status) => {
    switch (status) {
        case "excelente":
            return "bg-emerald-100 text-emerald-800";
        case "bueno":
            return "bg-green-100 text-green-800";
        case "razonable":
            return "bg-yellow-100 text-yellow-800";
        case "pobre":
            return "bg-red-100 text-red-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
};

const getImpactBadge = (impact) => {
    switch (impact) {
        case "high":
            return "bg-indigo-100 text-indigo-800";
        case "medium":
            return "bg-blue-100 text-blue-800";
        case "low":
            return "bg-gray-100 text-gray-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
};

function getFactorDescription(factorId) {
    switch (factorId) {
        case "payment-history":
            return "Su historial de pagos puntuales es excelente. Siga pagando puntualmente para mantener este estatus.";
        case "credit-usage":
            return "Estás usando el 20% de tu crédito disponible. Mantén la utilización por debajo del 30% para obtener mejores resultados.";
        case "credit-age":
            return "La antigüedad promedio de su cuenta es de 3 años. Un historial crediticio más largo suele mejorar su puntuación.";
        case "recent-inquiries":
            return "Tienes 5 consultas importantes en los últimos 12 meses. Limita las nuevas solicitudes de crédito.";
        default:
            return "No hay descripción disponible";
    }
}
</script>