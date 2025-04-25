<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import ListadoClientes from "./Partials/ListadoClientes.vue";

const { confirmsTwoFactorAuthentication, sessions, clientes } = defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
    clientes: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <AppLayout title="Listado de clientes">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Clientes
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <ListadoClientes :clientes="clientes" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
