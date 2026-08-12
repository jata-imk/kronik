<script setup>
import { router } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import { useToast } from "primevue/usetoast";
import { computed, ref } from "vue";
import TruncatedText from "@/Components/DataTable/TruncatedText.vue";

// Props para recibir los datos del controlador
const props = defineProps({
    clientes: {
        type: Array,
        default: () => [],
    },
    can: {
        type: Object,
        default: () => ({}),
    },
    filters: { type: Object, default: () => ({ scope: "current" }) },
    sucursales: { type: Array, default: () => [] },
});

const toast = useToast();
const clientes = computed(() => props.clientes);
const loading = ref(false);
const deleteProcessing = ref(false);
const deleteDialog = ref(false);
const importDialog = ref(false);
const clienteSeleccionado = ref(null);
const transferDialog = ref(false);
const targetSucursalId = ref(null);
const transferProcessing = ref(false);

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
    "sucursal.nombre": { value: null, matchMode: FilterMatchMode.CONTAINS },
});

/**
 * Copia el texto proporcionado al portapapeles.
 * @param {string} text - El texto que se copiará al portapapeles.
 */
function copyToClipboard(text) {
    if (!navigator.clipboard) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "El portapapeles no está disponible en este navegador.",
            life: 3000,
        });
        return;
    }

    navigator.clipboard
        .writeText(text)
        .then(() => {
            toast.add({
                severity: "success",
                summary: "Éxito",
                detail: "Texto copiado al portapapeles",
                life: 3000,
            });
        })
        .catch((err) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: `No se pudo copiar el texto: ${err.message}`,
                life: 3000,
            });
        });
}

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

const viewExpediente = (id) => {
    router.visit(route("clientes.expediente.show", id));
};

const confirmDelete = (cliente) => {
    clienteSeleccionado.value = cliente;
    deleteDialog.value = true;
};

const changeScope = (scope) => {
    router.get(
        route("clientes.index"),
        { scope },
        { preserveState: true, replace: true },
    );
};

const openTransfer = (cliente) => {
    clienteSeleccionado.value = cliente;
    targetSucursalId.value = null;
    transferDialog.value = true;
};

const transferCliente = () => {
    transferProcessing.value = true;
    router.patch(
        route("clientes.sucursal.transfer", clienteSeleccionado.value.id),
        { sucursal_id: targetSucursalId.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                transferDialog.value = false;
                toast.add({
                    severity: "success",
                    summary: "Cliente trasladado",
                    life: 3000,
                });
            },
            onFinish: () => {
                transferProcessing.value = false;
            },
        },
    );
};

