<script setup>
import CodigoPostalAutocomplete from "@/Components/CodigoPostalAutocomplete.vue";
import IntlTelInput from "@/Components/IntlTelInput.vue";
import { useDireccionCodigoPostal } from "@/Composables/useDireccionCodigoPostal";
import { useTelefonoInternacional } from "@/Composables/useTelefonoInternacional";
import { router, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import ConfirmDialog from "primevue/confirmdialog";
import { FilterMatchMode } from "@primevue/core/api";
import { ref } from "vue";

const props = defineProps({
    sucursales: Array,
});

const page = usePage();
const can = (permission) => page.props.auth.is_super_admin || page.props.auth.permissions?.[permission] === true;
const toast = useToast();
const confirm = useConfirm();
const visible = ref(false);
const selectedSucursal = ref(null);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    nombre: { value: null, matchMode: FilterMatchMode.CONTAINS },
    clave: { value: null, matchMode: FilterMatchMode.CONTAINS },
    activa: { value: true, matchMode: FilterMatchMode.EQUALS },
});

const emptySucursal = () => ({
    nombre: "",
    clave: "",
    domicilio: {
        calle: "",
        numero_exterior: "",
        numero_interior: "",
        colonia: "",
        municipio: "",
        estado: "",
        codigo_postal: "",
        pais_id: null,
        pais_codigo_iso: "",
        codigo_postal_id: null,
        division_admin_uno_id: null,
        division_admin_dos_id: null,
        division_admin_tres_id: null,
        pais: "",
    },
    telefono: "",
    email: "",
    horario: {
        lunes_viernes: "",
        sabado: "",
        domingo: "",
    },
    prefijo_folio: "",
    consecutivo_solicitud: 1,
    consecutivo_contrato: 1,
    consecutivo_credito: 1,
    consecutivo_recibo: 1,
    activa: true,
});

const form = useForm(emptySucursal());

const {
    localidades,
    aplicarCodigoPostal,
    limpiarUbicacionPostal,
    onLocalidadChange,
} = useDireccionCodigoPostal(() => form.domicilio);

const {
    telefonoInternacional,
    sincronizarDesdeFormulario: sincronizarTelefono,
    onChangeNumber: onSucursalTelefonoChange,
} = useTelefonoInternacional(form, { e164Key: "telefono" });

const openCreate = () => {
    selectedSucursal.value = null;
    localidades.value = [];
    form.defaults(emptySucursal());
    form.reset();
    sincronizarTelefono();
    form.clearErrors();
    visible.value = true;
};

const openEdit = (sucursal) => {
    selectedSucursal.value = sucursal;
    localidades.value =
        sucursal.domicilio?.codigo_postal_id &&
        sucursal.domicilio?.division_admin_tres_id
            ? [
                  {
                      codigoPostalId: sucursal.domicilio.codigo_postal_id,
                      divisionAdminTresId:
                          sucursal.domicilio.division_admin_tres_id,
                      nombre:
                          sucursal.domicilio.colonia ?? "Localidad guardada",
                      tipo: null,
                  },
              ]
            : [];
    form.defaults({
        ...emptySucursal(),
        ...sucursal,
        domicilio: {
            ...emptySucursal().domicilio,
            ...(sucursal.domicilio ?? {}),
        },
        horario: { ...emptySucursal().horario, ...(sucursal.horario ?? {}) },
    });
    form.reset();
    sincronizarTelefono();
    form.clearErrors();
    visible.value = true;
};

const closeDialog = () => {
    form.clearErrors();
    visible.value = false;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        only: ["sucursales"],
        onSuccess: () => {
            visible.value = false;
            toast.add({
                severity: "success",
                summary: selectedSucursal.value
                    ? "Sucursal actualizada"
                    : "Sucursal creada",
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: "error",
                summary: "No se pudo guardar la sucursal",
                detail: "Revise los campos marcados.",
                life: 5000,
            });
        },
    };

    if (selectedSucursal.value) {
        form.put(
            route("admin.sucursales.update", selectedSucursal.value.id),
            options,
        );
    } else {
        form.post(route("admin.sucursales.store"), options);
    }
};

const deactivate = (sucursal) => {
    confirm.require({
        header: "Desactivar sucursal",
        message: `¿Desactivar ${sucursal.nombre}? Su historial y folios se conservarán.`,
        icon: "pi pi-exclamation-triangle",
        rejectLabel: "Cancelar",
        acceptLabel: "Desactivar",
        acceptClass: "p-button-danger",
        accept: () => router.delete(route("admin.sucursales.destroy", sucursal.id), {
            preserveScroll: true,
            only: ["sucursales"],
            onSuccess: () =>
                toast.add({
                    severity: "success",
                    summary: "Sucursal desactivada",
                    life: 3000,
                }),
            onError: (errors) =>
                toast.add({
                    severity: "error",
                    summary: "No se puede desactivar",
                    detail: errors.sucursal ?? "Reasigna primero sus dependencias activas.",
                    life: 6000,
                }),
        }),
    });
};

