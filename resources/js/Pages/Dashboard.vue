<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { Link, usePage } from "@inertiajs/vue3";
const page = usePage();
</script>

<template>
    <AppLayout title="Tablero de inicio">
        <template #card-content>
            <div class="space-y-4 p-6">
                <h1 class="text-2xl font-semibold">Tablero de inicio</h1>
                <p>Usa el menú para acceder a los módulos disponibles para tu perfil.</p>
                <Link v-if="page.props.auth.is_super_admin || (page.props.auth.permissions?.['read-solicitudes'] && page.props.auth.permissions?.['read-clientes'])" :href="route('solicitudes.trabajo')" class="text-primary underline">Abrir Mi trabajo: solicitudes y siguientes pasos</Link>
            </div>
        </template>
    </AppLayout>
</template>