const deleteCliente = () => {
    deleteProcessing.value = true;
    router.delete(route("clientes.destroy", clienteSeleccionado.value.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Éxito",
                detail: "Cliente eliminado correctamente",
                life: 3000,
            });
            deleteDialog.value = false;
        },
        onError: () => {
            toast.add({
                severity: "error",
                summary: "No se pudo eliminar el cliente",
                detail: "Verifique sus permisos e intente nuevamente.",
                life: 4000,
            });
        },
        onFinish: () => {
            deleteProcessing.value = false;
        },
    });
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
    <div class="card !p-0">
        <div class="flex flex-wrap gap-4 justify-content-between align-items-center mb-4">
            <div class="w-full flex gap-2">
                <Button v-if="can.create" label="Nuevo Cliente" icon="pi pi-plus" class="p-button-success" @click="navigateToCreate" />
                <Button v-if="can.create" label="Importar" icon="pi pi-upload" class="p-button-info" @click="showImportDialog" />
                <Button label="Exportar" icon="pi pi-download" class="p-button-help" @click="exportData" />
                <SelectButton
                    :model-value="filters.scope"
                    :options="[{ label: 'Sucursal actual', value: 'current' }, { label: 'Todas', value: 'all' }]"
                    option-label="label"
                    option-value="value"
                    @update:model-value="changeScope"
                />
            </div>
        </div>

        <DataTable
            :value="clientes"
            v-model:filters="filters"
            filter-display="row"
            :global-filter-fields="[
                'id', 'nombre_completo', 'email', 'telefono', 
                'pais_nacimiento.nombre_es', 'sexo', 'datos_fiscales.rfc', 'sucursal.nombre'
            ]"
            :paginator="true"
            :rows="10"
            :loading="loading"
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            @rows-per-page-options="[10, 25, 50]"
            current-page-report-template="Mostrando {first} a {last} de {totalRecords} clientes"
            responsive-layout="scroll"
            :scrollable="true"
            scroll-height="73vh"
            striped-rows :row-hover="true" class="p-datatable-sm">
            <template #header>
                <div class="flex justify-end mb-4">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search mr-2" />
                        <InputText v-model="filters['global'].value" placeholder="Buscar en todos los campos" />
                    </span>
                </div>
            </template>

            <Column field="id" header="ID" :show-filter-menu="false" :pt="{
                columnheadercontent: { class: 'justify-center' },
                bodycell: { class: '!text-center' },
                filterElementContainer: { class: '!w-[7vw] !max-w-[100px]' },
            }" sortable>
                <template #filter="{ filterModel, filterCallback }">
                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter w-full" />
                </template>
            </Column>

            <Column field="nombre_completo" header="Nombre" sortable>
                <template #body="{ data }">
                    <div class="flex max-w-80 min-w-0 items-center gap-2 whitespace-nowrap">
                        <TruncatedText class="min-w-0 flex-1" :value="data.nombre_completo" max-width="18rem" />
                        <span
                            v-if="data.relaciones_count"
                            class="relationship-count"
                            :aria-label="`${data.relaciones_count} relación${data.relaciones_count === 1 ? '' : 'es'}`"
                        >
                            <i class="pi pi-link" aria-hidden="true" />
                            <span>{{ data.relaciones_count }}</span>
                        </span>
                    </div>
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

                <template #body="{ data }">
                    <Chip class="flex items-center !px-2 !py-1 !bg-primary-100 !text-gray-900 dark:!bg-primary-800 dark:!text-gray-200">
                        <Button icon="pi pi-clipboard" @click="copyToClipboard(data.email)" size="small" rounded />
                        <p class="font-semibold text-sm max-w-[250px] truncate">{{ data.email }}</p>
                    </Chip>
                </template>
            </Column>

            <Column field="telefono" header="Teléfono" sortable>
                <template #body="{ data }">
                    <Tag v-if="data.telefono_codigo_pais" severity="success" rounded class="font-mono" >
                        +{{ String(data.telefono_codigo_pais).replace(/^\+/, "") }}
                    </Tag>
                    <span class="ml-2 font-semibold font-mono">{{ data.telefono.replaceAll(' ', '') }}</span>
                </template>
                <template #filter="{ filterModel, filterCallback }">
                    <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter"
                        placeholder="Buscar por teléfono" />
                </template>
            </Column>

            <Column field="pais_nacimiento.nombre_es" header="País" sortable>
                <template #body="{ data }">
                    <div class="flex max-w-56 min-w-0 items-center gap-2 whitespace-nowrap">
                        <span v-twemoji class="shrink-0">{{ data.pais_nacimiento?.emoji }}</span>
                        <TruncatedText class="min-w-0" :value="data.pais_nacimiento?.nombre_es" max-width="11rem" />
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

            <Column field="sucursal.nombre" header="Sucursal" sortable>
                <template #body="{ data }">
                    <Tag
                        :value="data.sucursal?.nombre ?? 'Sin asignar'"
                        severity="secondary"
                        class="max-w-40 whitespace-nowrap"
                        :pt="{ label: { class: 'block truncate' } }"
                    />
                </template>
                <template #filter="{ filterModel, filterCallback }">
                    <InputText v-model="filterModel.value" type="text" class="p-column-filter" placeholder="Buscar por sucursal" @input="filterCallback()" />
                </template>
            </Column>

            <Column field="sexo" header="Sexo" sortable>
                <template #body="{ data }">
                    <Tag :severity="data.sexo === 'masculino' ? 'info' : 'success'">
                        {{ data.sexo === 'masculino' ? 'Masculino' : 'Femenino' }}
                    </Tag>
                </template>
                <template #filter="{ filterModel, filterCallback }">
                    <Select v-model="filterModel.value" @change="filterCallback()"
                        :options="['masculino', 'femenino']" placeholder="Seleccionar sexo" class="p-column-filter"
                        :showClear="true" />
                </template>
            </Column>

            <Column header="Acciones" :exportable="false" :frozen="true" align-frozen="right">
                <template #body="{ data }">
                    <div class="flex gap-2">
                        <Button :aria-label="`Abrir expediente de ${data.nombre_completo}`" icon="pi pi-folder-open" class="p-button-rounded p-button-warning p-button-sm"
                            @click="viewExpediente(data.id)" />
                        <Button :aria-label="`Ver ${data.nombre_completo}`" icon="pi pi-eye" class="p-button-rounded p-button-info p-button-sm"
                            @click="viewCliente(data.id)" />
                        <Button v-if="data.can_update" :aria-label="`Editar ${data.nombre_completo}`" icon="pi pi-pencil" class="p-button-rounded p-button-success p-button-sm"
                            @click="editCliente(data.id)" />
                        <Button v-if="data.can_transfer" :aria-label="`Trasladar ${data.nombre_completo}`" icon="pi pi-arrow-right-arrow-left" text
                            @click="openTransfer(data)" />
                        <Button v-if="data.can_delete" :aria-label="`Eliminar ${data.nombre_completo}`" icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-sm"
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
                <Button label="Sí" icon="pi pi-check" class="p-button-danger" :loading="deleteProcessing" :disabled="deleteProcessing" @click="deleteCliente" />
            </template>
        </Dialog>

        <Dialog v-model:visible="transferDialog" header="Trasladar cliente" :style="{ width: '420px' }" modal>
            <div class="flex flex-col gap-3">
                <p>Selecciona la nueva sucursal responsable de <strong>{{ clienteSeleccionado?.nombre_completo }}</strong>.</p>
                <Select v-model="targetSucursalId" :options="sucursales" option-label="nombre" option-value="id" placeholder="Sucursal de destino" fluid />
            </div>
            <template #footer>
                <Button label="Cancelar" text @click="transferDialog = false" />
                <Button label="Trasladar" :disabled="!targetSucursalId" :loading="transferProcessing" @click="transferCliente" />
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
    </div>
</template>

<style scoped>
.relationship-count {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 0.3rem;
    min-width: 2rem;
    padding: 0.2rem 0.45rem;
    color: var(--p-primary-700);
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    background: var(--p-primary-50);
    border: 1px solid var(--p-primary-200);
    border-radius: 999px;
}

.relationship-count .pi {
    font-size: 0.7rem;
}

:global(.app-dark) .relationship-count {
    color: var(--p-primary-200);
    background: color-mix(in srgb, var(--p-primary-900) 55%, transparent);
    border-color: var(--p-primary-700);
}
</style>
