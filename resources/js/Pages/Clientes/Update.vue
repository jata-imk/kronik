<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import FormCliente from "./Partials/FormCliente.vue";

</script>

<template>
    <AppLayout title="Informacion del cliente">
        <template #card-header>
            <div class="flex flex-wrap justify-between items-center gap-3 px-6 pt-4">
                <h2 class="text-2xl font-bold mb-4">Editar informacion del cliente</h2>
            </div>
        </template>
        <template #card-content><FormCliente /></template>
    </AppLayout>
</template>
