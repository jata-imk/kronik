<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import ListadoClientes from "./Partials/ListadoClientes.vue";

const { clientes, can, filters, sucursales, view } = defineProps({
    view: { type: String, default: "clientes" },
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
    clientes: {
        type: Array,
        default: () => [],
    },
    can: {
        type: Object,
        default: () => ({}),
    },
    filters: { type: Object, default: () => ({ scope: "current" }) },
    sucursales: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout :title="view === 'expedientes' ? 'Expedientes KYC' : 'Clientes'">
        <template #card-header>
            <PageHeader eyebrow="Operación · Clientes" :title="view === 'expedientes' ? 'Expedientes KYC' : 'Clientes'" :description="view === 'expedientes' ? 'Consulta la documentación y el estado de cada expediente.' : 'Gestiona la información de tus clientes y sus sucursales.'" />
        </template>

        <template #card-content>
            <ListadoClientes :clientes="clientes" :can="can" :filters="filters" :sucursales="sucursales" :view="view" />
        </template>
    </AppLayout>
</template>
