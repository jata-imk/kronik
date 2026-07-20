<script setup>
import { ref, reactive, computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import CreditScoreCard from "@/Components/HistorialCrediticio/CreditScoreCard.vue";
import CreditFactorsCard from "@/Components/HistorialCrediticio/CreditFactorsCard.vue";
import TransactionTimeLine from "@/Components/HistorialCrediticio/TransactionTimeline.vue";
import RecommendationCard from "@/Components/HistorialCrediticio/RecommendationCard.vue";
import NoDataScreen from "@/Components/HistorialCrediticio/NoDataScreen.vue";
import ActionButton from "@/Components/HistorialCrediticio/ActionButton.vue";

const page = usePage();

const cliente = page.props.cliente;
const sics = page.props.sics;
const selectedSic = ref(sics.find((sic) => Boolean(sic.activo) === true));

const creditScoreHistory = computed(
    () => page.props.sicsQueries && page.props.sicsQueries.length > 0,
);
const sicsQueries = page.props.sicsQueries;

const antepenultimateSicQuery = page.props.antepenultimateSicQuery;
const antepenultimateSicQueryData = ref(antepenultimateSicQuery?.response_data);

const lastSicQuery = page.props.lastSicQuery;
const lastSicQueryData = ref(lastSicQuery?.response_data);

const creditScoreData = reactive({
    score:
        lastSicQueryData.value?.scores?.[0].valor ||
        lastSicQueryData.value?.score?.valor,
    previousScore:
        antepenultimateSicQueryData.value?.scores?.[0].valor ||
        antepenultimateSicQueryData.value?.score.valor ||
        0,
    maxScore: 850,
});

const creditTransactions = ref([
    {
        id: "trx-1",
        date: "2025-03-25",
        description: "La puntuación crediticia aumentó",
        amount: 0,
        type: "credit_change",
        impact: "positive",
    },
    {
        id: "trx-2",
        date: "2025-03-20",
        description: "Pago de préstamo de auto",
        amount: 450,
        type: "payment",
        accountName: "Préstamo para auto - Bank of America",
        impact: "positive",
    },
    {
        id: "trx-3",
        date: "2025-03-15",
        description: "Pago de tarjeta de crédito",
        amount: 300,
        type: "payment",
        accountName: "Recompensas Platinum - Chase Bank",
        impact: "positive",
    },
    {
        id: "trx-4",
        date: "2025-03-12",
        description: "Pago de préstamo personal",
        amount: 350,
        type: "payment",
        accountName: "Préstamo personal - SoFi",
        impact: "positive",
    },
    {
        id: "trx-5",
        date: "2025-03-10",
        description: "Pago de tarjeta de crédito",
        amount: 200,
        type: "payment",
        accountName: "Visa con reembolso en efectivo - Capital One",
        impact: "positive",
    },
    {
        id: "trx-7",
        date: "2025-03-05",
        description: "Cargo por pago tardío",
        amount: 39,
        type: "fee",
        accountName: "Recompensas de viaje - American Express",
        impact: "negative",
    },
    {
        id: "trx-8",
        date: "2025-03-01",
        description: "Pago hipotecario",
        amount: 1850,
        type: "payment",
        accountName: "Hipoteca de vivienda - Wells Fargo",
        impact: "positive",
    },
    {
        id: "trx-9",
        date: "2025-02-28",
        description: "Nueva cuenta abierta",
        amount: 0,
        type: "credit_change",
        accountName: "Plan de pago de teléfono - Verizon",
        impact: "neutral",
    },
]);

</script>

<template>
    <AppLayout title="Listado de clientes">
        <template #card-header>
            <div class="pl-6">
                <div class="flex justify-between items-center pt-4">
                    <h2 class="text-2xl font-bold mb-4">Listado de consultas</h2>
                </div>
    
                <SelectButton v-model="selectedSic" :options="sics" optionLabel="nombre" :option-disabled="(sic) => !sic.activo" :allow-empty="false" />
            </div>
        </template>

        <template #card-content>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <NoDataScreen v-if="!creditScoreHistory"
                    type="credit-score"
                    :primaryAction="{
                        label: 'Realizar nueva consulta de ' + selectedSic.nombre,
                        url: route(`${selectedSic.clave}.create`, { cliente: (cliente.id || 'null') })
                    }" />
                <CreditScoreCard v-if="creditScoreHistory" :score="creditScoreData.score" :previousScore="creditScoreData.previousScore" :maxScore="creditScoreData.maxScore" />
                
                <CreditFactorsCard v-if="creditScoreHistory" />
            </div>

            <div className="mt-6">
                <NoDataScreen v-if="!creditScoreHistory"
                    type="history"
                    :primary-action="{
                        url: '#'
                    }" />
                <TransactionTimeLine v-if="creditScoreHistory" :transactions="creditTransactions" />
                <RecommendationCard class="mt-6" />
            </div>
        </template>
    </AppLayout>
</template>
