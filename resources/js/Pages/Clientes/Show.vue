<script setup>
import { usePage } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { computed } from "vue";
import FormCliente from "./Partials/FormCliente.vue";

const page = usePage();
const nombreCompleto = computed(() =>
    [
        page.props.cliente.primer_nombre,
        page.props.cliente.segundo_nombre,
        page.props.cliente.apellido_paterno,
        page.props.cliente.apellido_materno,
    ]
        .filter(Boolean)
        .join(" "),
);
</script>

<template>
    <AppLayout title="Informacion del cliente">
        <template #card-header>
            <PageHeader eyebrow="Operación · Clientes" :title="nombreCompleto" description="Información y documentos del cliente." />
        </template>
        <template #card-content><FormCliente /></template>
    </AppLayout>
</template>
