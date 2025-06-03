<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";

const page = usePage();
const menubarItems = ref(page.props.menubarItems);
</script>

<template>
    <AppLayout title="Opciones de Super Usuario">
        <template #card-header>
            <Menubar :model="menubarItems">
                <template #end>
                    <i class="pi pi-search px-2" />
                    <i class="pi pi-bars px-2" />
                </template>
            </Menubar>

            <div class="flex justify-between items-center pl-8 pt-4">
                <h2 class="text-2xl font-bold mb-4">Configuraciones generales del super usuario</h2>
            </div>
        </template>

        <template #card-content>
            <div class="pl-8 flex flex-col items-start justify-center gap-4">
                <Button label="Usuarios" as="a" :href="route('admin.users.index')" class="mb-3" />
                <Button label="Configurar menubar" as="a" :href="route('admin.menubar-items.index')" class="mb-3" />
                <Button label="Configurar roles" as="a" :href="route('admin.roles.index')" class="mb-3" />
            </div>
        </template>
    </AppLayout>
</template>
