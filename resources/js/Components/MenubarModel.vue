<script setup>
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({ model: { type: Array, default: () => [] }, preview: Boolean });
const visibleModel = computed(() => {
    const clean = (items, root = false) => items.map(item => ({
        ...item, icon: root ? undefined : item.icon, url: props.preview ? undefined : item.url,
        command: undefined, items: item.items ? clean(item.items) : undefined,
    }));
    return clean(props.model, true);
});
const hints = computed(() => {
    const collect = items => items.flatMap(item => [item.reason, ...collect(item.items ?? [])]).filter(Boolean);
    return [...new Set(collect(props.model))];
});
function navigate(event) {
    const anchor = event.target.closest?.("a[href]");
    if (!anchor) return;
    if (props.preview) { event.preventDefault(); return; }
    if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || anchor.target === "_blank") return;
    const flatten = items => items.flatMap(item => [item, ...flatten(item.items ?? [])]);
    const node = flatten(props.model).find(item => item.url && new URL(item.url, window.location.origin).href === anchor.href);
    if (node && !node.external && !node.native && new URL(anchor.href).origin === window.location.origin) {
        event.preventDefault();
        router.visit(anchor.href);
    }
}
</script>

<template>
    <div class="k-context-menu" @click="navigate">
        <Menubar :model="visibleModel" :aria-label="preview ? 'Vista previa del menú' : 'Acciones de esta pantalla'" :buttonProps="{ 'aria-label': 'Abrir acciones de esta pantalla' }" breakpoint="767px">
            <template #end><slot name="end" /></template>
        </Menubar>
        <p v-for="hint in hints" :key="hint" class="k-context-hint">{{ hint }}</p>
    </div>
</template>

<style scoped>
.k-context-menu :deep(.p-menubar) { min-height: 38px; padding: 0 8px; border: 0; border-bottom: 1px solid var(--k-line); border-radius: 0; gap: 4px; font-size: 13px; }
.k-context-menu :deep(.p-menubar-item-link) { padding: 7px 9px; gap: 7px; font-size: 13px; line-height: 20px; }
.k-context-menu :deep(.p-menubar-root-list) { gap: 2px; }
.k-context-menu :deep(.p-menubar-submenu) { min-width: 220px; max-width: min(320px, calc(100vw - 32px)); padding: 4px; }
.k-context-menu :deep(.p-menubar-item-link span) { overflow-wrap: anywhere; }
.k-context-menu :deep(.p-menubar-submenu-icon) { width: 10px; height: 10px; color: var(--k-muted); }
.k-context-hint { padding: 6px 12px; font-size: 12px; color: var(--k-muted); }
.k-context-menu :deep(.p-menubar-item-content:has(:focus-visible)), .k-context-menu :deep(.p-focus > .p-menubar-item-content) { outline: 2px solid var(--k-brand); outline-offset: -2px; }
@media (max-width: 767px), (pointer: coarse) {
    .k-context-menu :deep(.p-menubar-item-link), .k-context-menu :deep(.p-menubar-button) { min-height: 44px; }
    .k-context-menu :deep(.p-menubar-item-link) { font-size: 14px; }
}
</style>
