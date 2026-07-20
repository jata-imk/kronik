<script setup>
import { router, usePage } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
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
            <div class="flex flex-wrap justify-between items-center gap-3 px-6 pt-4">
                <h2 class="text-2xl font-bold mb-4">Cliente {{ nombreCompleto }}</h2>
                <Button label="Abrir expediente" icon="pi pi-folder-open" class="mb-4" @click="router.visit(route('clientes.expediente.show', page.props.cliente.id))" />
            </div>
        </template>
        <template #card-content><FormCliente /></template>
    </AppLayout>
</template>
