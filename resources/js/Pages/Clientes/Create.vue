<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import FormCliente from "./Partials/FormCliente.vue";
</script>

<template>
    <AppLayout title="Crear nuevo cliente">
        <!-- <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Clientes
            </h2>
        </template> -->

        <div class="py-12 pt-0">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <FormCliente />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
