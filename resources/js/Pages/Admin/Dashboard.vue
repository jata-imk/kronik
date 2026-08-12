<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const can = (permission) => page.props.auth.is_super_admin || page.props.auth.permissions?.[permission] === true;
</script>

<template>
    <AppLayout title="Opciones de Super Usuario">
        <template #card-header>
            <div class="flex justify-between items-center pl-8 pt-4">
                <h2 class="text-2xl font-bold mb-4">Configuraciones del Super Admin</h2>
            </div>
        </template>

        <template #card-content>
            <div class="pl-8 flex flex-col items-start justify-center gap-2">
                <div class="flex gap-2 mb-3">
                    <Button v-if="can('read-users')" label="Usuarios" as="a" :href="route('admin.users.index')" />
                    <Button v-if="can('read-teams')" label="Equipos" as="a" :href="route('admin.teams.index')" />
                    <Button v-if="can('read-activity-log')" label="Logs de Actividades" as="a" :href="route('admin.users.activity')" />
                </div>
                <Button v-if="can('read-configuracion-empresa')" label="Configuración de empresa" as="a" :href="route('admin.configuracion-empresa.index')" class="mb-3" />
                <Button v-if="can('read-sucursales')" label="Sucursales" as="a" :href="route('admin.sucursales.index')" class="mb-3" />
                <Button v-if="can('read-menubar-items')" label="Configurar menubar" as="a" :href="route('admin.menubar-items.index')" class="mb-3" />
                <Button v-if="can('read-roles')" label="Configurar roles" as="a" :href="route('admin.roles.index')" class="mb-3" />
            </div>
        </template>
    </AppLayout>
</template>
