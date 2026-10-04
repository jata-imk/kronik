<script setup>
import AppMenu from "./AppMenu.vue";
import { useLayout } from "@sakai-vue/layout/composables/layout";
const { closeMenu } = useLayout();
</script>

<template>
    <div id="k-main-sidebar" class="layout-sidebar">
        <div class="k-mobile-sidebar-head"><span>Menú</span><button type="button" class="k-mobile-sidebar-close" aria-label="Cerrar menú" @click="closeMenu"><i class="pi pi-times" aria-hidden="true" /></button></div>
        <app-menu></app-menu>
    </div>
</template>

<style lang="scss" scoped></style>
