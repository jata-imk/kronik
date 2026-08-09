<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import ListadoClientes from "./Partials/ListadoClientes.vue";

const { clientes, can, filters, sucursales } = defineProps({
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
    <AppLayout title="Listado de clientes">
        <template #card-header>
            <div class="flex justify-between items-center pl-4 pt-4">
                <h2 class="text-2xl font-bold">Gestión de clientes</h2>
            </div>
        </template>

        <template #card-content>
            <ListadoClientes :clientes="clientes" :can="can" :filters="filters" :sucursales="sucursales" />
        </template>
    </AppLayout>
</template>
