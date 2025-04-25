<script setup>
import { ref, onMounted, reactive } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import { router } from "@inertiajs/vue3";

// Props para recibir los datos del controlador
const props = defineProps({
    clientes: {
        type: Array,
        default: () => [],
    },
});

const toast = useToast();
const clientes = ref(props.clientes);
const loading = ref(false);
const deleteDialog = ref(false);
const importDialog = ref(false);
const clienteSeleccionado = ref(null);

// Inicialización de filtros
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    id: { value: null, matchMode: FilterMatchMode.EQUALS },
    nombre_completo: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    telefono: { value: null, matchMode: FilterMatchMode.CONTAINS },
    "pais_nacimiento.nombre_es": {
        value: null,
        matchMode: FilterMatchMode.CONTAINS,
    },
    sexo: { value: null, matchMode: FilterMatchMode.EQUALS },
    "datos_fiscales.rfc": { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const loadClientes = async () => {
    loading.value = true;
    try {
        router.reload({
            only: ["clientes"],
        });
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los clientes",
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
};

const navigateToCreate = () => {
    router.visit(route("clientes.create"));
};

const editCliente = (id) => {
    router.visit(`/clientes/${id}/edit`);
};

const viewCliente = (id) => {
    router.visit(`/clientes/${id}`);
};

const confirmDelete = (cliente) => {
    clienteSeleccionado.value = cliente;
    deleteDialog.value = true;
};

const deleteCliente = async () => {
    try {
        await fetch(`/api/clientes/${clienteSeleccionado.value.id}`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
        });

        toast.add({
            severity: "success",
            summary: "Éxito",
            detail: "Cliente eliminado correctamente",
            life: 3000,
        });
        loadClientes();
        deleteDialog.value = false;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo eliminar el cliente",
            life: 3000,
        });
    }
};

const showImportDialog = () => {
    importDialog.value = true;
};

const onImportSuccess = () => {
    toast.add({
        severity: "success",
        summary: "Éxito",
        detail: "Clientes importados correctamente",
        life: 3000,
    });
    importDialog.value = false;
    loadClientes();
};

const onImportError = () => {
    toast.add({
        severity: "error",
        summary: "Error",
        detail: "No se pudieron importar los clientes",
        life: 3000,
    });
};

