<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({ model: { type: Array, default: () => [] }, preview: Boolean });
const hints = computed(() => {
    const collect = items => items.flatMap(item => [item.reason, ...collect(item.items ?? [])]).filter(Boolean);
    return [...new Set(collect(props.model))];
});
</script>

<template>
    <div class="k-context-menu">
        <Menubar :model="model" :aria-label="preview ? 'Vista previa del menú' : 'Acciones de esta pantalla'" :buttonProps="{ 'aria-label': 'Abrir acciones de esta pantalla' }" breakpoint="767px">
            <template #item="{ item, props: itemProps, root, hasSubmenu }">
                <component :is="item.url && !preview && !item.disabled ? (item.external ? 'a' : Link) : 'span'"
                    v-bind="itemProps.action" :href="!preview && !item.disabled ? item.url : undefined"
                    :class="{ 'k-context-root': root }" :aria-disabled="item.disabled || undefined">
                    <span v-if="!root && item.icon" :class="item.icon" aria-hidden="true" />
                    <span>{{ item.label }}</span>
                    <span v-if="hasSubmenu" :class="['pi', root ? 'pi-angle-down' : 'pi-angle-right', 'k-context-chevron']" aria-hidden="true" />
                </component>
            </template>
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
.k-context-chevron { margin-left: auto; font-size: 10px; color: var(--k-muted); }
.k-context-root .k-context-chevron { margin-left: 2px; }
.k-context-hint { padding: 6px 12px; font-size: 12px; color: var(--k-muted); }
.k-context-menu :deep(.p-menubar-item-content:has(:focus-visible)), .k-context-menu :deep(.p-focus > .p-menubar-item-content) { outline: 2px solid var(--k-brand); outline-offset: -2px; }
@media (max-width: 767px), (pointer: coarse) {
    .k-context-menu :deep(.p-menubar-item-link), .k-context-menu :deep(.p-menubar-button) { min-height: 44px; }
    .k-context-menu :deep(.p-menubar-item-link) { font-size: 14px; }
}
</style>
