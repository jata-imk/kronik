<script setup>
import { ref, watch } from "vue";
import { useToast } from "primevue/usetoast";
import { usePage, useForm } from "@inertiajs/vue3";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";

const toast = useToast();
const page = usePage();

const readOnly = ref(page.props.readOnly || false);
const api = ref(page.props.api);
const cliente = ref(page.props.cliente);
const menubarItems = ref(page.props.menubarItems);

const form = useForm({
    api: api.value ?? "",
});

const apisCirculoDeCredito = ref([
    {
        label: "FICO® Score",
        value: "fico_score_v2",
        description:
            "La API de FICO® Score determina la probabilidad de incumplimiento de un acreditado en los próximos doce meses. A mayor puntaje de score, menor es el riesgo.",
    },
    {
        label: "Fintech Score",
        value: "fintech",
        description:
            "La API FinTech Score es una herramienta con la que ordenas una población de solicitantes por su nivel de riesgo cubriendo las necesidades de las FinTechs como los son: plazos más cortos que van de 1 día hasta 3 meses en promedio, montos de $3.5K promedio, renovaciones con mayor frecuencia, mayores tasas de interés y disponibilidad inmediata.",
    },
    {
        label: "Reporte de Crédito Fico Score",
        value: "fico_score",
        description:
            "Esta API reporta el historial crediticio, el cumplimiento de pago de los compromisos que la persona ha adquirido con entidades financieras, no financieras e instituciones comerciales que dan crédito o participan en actividades afines al crédito. En esta versión se retornan los campos del Crédito Asociado a Nomina (CAN) en el nodo de créditos.",
    },
    {
        label: "Reporte de Crédito Consolidado con FICO Score",
        value: "fico_score_consolidado",
        description:
            "Este reporte muestra el historial crediticio, el cumplimiento de pago de los compromisos que la persona ha adquirido con entidades financieras, no financieras e instituciones comerciales que dan crédito o participan en actividades afines al crédito.",
    },
    {
        label: "Reporte de Crédito Consolidado con FICO Score v2",
        value: "fico_score_consolidado_v2",
        description:
            "Esta API simula el reporte del historial crediticio, el cumplimiento de pago de los compromisos que la persona ha adquirido con entidades financieras, no financieras e instituciones comerciales que dan crédito o participan en actividades afines al crédito. En esta versión se retornan los campos del Crédito Asociado a Nomina (CAN) en el nodo de créditos.",
    },
    {
        label: "Reporte de Crédito Consolidado con FICO Score y PLD Check MX",
        value: "fico_score_consolidado_pld_check",
        description:
            "Esta API simula: el reporte del historial crediticio; el cumplimiento de pago de los compromisos que la persona ha adquirido con entidades financieras, no financieras e instituciones comerciales que dan crédito o participan en actividades afines al crédito; y filtra contra listas de cumplimiento para Prevención de Lavado de Dinero. En esta versión se retornan los campos del Crédito Asociado a Nomina (CAN) en el nodo de créditos.",
    },
    {
        label: "FICO Extended Score v2",
        value: "fico_extended_score_v2",
        description:
            "La API para FICO® Score Extended, es el primer score en el mercado mexicano que califica el nivel de cumplimiento de pago de un individuo considerando al grupo de personas con las que comparte domicilio.",
    },
]);

const selectedApi = ref();

watch(
    () => form.api,
    () => {
        selectedApi.value = apisCirculoDeCredito.value.find(
            (api) => api.value === form.api,
        );
        api.value = form.api;
    },
);

const onSubmit = () => {
    form.post(route("circulo-credito.store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            toast.add({
                severity: "success",
                summary: "Consulta realizada exitosamente",
                life: 3000,
            });
        },
    });
};
</script>

<template>
    <AppLayout title="Listado de clientes">
        <template #card-header>
            <Menubar :model="menubarItems">
                <template #end>
                    <i class="pi pi-search px-2" />
                    <i class="pi pi-bars px-2" />
                </template>
            </Menubar>

            <div class="flex justify-between items-center pl-8 pt-4">
                <h2 class="text-2xl font-bold mb-4">Nueva consulta</h2>
            </div>
        </template>

        <template #card-content>
            <form @submit.prevent="onSubmit" class="grid gap-4">
                <label class="block text-sm font-medium mb-1" for="api-circulo-de-credito">Seleccione el tipo de consulta</label>
                <Select
                    id="api-circulo-de-credito" name="api-circulo-de-credito"
                    v-model="form.api" :disabled="readOnly"
                    :options="apisCirculoDeCredito"
                    optionLabel="label"
                    optionValue="value"
                    fluid :invalid="!!form.errors.api" />
                <Message v-if="form.errors.api" severity="error" size="small">
                    {{ form.errors.api }}
                </Message>

                <Message v-if="selectedApi && selectedApi.description" severity="info" size="small" class="mt-2">
                    {{ selectedApi.description }}
                </Message>

                <div class="flex justify-end">
                    <Button v-if="!readOnly && form.api" class="mt-4" label="Consultar Historial Crediticio" type="submit" :disabled="form.processing" :loading="form.processing" />
                </div>
            </form>
        </template>
    </AppLayout>
</template>
