<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import IntlTelInput from "@/Components/IntlTelInput.vue";
import { useCodigoPostal } from "@/Composables/useCodigoPostal";

const props = defineProps({
    sucursales: Array,
});

const toast = useToast();
const visible = ref(false);
const selectedSucursal = ref(null);

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
    sugerenciasData: sugerenciasCodigosPostales,
    busquedaData: resultadosCodigoPostal,
    loading: buscandoCodigoPostal,
    error: errorCodigoPostal,
    busqueda: buscarCodigoPostal,
} = useCodigoPostal(() => form.domicilio.codigo_postal, {
    shouldFetchSugerencias: (codigo) =>
        codigo?.length >= 3 && codigo.length < 5,
    shouldFetchBusqueda: (codigo) => codigo?.length === 5,
});

const localidades = computed(() =>
    (resultadosCodigoPostal.value ?? []).map((item) => ({
        id: item.id,
        label:
            item.divisiones_administrativas?.nivel_tres?.nombre ??
            item.datos_adicionales?.asentamiento ??
            "Sin localidad",
        item,
    })),
);

const aplicarCodigoPostal = (item) => {
    if (!item) return;

    const nivelUno = item.divisiones_administrativas?.nivel_uno;
    const nivelDos = item.divisiones_administrativas?.nivel_dos;
    const nivelTres = item.divisiones_administrativas?.nivel_tres;

    Object.assign(form.domicilio, {
        pais_id: item.pais?.id ?? null,
        pais_codigo_iso: item.pais?.codigo_iso ?? "",
        codigo_postal_id: item.id,
        codigo_postal: item.codigo,
        division_admin_uno_id: nivelUno?.id ?? null,
        division_admin_dos_id: nivelDos?.id ?? null,
        division_admin_tres_id: nivelTres?.id ?? null,
        colonia: nivelTres?.nombre ?? item.datos_adicionales?.asentamiento ?? "",
        municipio: nivelDos?.nombre ?? item.datos_adicionales?.municipio ?? "",
        estado: nivelUno?.nombre ?? item.datos_adicionales?.estado ?? "",
        pais: item.pais?.nombre_es ?? "",
    });
};

const limpiarUbicacionPostal = () => {
    Object.assign(form.domicilio, {
        pais_id: null,
        pais_codigo_iso: "",
        codigo_postal_id: null,
        division_admin_uno_id: null,
        division_admin_dos_id: null,
        division_admin_tres_id: null,
        colonia: "",
        municipio: "",
        estado: "",
        pais: "",
    });
};

watch(
    () => form.domicilio.codigo_postal,
    (codigo) => {
        const normalized = String(codigo ?? "").replace(/\D/g, "").slice(0, 5);
        if (normalized !== codigo) {
            form.domicilio.codigo_postal = normalized;
            return;
        }

        if (normalized.length === 5) {
            buscarCodigoPostal();
        } else {
            limpiarUbicacionPostal();
        }
    },
);

watch(resultadosCodigoPostal, (resultados) => {
    if (!resultados?.length) return;

    aplicarCodigoPostal(
        resultados.find((item) => item.id === form.domicilio.codigo_postal_id) ??
            resultados[0],
    );
});

watch(errorCodigoPostal, (error) => {
    if (!error) return;

    limpiarUbicacionPostal();
    toast.add({
        severity: "warn",
        summary: "Código postal no encontrado",
        detail: "Revise el código postal capturado.",
        life: 4000,
    });
});

const onCodigoPostalInput = (value) => {
    form.domicilio.codigo_postal = value?.codigo ?? value ?? "";
};

const onLocalidadChange = ({ value }) => {
    aplicarCodigoPostal(
        localidades.value.find((localidad) => localidad.id === value)?.item,
    );
};

const openCreate = () => {
    selectedSucursal.value = null;
    form.defaults(emptySucursal());
    form.reset();
    form.clearErrors();
    visible.value = true;
};

const openEdit = (sucursal) => {
    selectedSucursal.value = sucursal;
    form.defaults({
        ...emptySucursal(),
        ...sucursal,
        domicilio: { ...emptySucursal().domicilio, ...(sucursal.domicilio ?? {}) },
        horario: { ...emptySucursal().horario, ...(sucursal.horario ?? {}) },
    });
    form.reset();
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
            toast.add({ severity: "success", summary: selectedSucursal.value ? "Sucursal actualizada" : "Sucursal creada", life: 3000 });
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
        form.put(route("admin.sucursales.update", selectedSucursal.value.id), options);
    } else {
        form.post(route("admin.sucursales.store"), options);
    }
};

const deactivate = (sucursal) => {
    if (confirm(`Desactivar sucursal ${sucursal.nombre}?`)) {
        router.delete(route("admin.sucursales.destroy", sucursal.id), {
            preserveScroll: true,
            only: ["sucursales"],
            onSuccess: () => toast.add({ severity: "success", summary: "Sucursal desactivada", life: 3000 }),
        });
    }
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
    ].filter(Boolean).join(", ");
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
            <div class="p-4">
                <div class="flex justify-end pb-6">
                    <Button label="Crear sucursal" icon="pi pi-plus" @click="openCreate" />
                </div>

                <DataTable :value="props.sucursales" paginator :rows="10" responsive-layout="scroll">
                    <Column field="nombre" header="Nombre" sortable />
                    <Column field="clave" header="Clave" sortable />
                    <Column header="Domicilio">
                        <template #body="{ data }">
                            <span class="block max-w-96 truncate" :title="formatAddress(data.domicilio)">
                                {{ formatAddress(data.domicilio) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="prefijo_folio" header="Prefijo" />
                    <Column header="Folios">
                        <template #body="{ data }">
                            <div class="text-sm">
                                Sol {{ data.consecutivo_solicitud }} · Con {{ data.consecutivo_contrato }} · Cred {{ data.consecutivo_credito }} · Rec {{ data.consecutivo_recibo }}
                            </div>
                        </template>
                    </Column>
                    <Column field="activa" header="Estado">
                        <template #body="{ data }">
                            <Tag :severity="data.activa ? 'success' : 'secondary'">
                                {{ data.activa ? "Activa" : "Inactiva" }}
                            </Tag>
                        </template>
                    </Column>
                    <Column header="Acciones">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" text size="small" @click="openEdit(data)" />
                            <Button v-if="data.activa" icon="pi pi-ban" text size="small" severity="danger" @click="deactivate(data)" />
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
                                v-model="form.telefono"
                                emit-e164
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
                                <AutoComplete
                                    id="codigo_postal"
                                    :model-value="form.domicilio.codigo_postal"
                                    :suggestions="sugerenciasCodigosPostales"
                                    option-label="codigo"
                                    :loading="buscandoCodigoPostal"
                                    :invalid="!!form.errors['domicilio.codigo_postal']"
                                    fluid
                                    @update:model-value="onCodigoPostalInput"
                                />
                                <small v-if="form.errors['domicilio.codigo_postal']" class="text-red-500">{{ form.errors["domicilio.codigo_postal"] }}</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Colonia</label>
                                <Select
                                    v-if="localidades.length"
                                    v-model="form.domicilio.codigo_postal_id"
                                    :options="localidades"
                                    option-label="label"
                                    option-value="id"
                                    fluid
                                    @change="onLocalidadChange"
                                />
                                <InputText v-else v-model="form.domicilio.colonia" disabled fluid />
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
