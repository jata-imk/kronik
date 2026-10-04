<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import FormCliente from "./Partials/FormCliente.vue";

</script>

<template>
    <AppLayout title="Informacion del cliente">
        <template #card-header>
            <PageHeader eyebrow="Operación · Clientes" title="Editar cliente" description="Actualiza los datos del expediente." />
        </template>
        <template #card-content><FormCliente /></template>
    </AppLayout>
</template>
