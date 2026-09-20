<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import AppMenuItem from "./AppMenuItem.vue";

const page = usePage();
const model = computed(() => page.props.navigation ?? []);
</script>

<template>
    <ul class="layout-menu">
        <app-menu-item v-for="(item, i) in model" :key="item.label" :item="item" :index="i" />
    </ul>
</template>