const formatAddress = (domicilio) => {
    if (!domicilio) return "";
    return [
        domicilio.calle,
        domicilio.numero_exterior,
        domicilio.colonia,
        domicilio.municipio,
        domicilio.estado,
        domicilio.codigo_postal,
    ]
        .filter(Boolean)
        .join(", ");
};
</script>

<template>
    <AppLayout title="Sucursales">
        <template #card-header>
            <div class="flex items-center p-4">
                <Button icon="pi pi-arrow-left" as="a" :href="route('admin.dashboard')" />
                <h2 class="text-2xl font-bold ml-4">Sucursales</h2>
            </div>
        </template>

        <template #card-content>
            <ConfirmDialog />
            <div class="p-4">
                <div class="flex justify-end pb-6">
                    <Button v-if="can('create-sucursales')" label="Crear sucursal" icon="pi pi-plus" @click="openCreate" />
                </div>

                <DataTable v-model:filters="filters" :value="props.sucursales" :global-filter-fields="['nombre', 'clave', 'email', 'telefono', 'prefijo_folio']" paginator :rows="10" striped-rows scrollable responsive-layout="scroll">
                    <template #header><div class="flex flex-wrap items-center justify-between gap-3"><span class="text-sm text-surface-500">Se muestran activas por defecto; usa el filtro Estado para consultar el archivo.</span><IconField><InputIcon class="pi pi-search" /><InputText v-model="filters.global.value" placeholder="Buscar sucursales" /></IconField></div></template>
                    <Column field="nombre" header="Sucursal" sortable>
                        <template #body="{ data }"><div class="flex items-center gap-3"><Avatar icon="pi pi-building" shape="circle" /><div><div class="font-semibold">{{ data.nombre }}</div><div class="mt-1 flex gap-1"><Tag :value="data.clave" severity="contrast" /><Tag :value="data.activa ? 'Activa' : 'Inactiva'" :severity="data.activa ? 'success' : 'secondary'" /></div></div></div></template>
                        <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Nombre" @input="filterCallback()" /></template>
                    </Column>
                    <Column field="clave" header="Clave" sortable><template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Clave" @input="filterCallback()" /></template></Column>
                    <Column header="Ubicación y contacto">
                        <template #body="{ data }">
                            <div class="max-w-80"><div class="truncate" :title="formatAddress(data.domicilio)"><i class="pi pi-map-marker mr-1 text-surface-500" />{{ formatAddress(data.domicilio) || "Sin domicilio" }}</div><small class="block truncate text-surface-500">{{ data.email || data.telefono || "Sin contacto" }}</small></div>
                        </template>
                    </Column>
                    <Column header="Operación"><template #body="{ data }"><div class="flex flex-wrap gap-1"><Chip :label="`${data.users_count} usuarios`" icon="pi pi-users" /><Chip :label="`${data.clientes_count} clientes`" icon="pi pi-id-card" /><Chip :label="`Prefijo ${data.prefijo_folio || '—'}`" /></div></template></Column>
                    <Column header="Folios">
                        <template #body="{ data }">
                            <div class="grid grid-cols-2 gap-1 text-xs"><Tag :value="`SOL ${data.consecutivo_solicitud}`" severity="secondary" /><Tag :value="`CON ${data.consecutivo_contrato}`" severity="secondary" /><Tag :value="`CRE ${data.consecutivo_credito}`" severity="secondary" /><Tag :value="`REC ${data.consecutivo_recibo}`" severity="secondary" /></div>
                        </template>
                    </Column>
                    <Column field="activa" header="Estado">
                        <template #body="{ data }">
                            <Tag :severity="data.activa ? 'success' : 'secondary'">
                                {{ data.activa ? "Activa" : "Inactiva" }}
                            </Tag>
                        </template>
                        <template #filter="{ filterModel, filterCallback }"><Select v-model="filterModel.value" :options="[{ label: 'Activas', value: true }, { label: 'Inactivas', value: false }]" option-label="label" option-value="value" placeholder="Todas" show-clear @change="filterCallback()" /></template>
                    </Column>
                    <Column header="Acciones" frozen align-frozen="right">
                        <template #body="{ data }">
                            <Button v-if="can('update-sucursales')" :aria-label="`Editar ${data.nombre}`" icon="pi pi-pencil" text size="small" @click="openEdit(data)" />
                            <Button v-if="can('delete-sucursales') && data.activa" :aria-label="`Desactivar ${data.nombre}`" icon="pi pi-ban" text size="small" severity="danger" @click="deactivate(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>

            <Dialog v-model:visible="visible" :header="selectedSucursal ? 'Editar sucursal' : 'Crear sucursal'" modal :style="{ width: '720px' }">
                <form class="flex flex-col gap-5" @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Nombre *</label>
                            <InputText v-model="form.nombre" fluid />
                            <small v-if="form.errors.nombre" class="text-red-500">{{ form.errors.nombre }}</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Clave *</label>
                            <InputText v-model="form.clave" fluid />
                            <small v-if="form.errors.clave" class="text-red-500">{{ form.errors.clave }}</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Telefono</label>
                            <IntlTelInput
                                id="telefono"
                                v-model="telefonoInternacional"
                                emit-e164
                                @change-number="onSucursalTelefonoChange"
                                :intl-tel-input-options="{ initialCountry: 'mx' }"
                                :invalid="!!form.errors.telefono"
                                fluid
                            />
                            <small v-if="form.errors.telefono" class="text-red-500">{{ form.errors.telefono }}</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <InputText v-model="form.email" fluid />
                            <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Prefijo de folio</label>
                            <InputText v-model="form.prefijo_folio" fluid placeholder="MTY, CDMX, MATRIZ" />
                        </div>
                        <div class="flex items-center gap-3 pt-6">
                            <Checkbox v-model="form.activa" input-id="sucursal-activa" binary />
                            <label for="sucursal-activa">Sucursal activa</label>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold mb-3">Domicilio</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Código postal</label>
                                <CodigoPostalAutocomplete
                                    v-model="form.domicilio.codigo_postal"
                                    input-id="codigo_postal"
                                    :invalid="!!form.errors['domicilio.codigo_postal']"
                                    @changed="limpiarUbicacionPostal"
                                    @confirmed="aplicarCodigoPostal"
                                />
                                <small v-if="form.errors['domicilio.codigo_postal']" class="text-red-500">{{ form.errors["domicilio.codigo_postal"] }}</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Colonia</label>
                                <Select
                                    v-if="localidades.length"
                                    v-model="form.domicilio.codigo_postal_id"
                                    :options="localidades"
                                    option-label="nombre"
                                    option-value="codigoPostalId"
                                    :invalid="!!(form.errors['domicilio.codigo_postal_id'] || form.errors['domicilio.division_admin_tres_id'])"
                                    fluid
                                    @change="onLocalidadChange"
                                />
                                <InputText
                                    v-else
                                    v-model="form.domicilio.colonia"
                                    :invalid="!!(form.errors['domicilio.codigo_postal_id'] || form.errors['domicilio.division_admin_tres_id'])"
                                    disabled
                                    fluid
                                />
                                <small
                                    v-if="form.errors['domicilio.codigo_postal_id'] || form.errors['domicilio.division_admin_tres_id']"
                                    class="text-red-500"
                                >
                                    {{ form.errors["domicilio.codigo_postal_id"] ?? form.errors["domicilio.division_admin_tres_id"] }}
                                </small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Municipio</label>
                                <InputText v-model="form.domicilio.municipio" disabled fluid />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Estado</label>
                                <InputText v-model="form.domicilio.estado" disabled fluid />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Calle</label>
                                <InputText v-model="form.domicilio.calle" :invalid="!!form.errors['domicilio.calle']" fluid />
                                <small v-if="form.errors['domicilio.calle']" class="text-red-500">{{ form.errors["domicilio.calle"] }}</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Numero exterior</label>
                                <InputText v-model="form.domicilio.numero_exterior" fluid />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Numero interior</label>
                                <InputText v-model="form.domicilio.numero_interior" fluid />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold mb-3">Horarios y folios</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Lunes a viernes</label>
                                <InputText v-model="form.horario.lunes_viernes" fluid placeholder="09:00-18:00" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Sabado</label>
                                <InputText v-model="form.horario.sabado" fluid placeholder="09:00-14:00" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Domingo</label>
                                <InputText v-model="form.horario.domingo" fluid placeholder="Cerrado" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Consecutivo solicitud</label>
                                <InputNumber v-model="form.consecutivo_solicitud" fluid :min="1" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Consecutivo contrato</label>
                                <InputNumber v-model="form.consecutivo_contrato" fluid :min="1" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Consecutivo credito</label>
                                <InputNumber v-model="form.consecutivo_credito" fluid :min="1" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Consecutivo recibo</label>
                                <InputNumber v-model="form.consecutivo_recibo" fluid :min="1" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-surface-200 dark:border-surface-700">
                        <Button label="Cancelar" severity="secondary" icon="pi pi-times" @click.prevent="closeDialog" />
                        <Button label="Guardar" icon="pi pi-check" type="submit" :loading="form.processing" />
                    </div>
                </form>
            </Dialog>
        </template>
    </AppLayout>
</template>
