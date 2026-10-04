<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const can = (permission) => page.props.auth.is_super_admin || page.props.auth.permissions?.[permission] === true;
const sections = computed(() => [
    { title: "Organización", description: "Personas, equipos y accesos a la operación.", items: [
        { label: "Usuarios", icon: "pi pi-users", route: "admin.users.index", permission: "read-users" },
        { label: "Equipos", icon: "pi pi-sitemap", route: "admin.teams.index", permission: "read-teams" },
        { label: "Roles y permisos", icon: "pi pi-key", route: "admin.roles.index", permission: "read-roles" },
    ] },
    { title: "Empresa", description: "Configuración general y sucursales.", items: [
        { label: "Datos de empresa", icon: "pi pi-building", route: "admin.configuracion-empresa.index", permission: "read-configuracion-empresa" },
        { label: "Sucursales", icon: "pi pi-map-marker", route: "admin.sucursales.index", permission: "read-sucursales" },
    ] },
    { title: "Control", description: "Actividad y navegación de la aplicación.", items: [
        { label: "Registro de actividad", icon: "pi pi-history", route: "admin.users.activity", permission: "read-activity-log" },
        { label: "Menú contextual", icon: "pi pi-bars", route: "admin.menubar-items.index", permission: "read-menubar-items" },
    ] },
].map((section) => ({ ...section, items: section.items.filter((item) => can(item.permission)) })).filter((section) => section.items.length));
</script>

<template>
    <AppLayout title="Administración">
        <template #card-header><PageHeader eyebrow="Configuración · Administración" title="Administración" description="Gestiona la estructura y los accesos de Kronik." /></template>
        <template #card-content>
            <div class="p-4 md:p-6">
                <div class="k-admin-grid">
                    <section v-for="section in sections" :key="section.title" class="k-surface k-panel">
                        <h2 class="text-lg font-semibold">{{ section.title }}</h2><p class="k-page-description">{{ section.description }}</p>
                        <div class="k-shortcuts mt-4"><Link v-for="item in section.items" :key="item.route" :href="route(item.route)"><span class="k-shortcut-icon"><i :class="item.icon" aria-hidden="true" /></span><span><strong>{{ item.label }}</strong></span><i class="pi pi-arrow-up-right" aria-hidden="true" /></Link></div>
                    </section>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
