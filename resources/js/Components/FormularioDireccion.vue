<script setup>
import { useToast } from "primevue/usetoast";
import { computed, onMounted, ref, watch } from "vue";

import CodigoPostalAutocomplete from "@/Components/CodigoPostalAutocomplete.vue";
import { useGeocoding } from "@/Composables/useGeocoding";

const toast = useToast();

const {
    result: geocodingResult,
    error: geocodingError,
    search: geocodingSearch,
} = useGeocoding();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    formErrors: {
        type: Object,
        required: false,
        default: {},
    },
    newRecord: {
        type: Boolean,
        required: false,
        default: true,
    },
    readOnly: {
        type: Boolean,
        required: false,
        default: false,
    },
    initialLoad: {
        type: Object,
        required: false,
        default: {},
    },
    direccionMapConnector: {
        type: Function,
        required: false,
        default: undefined,
    },
});

const form = props.form;
const formErrors = props.formErrors;
const newRecord = props.newRecord;
const existentRecord = !newRecord;
const readOnly = props.readOnly;
const initialLoad = props.initialLoad;
const isInitialLoadProccessActive = ref(true);

const divisionesAdminUno = ref(
    (existentRecord && initialLoad.divisionesAdministrativas?.uno) ?? [],
);
const divisionAdminUnoSeleccionada = ref(
    (existentRecord && form.division_admin_uno_id) ?? null,
);
const divisionesAdminDos = ref(
    (existentRecord && initialLoad.divisionesAdministrativas?.dos) ?? [],
);
const divisionAdminDosSeleccionada = ref(
    (existentRecord && form.division_admin_dos_id) ?? null,
);
const divisionesAdminTres = ref(
    (existentRecord && initialLoad.divisionesAdministrativas?.tres) ?? [],
);
const divisionAdminTresSeleccionada = ref(
    (existentRecord && form.division_admin_tres_id) ?? null,
);
const tipoLocalidadSeleccionada = ref(
    existentRecord && initialLoad.divisionesAdministrativas?.tres[0]?.tipo,
);

const resetDivisionAdministrativaSelectOptionsAndValue = () => {
    divisionesAdminUno.value = [];
    divisionesAdminDos.value = [];
    divisionesAdminTres.value = [];
    divisionAdminUnoSeleccionada.value = null;
    divisionAdminDosSeleccionada.value = null;
    divisionAdminTresSeleccionada.value = null;
    form.pais_id = null;
    form.codigo_postal_id = null;
    form.division_admin_uno_id = null;
    form.division_admin_dos_id = null;
    form.division_admin_tres_id = null;
    tipoLocalidadSeleccionada.value = null;
};

const aplicarCodigoPostal = (contexto) => {
    const nivelUno = contexto.divisionAdminUno;
    const nivelDos = contexto.divisionAdminDos;

    divisionesAdminUno.value = nivelUno ? [nivelUno] : [];
    divisionesAdminDos.value = nivelDos ? [nivelDos] : [];
    divisionesAdminTres.value = contexto.localidades.map((localidad) => ({
        id: localidad.divisionAdminTresId,
        nombre: localidad.nombre,
        tipo: localidad.tipo,
        codigoPostalId: localidad.codigoPostalId,
    }));

    divisionAdminUnoSeleccionada.value = nivelUno?.id ?? null;
    divisionAdminDosSeleccionada.value = nivelDos?.id ?? null;
    divisionAdminTresSeleccionada.value = null;

    Object.assign(form, {
        codigo_postal: contexto.codigo,
        pais_id: contexto.pais?.id ?? null,
        codigo_postal_id: null,
        division_admin_uno_id: nivelUno?.id ?? null,
        division_admin_dos_id: nivelDos?.id ?? null,
        division_admin_tres_id: null,
    });

    tipoLocalidadSeleccionada.value = null;
};

const onChangeLocalidad = (event) => {
    let divisionNivelTresSeleccionada = null;
    divisionNivelTresSeleccionada = divisionesAdminTres.value.find(
        (item) => item.id === event.value,
    );

    if (!divisionNivelTresSeleccionada) {
        form.codigo_postal_id = null;
        form.division_admin_tres_id = null;
        tipoLocalidadSeleccionada.value = null;
        return;
    }

    tipoLocalidadSeleccionada.value = divisionNivelTresSeleccionada.tipo;
    form.codigo_postal_id = divisionNivelTresSeleccionada.codigoPostalId;
    form.division_admin_tres_id = divisionNivelTresSeleccionada.id;
};

const queryGeocoding = computed(() => {
    if (
        !divisionAdminUnoSeleccionada.value ||
        !divisionAdminDosSeleccionada.value ||
        !divisionAdminTresSeleccionada.value
    ) {
        return null;
    }

    const nombreDivisionAdminUno =
        divisionesAdminUno.value.find(
            (item) => item.id === divisionAdminUnoSeleccionada.value,
        ).nombre || "";
    const nombreDivisionAdminDos =
        divisionesAdminDos.value.find(
            (item) => item.id === divisionAdminDosSeleccionada.value,
        ).nombre || "";
    const nombreDivisionAdminTres =
        divisionesAdminTres.value.find(
            (item) => item.id === divisionAdminTresSeleccionada.value,
        ).nombre || "";

    return `${form.codigo_postal}, ${nombreDivisionAdminTres}, ${nombreDivisionAdminDos}, ${nombreDivisionAdminUno}`;
});

watch(
    () => queryGeocoding.value,
    async () => {
        if (!queryGeocoding.value) {
            return;
        }

        await geocodingSearch(queryGeocoding.value);
    },
    { immediate: true },
);

