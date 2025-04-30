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
        <div class="pb-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <template #content>
                        <ListadoClientes :clientes="clientes" />
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
