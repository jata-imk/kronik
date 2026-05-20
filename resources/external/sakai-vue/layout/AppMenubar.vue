<script setup>
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import MenubarQuickAdd from "@/Components/MenubarQuickAdd.vue";

const page = usePage();
const menubarItems = computed(() => page.props.menubarItems ?? []);
const quickAddRef = ref(null);

const opcionesSuperAdmin = [
    {
        label: "Configurar menubar",
        icon: "pi pi-bars",
        command: () => {
            window.location.href = route("admin.menubar-items.index");
        },
    },
];
</script>

<template>
    <Menubar :model="menubarItems">
        <template #item="{ item, props }">
            <a v-if="item.url" :href="item.url" v-bind="props.action">
                <span :class="item.icon" />
                <span>{{ item.label }}</span>
            </a>
            <span v-else v-bind="props.action">
                <span :class="item.icon" />
                <span>{{ item.label }}</span>
            </span>
        </template>
        <template #end>
            <div class="flex items-center gap-1">
                <Button
                    v-if="page.props.menubarAdmin"
                    icon="pi pi-plus"
                    rounded
                    text
                    severity="secondary"
                    title="Agregar item al menú"
                    @click="quickAddRef?.open()" />
                <SplitButton
                    :buttonProps="{ class: '!hidden' }"
                    dropdownIcon="pi pi-cog"
                    :model="opcionesSuperAdmin" />
            </div>
        </template>
    </Menubar>

    <MenubarQuickAdd ref="quickAddRef" />
</template>