watch(
    () => [geocodingResult.value, geocodingError.value],
    () => {
        if (!queryGeocoding.value) {
            return;
        }

        if (!geocodingResult.value && !geocodingError.value) {
            return;
        }
        if (newRecord) {
            form.coordenadas = {
                lat:
                    geocodingResult.value[0]?.lat ??
                    geocodingResult.value[0]?.lat ??
                    0,
                lng:
                    geocodingResult.value[0]?.lon ??
                    geocodingResult.value[0]?.lon ??
                    0,
            };
        }

        if (geocodingError.value) {
            toast.add({
                severity: "warn",
                summary: "Datos geoespaciales no encontrados",
                detail: "Ubique el marcador de domicilio manualmente en el mapa",
            });

            try {
                const nombreDivisionAdminUno =
                    divisionesAdminUno.value.find(
                        (item) =>
                            item.id === divisionAdminUnoSeleccionada.value,
                    ).nombre || "";

                const nombreDivisionAdminDos =
                    divisionesAdminDos.value.find(
                        (item) =>
                            item.id === divisionAdminDosSeleccionada.value,
                    ).nombre || "";

                geocodingSearch(
                    `${nombreDivisionAdminDos}, ${nombreDivisionAdminUno}`,
                );
            } catch (error) {
                console.error(error);
            }

            return;
        }
    },
);

onMounted(() => {
    if (!existentRecord || !props.direccionMapConnector) {
        isInitialLoadProccessActive.value = false;
    }

    if (props.direccionMapConnector) {
        const { setConfig, setFormConfig } = props.direccionMapConnector();

        setConfig({
            readOnly,
            existentRecord,
            isInitialLoadProccessActive,
        });

        setFormConfig({
            form,
            initialLoad,
            queryGeocoding,
            geocodingResult,
            geocodingError,
        });
    }
});
</script>

<template>
    <div class="grid grid-cols-4 gap-4">
        <div class="col-span-4">
            <label class="block text-sm font-medium mb-1" for="linea_uno">Dirección</label>
            <input type="hidden" v-model="form.tipo" :disabled="readOnly" name="tipo" value="personal">
            <InputText
                id="linea_uno" name="linea_uno"
                v-model="form.linea_uno" :disabled="readOnly"
                fluid :invalid="!!formErrors.linea_uno" />
            <Message size="small" severity="secondary" variant="simple">Llene con la calle, el número exterior y los cruzamientos.</Message>
            <Message v-if="formErrors.linea_uno" severity="error" size="small">
                {{ formErrors.linea_uno }}
            </Message>
        </div>

        <div class="col-span-2">
            <label class="block text-sm font-medium mb-1" for="codigo_postal">Código Postal</label>
            <CodigoPostalAutocomplete
                v-model="form.codigo_postal"
                input-id="codigo_postal"
                input-name="codigo_postal"
                :disabled="readOnly"
                :invalid="!!formErrors.codigo_postal"
                @changed="resetDivisionAdministrativaSelectOptionsAndValue"
                @confirmed="aplicarCodigoPostal"
            />
            <Message v-if="formErrors.codigo_postal" severity="error" size="small">
                {{ formErrors.codigo_postal }}
            </Message
            >
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1" for="division_admin_uno_id">Estado</label>
            <Select
                id="division_admin_uno_id" name="division_admin_uno_id"
                v-model="divisionAdminUnoSeleccionada"
                :options="divisionesAdminUno"
                optionLabel="nombre"
                optionValue="id"
                disabled
                fluid :invalid="!!formErrors.division_admin_uno_id" />
            <Message v-if="formErrors.division_admin_uno_id" severity="error" size="small">
                {{ formErrors.division_admin_uno_id }}
            </Message>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1" for="division_admin_dos_id">Municipio</label>
            <Select
                id="division_admin_dos_id" name="division_admin_dos_id"
                v-model="divisionAdminDosSeleccionada"
                :options="divisionesAdminDos"
                optionLabel="nombre"
                optionValue="id"
                disabled
                fluid :invalid="!!formErrors.division_admin_dos_id" />
            <Message v-if="formErrors.division_admin_dos_id" severity="error" size="small">
                {{ formErrors.division_admin_dos_id }}
            </Message>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-1" for="division_admin_tres_id">Localidad</label>
            <Select
                id="division_admin_tres_id" name="division_admin_tres_id"
                v-model="divisionAdminTresSeleccionada" :disabled="readOnly"
                @change="onChangeLocalidad"
                :options="divisionesAdminTres"
                optionLabel="nombre"
                optionValue="id"
                fluid :invalid="!!(formErrors.division_admin_tres_id || formErrors.codigo_postal_id)" />
            <Message v-if="formErrors.division_admin_tres_id || formErrors.codigo_postal_id" severity="error" size="small">
                {{ formErrors.division_admin_tres_id ?? formErrors.codigo_postal_id }}
            </Message>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Tipo Localidad</label>
            <InputText v-model="tipoLocalidadSeleccionada" disabled fluid />
        </div>

        <div>
            <label class="block text-sm font-medium mb-1" for="linea_dos">Número interior / Departamento</label>
            <InputText
                id="linea_dos" name="linea_dos"
                v-model="form.linea_dos" :disabled="readOnly"
                fluid :invalid="!!formErrors.linea_dos" />
            <Message v-if="formErrors.linea_dos" severity="error" size="small">
                {{ formErrors.linea_dos }}
            </Message>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1" for="linea_tres">Datos adicionales (Referencias)</label>
        <InputText
            id="linea_tres" name="linea_tres"
            v-model="form.linea_tres" :disabled="readOnly"
            fluid :invalid="!!formErrors.linea_tres" />
        <Message v-if="formErrors.linea_tres" severity="error" size="small">
            {{ formErrors.linea_tres }}
        </Message>
    </div>
</template>
