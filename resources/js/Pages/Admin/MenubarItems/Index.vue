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
const dismissedId = ref(null);
watch([() => page.props.menubarSeleccionado, () => page.props.modoMenu, () => readonly.value], () => {
    if (dismissedId.value && page.props.menubarSeleccionado?.id === dismissedId.value) return;
    if (!page.props.menubarSeleccionado) dismissedId.value = null;
    if (showForm.value && selectedItem.value?.id === page.props.menubarSeleccionado?.id && !readonly.value) return;
    selectedItem.value = page.props.menubarSeleccionado ?? null; showForm.value = Boolean(selectedItem.value); formRevision.value++;
}, {immediate:true});

const createItem = () => {
    if (!can('create-menubar-items')) return;
    dismissedId.value = page.props.menubarSeleccionado?.id ?? null;
    selectedItem.value = null;
    showForm.value = true;
};
const editItem = (item) => {
    if (!can('update-menubar-items')) return;
    dismissedId.value = null;
    serverList.visit({selected_item_id:item.id,modo:'edicion',page:page.props.listado?.current_page ?? 1});
};
const viewItem = item => { dismissedId.value = null; serverList.visit({selected_item_id:item.id,modo:'consulta',page:page.props.listado?.current_page ?? 1}); };
const closeForm = () => { dismissedId.value = selectedItem.value?.id ?? null; showForm.value = false; selectedItem.value = null; serverList.visit({selected_item_id:null,modo:null,page:page.props.listado?.current_page ?? 1}); };
const deleteItem = (item) => {
    if (!can('delete-menubar-items')) return;
    if (confirm("¿Seguro que deseas eliminar este elemento?")) {
        router.delete(route("admin.menubar-items.destroy", item.id), {
            only: ["menubarCatalogo", "menubarItems", "menubarSeleccionado", "modoMenu", "listado", "auth"],
        });
    }
};
</script>

<template>
    <AppLayout title="Configuración de barra de menú por módulos">
        <template #card-header>
            <PageHeader eyebrow="Administración · Navegación" title="Menú contextual por módulos" description="Organiza grupos y acciones, y elige en qué pantallas aparecen. Configuración compartida para toda la instalación.">
                <template #actions><Button icon="pi pi-arrow-left" label="Volver" severity="secondary" as="a" :href="route('admin.dashboard')" /></template>
            </PageHeader>
        </template>

        <template #card-content>
            <div class="p-4"><InputText v-model="filters.global.value" aria-label="Buscar acciones de menú" placeholder="Buscar acciones de menú" /><ListadoPaginador /></div>
            <Message v-if="Object.keys(page.props.errors ?? {}).length && !showForm" severity="error" role="alert" class="mb-4"><ul><li v-for="(message, field) in page.props.errors" :key="field">{{ message }}</li></ul></Message>
            <div class="flex justify-end pb-8">
                <Button v-if="can('create-menubar-items')" label="Agregar acción o grupo" icon="pi pi-plus" class="mt-3" @click="createItem" />
            </div>

            <Message v-if="!items.length" severity="info" class="mb-4">No hay elementos con estos filtros. Puedes cambiar la búsqueda o agregar un grupo o acción si tienes permiso.</Message>
            <TreeTable :value="items" lazy :rows="10" :loading="loading" responsiveLayout="scroll">
                <Column field="label" header="Etiqueta" expander>
                    <template #body="{ node }">
                        <span :class="node.data.icon"></span>
                        <span class="ml-2">{{ node.data.label }}</span>
                    </template>
                </Column>
                <Column field="module_id" header="Ámbito" :pt="{ columnheadercontent: { class: 'justify-center' } }">
                    <template #body="{ node }">
                        <div class="w-full flex flex-col items-center gap-2">
                            <Tag
                                v-if="node.data.modules"
                                v-for="module in node.data.modules"
                                :key="module.id"
                                severity="primary"
                                rounded="">
                                {{ page.props.catalogoMenu?.acciones.find(action => action.module === module.name)?.group ?? module.name }}
                            </Tag>
                        </div>
                    </template>
                </Column>
                <Column header="Tipo">
                    <template #body="{ node }"><Tag :severity="node.data.type === 'menu' ? 'info' : 'secondary'">{{ node.data.type === "menu" ? "Grupo" : node.data.type === "route:name" ? "Acción" : "Avanzado" }}</Tag></template>
                </Column>
                <Column header="Dónde aparece">
                    <template #body="{ node }">
                        <div class="flex flex-col gap-1 text-sm">
                            <span v-for="screen in node.data.modules?.flatMap(module => module.menubar_item_module?.routes ?? [])" :key="screen">{{ page.props.catalogoMenu?.pantallas.find(option => option.key === screen)?.label ?? "Pantalla guardada · consulta avanzada" }}</span>
                            <span v-if="!node.data.modules?.some(module => module.menubar_item_module?.routes?.length)" class="text-surface-500">{{ node.data.type === "menu" ? "Según sus acciones" : "Sin pantallas asignadas" }}</span>
                        </div>
                    </template>
                </Column>
                <Column field="sort_order" header="Orden" />
                <Column header="Acciones">
                    <template #body="{ node }">
                        <Button :aria-label="`Ver ${node.data.label}`" icon="pi pi-eye" @click="viewItem(node.data)" text />
                        <Button v-if="can('update-menubar-items')" :aria-label="`Editar ${node.data.label}`" icon="pi pi-pencil" @click="editItem(node.data)" class="p-button-text p-button-sm" />
                        <Button v-if="can('delete-menubar-items')" :aria-label="`Eliminar ${node.data.label}`" icon="pi pi-trash" @click="deleteItem(node.data)"
                            class="p-button-text p-button-sm p-button-danger" />
                    </template>
                </Column>
            </TreeTable>

            <MenubarItemForm v-if="showForm" :key="`${selectedItem?.id ?? 'nuevo'}:${readonly}:${formRevision}`" :readonly="readonly" :catalogo="page.props.catalogoMenu" :modules="modules" :menubar-items="menubarItems" :item="selectedItem"
                @close="closeForm" />
        </template>
    </AppLayout>
</template>
