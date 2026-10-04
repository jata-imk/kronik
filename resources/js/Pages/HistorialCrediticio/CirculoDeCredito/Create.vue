<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";

defineProps({
    cliente: { type: Object, default: null },
    motivoNoDisponible: { type: String, required: true },
});
</script>

<template>
    <AppLayout title="Disponibilidad de consultas SIC">
        <template #card-header><PageHeader eyebrow="Operación · Clientes" title="Disponibilidad de consultas SIC" description="Consulta el estado de la integración antes de continuar." /></template>
        <template #card-content>
            <div class="space-y-5 p-6">
                <Message severity="warn" :closable="false">{{ motivoNoDisponible }}</Message>
                <p>El historial se conserva. No se enviarán datos al proveedor ni se generarán costos desde esta pantalla.</p>
                <Link v-if="cliente" :href="route('clientes.expediente.show', cliente.id)" class="text-primary underline">Volver al expediente y revisar la autorización</Link>
                <Link v-else :href="route('clientes.index')" class="text-primary underline">Volver a clientes</Link>
            </div>
        </template>
    </AppLayout>
</template>
