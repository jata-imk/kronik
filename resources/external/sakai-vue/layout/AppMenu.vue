<script setup>
import { computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import AppMenuItem from "./AppMenuItem.vue";
import { useLayout } from "./composables/layout";
import { findMenuSelection } from "@/Utils/sidebarNavigation";

const page = usePage();
const model = computed(() => page.props.navigation ?? []);
const { syncMenuNavigation } = useLayout();
const pathname = computed(() => new URL(page.url ?? "/", window.location.origin).pathname);

watch([pathname, () => JSON.stringify(model.value)], ([path, structure]) => {
    const selected = findMenuSelection(model.value, (name) => route().current(name));
    syncMenuNavigation(path, structure, selected?.ancestors.at(-1) ?? null);
}, { immediate: true, flush: "post" });
</script>

<template>
    <ul class="layout-menu">
        <app-menu-item v-for="(item, i) in model" :key="item.label" :item="item" :index="i" />
    </ul>
</template>
