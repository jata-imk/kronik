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
import { computed, ref } from "vue";
import TruncatedText from "@/Components/DataTable/TruncatedText.vue";

const props = defineProps({
    sucursales: Array,
});

const page = usePage();
const can = (permission) =>
    page.props.auth.is_super_admin ||
    page.props.auth.permissions?.[permission] === true;
const toast = useToast();
const confirm = useConfirm();
const visible = ref(false);
const selectedSucursal = ref(null);
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
const formatAddressLines = (domicilio) => {
    if (!domicilio) return ["Sin domicilio", "—", "—"];

    return [
        [domicilio.calle, domicilio.numero_exterior, domicilio.numero_interior]
            .filter(Boolean)
            .join(" ") || "Sin calle",
        [
            domicilio.colonia,
            domicilio.codigo_postal ? `CP ${domicilio.codigo_postal}` : null,
        ]
            .filter(Boolean)
            .join(" · ") || "Sin colonia ni CP",
        [domicilio.municipio, domicilio.estado].filter(Boolean).join(", ") ||
            "Sin municipio ni estado",
    ];
};
const sucursales = computed(() =>
    (props.sucursales ?? []).map((sucursal) => {
        const addressLines = formatAddressLines(sucursal.domicilio);

        return {
            ...sucursal,
            sucursal_search: `${sucursal.nombre} ${sucursal.telefono || ""} ${sucursal.email || ""}`,
            domicilio_display:
                formatAddress(sucursal.domicilio) || "Sin domicilio",
            domicilio_lines: addressLines,
            horario_semana_display:
                sucursal.horario?.lunes_viernes || "Sin horario entre semana",
            horario_sabado_display: sucursal.horario?.sabado || "—",
            horario_domingo_display: sucursal.horario?.domingo || "—",
            horario_search:
                Object.values(sucursal.horario ?? {}).join(" ") ||
                "Sin horario",
            summary_search: `${sucursal.users_count} usuarios ${sucursal.clientes_count} clientes`,
            estado_label: sucursal.activa ? "Activa" : "Inactiva",
        };
    }),
);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    sucursal_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
    domicilio_display: { value: null, matchMode: FilterMatchMode.CONTAINS },
    horario_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
    summary_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
    estado_label: { value: "Activa", matchMode: FilterMatchMode.EQUALS },
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
        accept: () =>
            router.delete(route("admin.sucursales.destroy", sucursal.id), {
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
                        detail:
                            errors.sucursal ??
                            "Reasigna primero sus dependencias activas.",
                        life: 6000,
                    }),
            }),
    });
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
            <div>
                <div class="flex justify-end pb-6">
                    <Button v-if="can('create-sucursales')" label="Crear sucursal" icon="pi pi-plus" @click="openCreate" />
                </div>

                <DataTable v-model:filters="filters" :value="sucursales" :global-filter-fields="['nombre', 'domicilio_display', 'email', 'telefono', 'horario_search', 'summary_search', 'estado_label']" filter-display="row" paginator :rows="10" striped-rows scrollable responsive-layout="scroll" :table-style="{ tableLayout: 'fixed', minWidth: '60rem' }">
                    <template #header><div class="flex flex-wrap items-center justify-between gap-3"><span class="text-sm text-surface-500">Se muestran activas por defecto; usa el filtro Estado para consultar el archivo.</span><div class="flex flex-wrap gap-2"><Select v-model="filters.estado_label.value" aria-label="Filtrar sucursales por estado" :options="['Activa', 'Inactiva']" placeholder="Todos los estados" show-clear class="w-44" /><IconField><InputIcon class="pi pi-search" /><InputText v-model="filters.global.value" placeholder="Buscar sucursales" /></IconField></div></div></template>
                    <Column field="sucursal_search" header="Sucursal" sortable :show-filter-menu="false" style="width: 26%">
                        <template #body="{ data }">
                            <div class="min-w-0 max-w-56">
                                <div class="flex min-w-0 items-center gap-2"><span class="size-2.5 shrink-0 rounded-full" :class="data.activa ? 'bg-green-500' : 'bg-surface-400'" :aria-label="`Sucursal ${data.estado_label.toLowerCase()}`" /><TruncatedText class="font-semibold" :value="data.nombre" max-width="12rem" /></div>
                                <TruncatedText class="text-sm text-surface-500" :value="data.telefono || 'Sin teléfono'" max-width="14rem" />
                                <TruncatedText class="text-sm text-surface-500" :value="data.email || 'Sin correo'" max-width="14rem" />
                            </div>
                        </template>
                        <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" class="w-full" placeholder="Nombre, teléfono o correo" @input="filterCallback()" /></template>
                    </Column>
                    <Column field="domicilio_display" header="Domicilio" :show-filter-menu="false" style="width: 25%"><template #body="{ data }"><div class="max-w-60"><TruncatedText v-for="line in data.domicilio_lines" :key="line" class="text-sm" :value="line" max-width="15rem" /></div></template><template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" class="w-full" placeholder="Domicilio" @input="filterCallback()" /></template></Column>
                    <Column field="horario_search" header="Horario" :show-filter-menu="false" style="width: 22%">
                        <template #body="{ data }"><div class="max-w-52 text-sm"><TruncatedText class="text-sm" :value="`L–V ${data.horario_semana_display}`" max-width="13rem" /><TruncatedText class="text-sm" :value="`Sáb ${data.horario_sabado_display}`" max-width="13rem" /><TruncatedText class="text-sm" :value="`Dom ${data.horario_domingo_display}`" max-width="13rem" /></div></template>
                        <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" class="w-full" placeholder="Horario" @input="filterCallback()" /></template>
                    </Column>
                    <Column field="summary_search" header="Resumen" :show-filter-menu="false" style="width: 20%">
                        <template #body="{ data }"><div class="flex flex-nowrap gap-2 whitespace-nowrap"><Chip :label="`${data.users_count} usuarios`" icon="pi pi-users" /><Chip :label="`${data.clientes_count} clientes`" icon="pi pi-id-card" /></div></template>
                        <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" class="w-full" placeholder="Resumen" @input="filterCallback()" /></template>
                    </Column>
                    <Column header="Acciones" :frozen="true" align-frozen="right" style="width: 7%">
                        <template #body="{ data }">
                            <div class="flex flex-nowrap items-center gap-1 whitespace-nowrap">
                                <Button v-if="can('update-sucursales')" :aria-label="`Editar ${data.nombre}`" icon="pi pi-pencil" text size="small" @click="openEdit(data)" />
                                <Button v-if="can('delete-sucursales') && data.activa" :aria-label="`Desactivar ${data.nombre}`" icon="pi pi-ban" text size="small" severity="danger" @click="deactivate(data)" />
                            </div>
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
