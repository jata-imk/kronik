<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import FormCliente from "./Partials/FormCliente.vue";
</script>

<template>
    <AppLayout title="Información del cliente">
        <div class="pb-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <template #content>
                        <FormCliente />
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
