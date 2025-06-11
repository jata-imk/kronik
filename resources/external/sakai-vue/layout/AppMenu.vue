<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import AppMenuItem from "./AppMenuItem.vue";

const page = usePage();
const model = ref([
    {
        label: "Inicio",
        items: [
            {
                label: "Tablero general",
                icon: "pi pi-fw pi-home",
                to: "dashboard",
            },
            {
                label: "Ejemplos",
                icon: "pi pi-fw pi-bolt",
                to: "sakai",
            },
        ],
    },
    {
        label: "Modulos",
        items: [
            {
                label: "CRM",
                icon: "pi pi-fw pi-users",
                items: [
                    {
                        label: "Clientes",
                        icon: "pi pi-fw pi-users",
                        to: "clientes.index",
                    },
                    {
                        label: "Historial crediticio",
                        icon: "pi pi-credit-card",
                        to: "historial-crediticio.index",
                    },
                ],
            },
        ],
    },
    {
        label: "Configuraciones",
        items: [
            {
                label: "Configurar equipo actual",
                icon: "pi pi-fw pi-users",
                to: "teams.show",
                toParams: { team: page.props.auth.user.current_team },
            },
            {
                label: "Editar perfil",
                icon: "pi pi-fw pi-user-edit",
                to: "profile.show",
            },
            page.props.auth.user.roles.find(
                (r) => r.name === "Super Admin",
            ) && {
                label: "Panel de superusuario",
                icon: "pi pi-fw pi-cog",
                to: "admin.dashboard",
            },
        ].filter(Boolean),
    },
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