const exportData = async () => {
    try {
        const response = await fetch("/api/clientes/exportar", {
            method: "GET",
        });

        if (response.ok) {
            // Crear un enlace para descargar el archivo
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `clientes-${new Date().toISOString().split("T")[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();

            toast.add({
                severity: "success",
                summary: "Éxito",
                detail: "Archivo de clientes exportado correctamente",
                life: 3000,
            });
        } else {
            throw new Error("Error al exportar clientes");
        }
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron exportar los clientes",
            life: 3000,
        });
    }
};
</script>

<template>
    <div class="card">
        <div class="flex flex-wrap gap-4 justify-content-between align-items-center mb-4">
            <h1 class="text-3xl font-bold">Gestión de Clientes</h1>
            <div class="w-full flex gap-2">
                <Button label="Nuevo Cliente" icon="pi pi-plus" class="p-button-success" @click="navigateToCreate" />
                <Button label="Importar" icon="pi pi-upload" class="p-button-info" @click="showImportDialog" />
                <Button label="Exportar" icon="pi pi-download" class="p-button-help" @click="exportData" />
            </div>
        </div>

        <DataTable :value="clientes" v-model:filters="filters" filter-display="row" :global-filter-fields="[
          'id', 'nombre_completo', 'email', 'telefono', 
          'pais_nacimiento.nombre_es', 'sexo', 'datos_fiscales.rfc'
        ]" :paginator="true" :rows="10" :loading="loading"
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            @rows-per-page-options="[10, 25, 50]"
            current-page-report-template="Mostrando {first} a {last} de {totalRecords} clientes"
            responsive-layout="scroll" striped-rows :row-hover="true" class="p-datatable-sm">
            <template #header>
                <div class="flex justify-end mb-4">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search mr-2" />
                        <InputText v-model="filters['global'].value" placeholder="Buscar en todos los campos" />
                    </span>
                </div>
            </template>

            <Column field="id" header="ID" sortable>
                <template #filter="{ filterModel, filterCallback }">
                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter"
                        placeholder="Buscar por ID" />
                </template>
            </Column>

            <Column field="nombre_completo" header="Nombre" sortable>
                <template #body="{ data }">
                    {{ data.nombre_completo }}
                </template>
                <template #filter="{ filterModel, filterCallback }">
                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter"
                        placeholder="Buscar por nombre" />
                </template>
            </Column>

            <Column field="email" header="Email" sortable>
                <template #filter="{ filterModel, filterCallback }">
                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter"
                        placeholder="Buscar por email" />
                </template>
            </Column>

            <Column field="telefono" header="Teléfono" sortable>
                <template #body="{ data }">
                    {{ data.telefono_codigo_pais }} {{ data.telefono }}
                </template>
                <template #filter="{ filterModel, filterCallback }">
                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter"
                        placeholder="Buscar por teléfono" />
                </template>
            </Column>

            <Column field="pais_nacimiento.nombre_es" header="País" sortable>
                <template #body="{ data }">
                    <div class="flex align-items-center gap-2">
                        <span>{{ data.pais_nacimiento?.emoji }}</span>
                        <span>{{ data.pais_nacimiento?.nombre_es }}</span>
                    </div>
                </template>
                <template #filter="{ filterModel, filterCallback }">
                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter"
                        placeholder="Buscar por país" />
                </template>
            </Column>

            <Column field="datos_fiscales.rfc" header="RFC" sortable>
                <template #filter="{ filterModel, filterCallback }">
                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter"
                        placeholder="Buscar por RFC" />
                </template>
            </Column>

            <Column field="sexo" header="Sexo" sortable>
                <template #body="{ data }">
                    <Tag :severity="data.sexo === 'masculino' ? 'info' : 'success'">
                        {{ data.sexo === 'masculino' ? 'Masculino' : 'Femenino' }}
                    </Tag>
                </template>
                <template #filter="{ filterModel, filterCallback }">
                    <Dropdown v-model="filterModel.value" @change="filterCallback()"
                        :options="['masculino', 'femenino']" placeholder="Seleccionar sexo" class="p-column-filter"
                        :showClear="true" />
                </template>
            </Column>

            <Column header="Acciones" :exportable="false">
                <template #body="{ data }">
                    <div class="flex gap-2">
                        <Button icon="pi pi-eye" class="p-button-rounded p-button-info p-button-sm"
                            @click="viewCliente(data.id)" />
                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-success p-button-sm"
                            @click="editCliente(data.id)" />
                        <Button icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-sm"
                            @click="confirmDelete(data)" />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Diálogo de confirmación para eliminar -->
        <Dialog v-model:visible="deleteDialog" header="Confirmar" :style="{ width: '450px' }" :modal="true">
            <div class="confirmation-content">
                <i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem" />
                <span v-if="clienteSeleccionado">¿Está seguro que desea eliminar a <b>{{
                        clienteSeleccionado.nombre_completo }}</b>?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" class="p-button-text" @click="deleteDialog = false" />
                <Button label="Sí" icon="pi pi-check" class="p-button-danger" @click="deleteCliente" />
            </template>
        </Dialog>

        <!-- Diálogo para importación -->
        <Dialog v-model:visible="importDialog" header="Importar Clientes" :style="{ width: '30vw' }" :modal="true">
            <div class="flex flex-column gap-3">
                <div class="flex align-items-center gap-2">
                    <i class="pi pi-info-circle text-blue-500" style="font-size: 1.5rem"></i>
                    <p>Sube un archivo CSV o Excel con los datos de los clientes.</p>
                </div>
                <FileUpload mode="basic" name="clientes" url="/api/clientes/importar" accept=".csv,.xlsx" :auto="true"
                    choose-label="Seleccionar Archivo" @upload="onImportSuccess" @error="onImportError" />
            </div>
            <template #footer>
                <Button label="Cerrar" icon="pi pi-times" class="p-button-text" @click="importDialog = false" />
                <a href="/api/clientes/plantilla" target="_blank">
                    <Button label="Descargar Plantilla" icon="pi pi-file" class="p-button-info" />
                </a>
            </template>
        </Dialog>

        <Toast />
    </div>
</template>