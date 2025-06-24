<script setup>
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import MetricCard from "@components/HistorialCrediticio/MetricCard.vue";
import QueryChart from "@components/HistorialCrediticio/QueryChart.vue";
import ClientActivityTable from "@components/HistorialCrediticio/ClientActivityTable.vue";

function calcularCambioPorcentual(actual, anterior) {
    if (anterior === 0) {
        return 100;
    }
    return ((actual - anterior) / anterior) * 100;
}

const page = usePage();

const clientesCount = page.props.clientesCount;
const clientesUntilLastMonth = page.props.clientesUntilLastMonth || 0;

const clientesChange = computed(() =>
    calcularCambioPorcentual(clientesCount, clientesUntilLastMonth),
);

const clientesWithSicQueryCount = page.props.clientesWithSicQueryCount;
const clientesWithSicQueryUntilLastMonthCount =
    page.props.clientesWithSicQueryUntilLastMonthCount || 0;

const clientesWithSicQueryChange = computed(() =>
    calcularCambioPorcentual(
        clientesWithSicQueryCount,
        clientesWithSicQueryUntilLastMonthCount,
    ),
);

const sics = page.props.sics;
const selectedSic = ref(sics.find((sic) => Boolean(sic.activo) === true));

const sicsQueriesCount = page.props.sicsQueriesCount;
const sicsQueriesUntilLastMonthCount =
    page.props.sicsQueriesUntilLastMonthCount || 0;
const sicsQueriesChange = computed(() =>
    calcularCambioPorcentual(sicsQueriesCount, sicsQueriesUntilLastMonthCount),
);

const sicQueriesPaginated = page.props.sicQueriesPaginated;

const menubarItems = ref(page.props.menubarItems);

import { Users, Database, TrendingUp, Zap, Shield } from "lucide-vue-next";

const primaryMetrics = ref([
    {
        title: "Total de Clientes",
        value: clientesCount,
        change: {
            value: clientesChange.value.toFixed(2),
            period: "ultimo mes",
            isPositive: clientesChange.value > 0,
        },
        icon: Users,
    },
    {
        title: "Total Clientes Consultados",
        value: clientesWithSicQueryCount,
        change: {
            value: clientesWithSicQueryChange.value.toFixed(2),
            period: "ultimo mes",
            isPositive: clientesWithSicQueryChange.value > 0,
        },
        icon: Users,
    },
    {
        title: "Total de Consultas SIC",
        value: sicsQueriesCount,
        change: {
            value: sicsQueriesChange.value.toFixed(2),
            period: "ultimo mes",
            isPositive: sicsQueriesChange.value > 0,
        },
        icon: Database,
    },
]);

const additionalSections = ref([
    {
        title: "Distribución de Clientes",
        icon: TrendingUp,
        type: "distribution",
        items: [
            {
                label: "Premium",
                count: "4,521",
                percent: "35.2%",
                dotClass: "bg-purple-500",
            },
            {
                label: "Basic",
                count: "6,892",
                percent: "53.6%",
                dotClass: "bg-blue-500",
            },
            {
                label: "Trial",
                count: "1,434",
                percent: "11.2%",
                dotClass: "bg-gray-400",
            },
        ],
    },
    {
        title: "Top APIS Consultadas",
        icon: Zap,
        type: "features",
        items: [
            { label: "Credit Score Monitoring", value: "89.2%" },
            { label: "Alert Notifications", value: "76.8%" },
            { label: "Credit Report Analysis", value: "68.4%" },
            { label: "Identity Monitoring", value: "54.7%" },
            { label: "Credit Improvement Tips", value: "42.1%" },
        ],
    },
]);
</script>

<template>
    <AppLayout title="Historial Crediticio">
        <template #card-header>
            <Menubar :model="menubarItems">
                <template #end>
                    <i class="pi pi-search px-2" />
                    <i class="pi pi-bars px-2" />
                </template>
            </Menubar>

            <div class="pl-6">
                <div class="flex flex-col pt-4">
                    <h2 class="text-2xl font-bold mb-4">Resumen del historial de crédito de los clientes</h2>
                </div>

                <SelectButton v-model="selectedSic" :options="sics" optionLabel="nombre"
                    :option-disabled="(sic) => !sic.activo" :allow-empty="false" />
            </div>
        </template>

        <template #card-content>
            <div class="flex flex-col gap-6">
                <!-- Primary Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <MetricCard v-for="(m, index) in primaryMetrics" :key="index" :title="m.title" :value="m.value"
                        :change="m.change">
                        <template #icon>
                            <component :is="m.icon" size="24" />
                        </template>
                    </MetricCard>
                </div>
    
                <!-- TODO: Adaptar tema oscuro -->
                <!-- Charts & Tables -->
                <QueryChart />
                <ClientActivityTable :sic-queries-paginated="sicQueriesPaginated" />
    
                <!-- Additional Analytics -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Client Distribution -->
                    <div v-for="(section, idx) in additionalSections" :key="idx"
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 transition-all duration-300 hover:shadow-md">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <component :is="section.icon" size="20" class="text-indigo-600 mr-2" />
                            {{ section.title }}
                        </h3>
                        <div v-if="section.type === 'distribution'" class="space-y-4">
                            <div v-for="(item, i) in section.items" :key="i" class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div :class="['w-3 h-3 rounded-full mr-3', item.dotClass]"></div>
                                    <span class="text-sm text-gray-600">{{ item.label }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-medium text-gray-900">{{ item.count }}</span>
                                    <span class="text-xs text-gray-500 ml-1">({{ item.percent }})</span>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="section.type === 'features'" class="space-y-4">
                            <div v-for="(f, i) in section.items" :key="i" class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">{{ f.label }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ f.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>