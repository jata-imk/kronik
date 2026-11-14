<script setup>
import ListadoPaginador from "@/Components/ListadoPaginador.vue";
import { useListadoServidor } from "@/Composables/useListadoServidor";
import { computed, ref, watch } from "vue";
import { NodeService } from "@sakai-vue/service/NodeService";
import { router, usePage } from "@inertiajs/vue3";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import MenubarItemForm from "./Form.vue";

const page = usePage();
const filters = ref({ global: { value: null } });
const serverList = useListadoServidor(filters);
const can = permission => page.props.auth.is_super_admin || page.props.auth.permissions?.[permission] === true;

const items = ref([]);
const modules = computed(() => page.props.modules);
const menubarItems = ref(null);

watch(
    () => page.props.menubarCatalogo,
    () => {
        menubarItems.value = page.props.menubarCatalogo;
        items.value = NodeService.toTreeNodes(menubarItems.value);
    },
    {
        immediate: true,
    },
);

const loading = ref(false);
const selectedItem = ref(null);
const showForm = ref(false);
const readonly = computed(() => selectedItem.value ? page.props.modoMenu !== 'edicion' || !can('update-menubar-items') : !can('create-menubar-items'));
const formRevision = ref(0);
watch([() => page.props.menubarSeleccionado, () => page.props.modoMenu, () => readonly.value], () => {
    if (showForm.value && selectedItem.value?.id === page.props.menubarSeleccionado?.id && !readonly.value) return;
    selectedItem.value = page.props.menubarSeleccionado ?? null; showForm.value = Boolean(selectedItem.value); formRevision.value++;
}, {immediate:true});

const createItem = () => {
    if (!can('create-menubar-items')) return;
    selectedItem.value = null;
    showForm.value = true;
};
const editItem = (item) => {
    if (!can('update-menubar-items')) return;
    serverList.visit({selected_item_id:item.id,modo:'edicion',page:page.props.listado?.current_page ?? 1});
};
const viewItem = item => serverList.visit({selected_item_id:item.id,modo:'consulta',page:page.props.listado?.current_page ?? 1});
const closeForm = () => { showForm.value = false; serverList.visit({selected_item_id:null,modo:null,page:page.props.listado?.current_page ?? 1}); };
const deleteItem = (item) => {
    if (!can('delete-menubar-items')) return;
    if (confirm("¿Seguro que deseas eliminar este item?")) {
        router.delete(route("admin.menubar-items.destroy", item.id), {
            only: ["menubarCatalogo", "menubarItems", "menubarSeleccionado", "modoMenu", "listado", "auth"],
        });
    }
};
</script>

<template>
    <AppLayout title="Configuración de barra de menú por módulos">
        <template #card-header>
            <PageHeader eyebrow="Administración · Navegación" title="Menú contextual por módulos" description="Configura las acciones locales de cada módulo.">
                <template #actions><Button icon="pi pi-arrow-left" label="Volver" severity="secondary" as="a" :href="route('admin.dashboard')" /></template>
            </PageHeader>
        </template>

        <template #card-content>
            <div class="p-4"><InputText v-model="filters.global.value" aria-label="Buscar acciones de menú" placeholder="Buscar acciones de menú" /><ListadoPaginador /></div>
            <div class="flex justify-end pb-8">
                <Button v-if="can('create-menubar-items')" label="Crear nuevo" icon="pi pi-plus" class="mt-3" @click="createItem" />
            </div>

            <TreeTable :value="items" lazy :rows="10" :loading="loading" responsiveLayout="scroll">
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
                        <Button :aria-label="`Ver ${node.data.label}`" icon="pi pi-eye" @click="viewItem(node.data)" text />
                        <Button v-if="can('update-menubar-items')" :aria-label="`Editar ${node.data.label}`" icon="pi pi-pencil" @click="editItem(node.data)" class="p-button-text p-button-sm" />
                        <Button v-if="can('delete-menubar-items')" :aria-label="`Eliminar ${node.data.label}`" icon="pi pi-trash" @click="deleteItem(node.data)"
                            class="p-button-text p-button-sm p-button-danger" />
                    </template>
                </Column>
            </TreeTable>

            <MenubarItemForm v-if="showForm" :key="`${selectedItem?.id ?? 'nuevo'}:${readonly}:${formRevision}`" :readonly="readonly" :modules="modules" :menubar-items="menubarItems" :item="selectedItem"
                @close="closeForm" />
        </template>
    </AppLayout>
</template>
