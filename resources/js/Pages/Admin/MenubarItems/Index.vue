<script setup>
import { ref, watch } from "vue";
import { NodeService } from "@sakai-vue/service/NodeService";
import { router, usePage } from "@inertiajs/vue3";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import MenubarItemForm from "./Form.vue";

const page = usePage();

const items = ref([]);
const modules = ref(page.props.modules);
const menubarItems = ref(null);

watch(
    () => page.props.menubarItems,
    () => {
        menubarItems.value = page.props.menubarItems;
        items.value = NodeService.toTreeNodes(menubarItems.value);
    },
    {
        immediate: true,
    },
);

const loading = ref(false);
const selectedItem = ref(null);
const showForm = ref(false);

const createItem = () => {
    selectedItem.value = null;
    showForm.value = true;
};
const editItem = (item) => {
    selectedItem.value = item;
    showForm.value = true;
};
const deleteItem = (item) => {
    if (confirm("¿Seguro que deseas eliminar este item?")) {
        router.delete(route("admin.menubar-items.destroy", item.id), {
            only: ["menubarItems"],
        });
    }
};
</script>

<template>
    <AppLayout title="Configuración de barra de menú por módulos">
        <template #card-header>
            <div class="flex items-center p-4">
                <Button icon="pi pi-arrow-left"  as="a" :href="route('admin.dashboard')"></Button>
                <h2 class="text-2xl font-bold ml-4">Configuración de barra de menú por módulos</h2>
            </div>
        </template>

        <template #card-content>
            <div class="flex justify-end pb-8">
                <Button label="Crear nuevo" icon="pi pi-plus" class="mt-3" @click="createItem" />
            </div>

            <TreeTable :value="items" paginator :rows="10" :loading="loading" responsiveLayout="scroll">
                <Column field="label" header="Etiqueta" expander>
                    <template #body="{ node }">
                        <span :class="node.data.icon"></span>
                        <span class="ml-2">{{ node.data.label }}</span>
                    </template>
                </Column>
                <Column field="module_id" header="Modulo(s)" :pt="{ columnheadercontent: { class: 'justify-center' } }">
                    <template #body="{ node }">
                        <div class="w-full flex flex-col items-center gap-2">
                            <Tag
                                v-if="node.data.modules"
                                v-for="module in node.data.modules"
                                :key="module.id"
                                severity="primary"
                                rounded="">
                                {{ module.name }}
                            </Tag>
                        </div>
                    </template>
                </Column>
                <Column header="Tipo y valor" :pt="{ columnheadercontent: { class: 'justify-center' } }">
                    <template #body="{ node }">
                        <Tag
                            v-if="node.data.type === 'menu'"
                            severity="info"
                            rounded
                            icon="pi pi-folder"
                            class="mx-auto">
                            Carpeta
                        </Tag>
                        
                        <div
                            v-if="node.data.type === 'route:name' && node.data.value"
                            class="w-full flex flex-col items-center justify-center gap-2">
                            <Tag
                                severity="info"
                                rounded>
                                    Ruta de Laravel
                            </Tag>
                            <Tag
                                severity="contrast"
                                rounded>
                                    <span class="pi pi-directions"></span>
                                    <p 
                                        class="max-w-40 truncate text-ellipsis overflow-hidden"
                                        :title="`route('${node.data.value}')`">
                                        route('{{ node.data.value }}')
                                    </p>
                            </Tag>
                        </div>

                        <Tag
                            v-else-if="node.data.type === 'route:dynamic'"
                            severity="warn"
                            rounded
                            class="mx-auto max-w-40 truncate text-ellipsis overflow-hidden"
                            icon="pi pi-code">
                            Dinámica
                        </Tag>
                        
                        <Tag
                            v-else-if="node.data.type === 'route:static' && node.data.value"
                            severity="secondary"
                            rounded
                            class="mx-auto max-w-40 truncate text-ellipsis overflow-hidden"
                            icon="pi pi-link">
                            {{ node.data.value }}
                        </Tag>
                    </template>
                </Column>
                <Column field="modules" header="Rutas">
                    <template #body="{ node }">
                        <div class="flex flex-col gap-2">
                            <div v-if="node.data.modules" v-for="module in node.data.modules">
                                <div v-if="module.menubar_item_module" v-for="route in module.menubar_item_module.routes">{{ route }}</div>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Acciones">
                    <template #body="{ node }">
                        <Button icon="pi pi-pencil" @click="editItem(node.data)" class="p-button-text p-button-sm" />
                        <Button icon="pi pi-trash" @click="deleteItem(node.data)"
                            class="p-button-text p-button-sm p-button-danger" />
                    </template>
                </Column>
            </TreeTable>

            <MenubarItemForm v-if="showForm" :modules="modules" :menubar-items="menubarItems" :item="selectedItem"
                @close="showForm = false" />
        </template>
    </AppLayout>
</template>