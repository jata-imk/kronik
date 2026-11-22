<script setup>
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import MenubarQuickAdd from "@/Components/MenubarQuickAdd.vue";
import MenubarModel from "@/Components/MenubarModel.vue";

const page = usePage();
const items = computed(() => (page.props.menubarItems ?? []).filter(item => item.kind !== "system"));
const admin = computed(() => page.props.menubarAdmin);
const quickAdd = ref(null);
const configuration = ref(null);
const configurationOpen = ref(false);
const options = computed(() => [
    ...(admin.value?.canCreate ? [{ label: "Agregar acción aquí", command: () => quickAdd.value?.open() }] : []),
    ...(admin.value?.canRead ? [{ label: "Administrar menú", command: () => router.visit(route("admin.menubar-items.index")) }] : []),
]);
</script>

<template>
    <MenubarModel v-if="items.length || options.length" :model="items">
        <template #end>
            <div v-if="options.length" class="flex items-center gap-2">
                <span v-if="!items.length && page.props.menubarEstado?.status !== 'unavailable'" class="k-menu-empty">Sin acciones en esta pantalla</span>
                <Button label="Configurar" icon="pi pi-angle-down" iconPos="right" text severity="secondary" size="small" class="k-menu-config"
                    aria-haspopup="menu" aria-controls="k-menubar-configuration" :aria-expanded="configurationOpen" @click="configuration.toggle($event)" />
                <Menu ref="configuration" id="k-menubar-configuration" :model="options" popup @show="configurationOpen = true" @hide="configurationOpen = false" />
            </div>
        </template>
    </MenubarModel>
    <div v-if="page.props.menubarEstado?.status === 'unavailable'" class="px-3 py-2 text-sm" role="alert">
        {{ page.props.menubarEstado.message }} <Button label="Reintentar" text size="small" severity="secondary" @click="router.reload({ only: ['menubarItems', 'menubarEstado', 'menubarAdmin', 'auth'] })" />
    </div>
    <MenubarQuickAdd ref="quickAdd" />
</template>

<style scoped>
.k-menu-empty { color: var(--k-muted); font-size: 12px; }
.k-menu-config { border-radius: 3px; padding: 5px 8px; font-size: 13px; background: transparent; }
@media (max-width: 767px), (pointer: coarse) {
    .k-menu-config { min-height: 44px; }
    .k-menu-empty { display: none; }
}
</style>
