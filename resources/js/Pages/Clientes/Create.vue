<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import FormCliente from "./Partials/FormCliente.vue";
</script>

<template>
    <AppLayout title="Crear nuevo cliente">
        <template #card-header>
            <PageHeader eyebrow="Operación · Clientes" title="Nuevo cliente" description="Registra la información necesaria para abrir su expediente." />
        </template>

        <template #card-content>
            <FormCliente />
        </template>
    </AppLayout>
</template>
