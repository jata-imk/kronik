<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import FormCliente from "./Partials/FormCliente.vue";

const page = usePage();
const menubarItems = ref(page.props.menubarItems);
</script>

<template>
    <AppLayout title="Crear nuevo cliente">
        <template #card-header>
            <Menubar :model="menubarItems">
                <template #end>
                    <i class="pi pi-search px-2" />
                    <i class="pi pi-bars px-2" />
                </template>
            </Menubar>

            <div class="flex justify-between items-center pl-4 pt-4">
                <h2 class="text-2xl font-bold">Formulario de creación de clientes</h2>
            </div>
        </template>

        <template #card-content>
            <FormCliente />
        </template>
    </AppLayout>
</template>