<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import FormCliente from "./Partials/FormCliente.vue";
</script>

<template>
    <AppLayout title="Crear nuevo cliente">
        <template #card-header>
            <div class="flex justify-between items-center pl-4 pt-4">
                <h2 class="text-2xl font-bold">Formulario de creación de clientes</h2>
            </div>
        </template>

        <template #card-content>
            <FormCliente />
        </template>
    </AppLayout>
</template>
