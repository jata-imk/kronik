<script setup>
import { useToast } from "primevue/usetoast";
import { ref, watch, computed, onMounted } from "vue";

import { useCodigoPostal } from "@composables/useCodigoPostal";
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
        default: () => ({}),
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
        default: () => ({}),
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
const initialLoad = {
    divisionesAdministrativas: {
        uno: [],
        dos: [],
        tres: [],
        ...(props.initialLoad?.divisionesAdministrativas ?? {}),
    },
};
const isInitialLoadProccessActive = ref(true);

const refAutocompleteCodigoPostal = ref(null);

const divisionesAdminUno = ref(
    existentRecord ? initialLoad.divisionesAdministrativas.uno : [],
);
const divisionAdminUnoSeleccionada = ref(
    (existentRecord && form.division_admin_uno_id) ?? null,
);
const divisionesAdminDos = ref(
    existentRecord ? initialLoad.divisionesAdministrativas.dos : [],
);
const divisionAdminDosSeleccionada = ref(
    (existentRecord && form.division_admin_dos_id) ?? null,
);
const divisionesAdminTres = ref(
    existentRecord ? initialLoad.divisionesAdministrativas.tres : [],
);
const divisionAdminTresSeleccionada = ref(
    (existentRecord && form.division_admin_tres_id) ?? null,
);
const tipoLocalidadSeleccionada = ref(
    existentRecord ? initialLoad.divisionesAdministrativas.tres[0]?.tipo : null,
);

const DEBOUNCE_MS_CODIGO_POSTAL = 300;
const MIN_LENGTH_CODIGO_POSTAL = 3;
const MAX_LENGTH_CODIGO_POSTAL = 5;

const shouldFetchSugerencias = (codigo) => {
    if (!codigo || codigo.length < MIN_LENGTH_CODIGO_POSTAL) {
        return false;
    }

    if (codigo.length === MAX_LENGTH_CODIGO_POSTAL) {
        return false;
    }

    return true;
};

const shouldFetchBusqueda = (codigo) => {
    if (codigo.length < MAX_LENGTH_CODIGO_POSTAL) {
        return false;
    }

    return true;
};

const {
    sugerenciasData: sugerenciasCodigosPostales,
    busquedaData: busquedaCodigosPostales,
    error: errorUseCodigoPostal,
    busqueda: buscarCodigoPostal,
} = useCodigoPostal(() => form.codigo_postal, {
    shouldFetchSugerencias,
    shouldFetchBusqueda,
    debounceMs: DEBOUNCE_MS_CODIGO_POSTAL,
});

const hideResultsAutocomplete = (refAutocomplete) => {
    if (!refAutocomplete.value) return;

    refAutocomplete.value.hide();
    const svgSpinner = refAutocomplete.value.$el.querySelector(
        ".p-icon.p-icon-spin.p-autocomplete-loader",
    );
    if (svgSpinner) {
        svgSpinner.parentElement.removeChild(svgSpinner);
    }
};

const obtenerDivisionesAdministrativasPorNivel = (nivel, codigosPostales) => {
    return [
        ...new Set(
            codigosPostales.map((cpItem) => {
                return {
                    id: cpItem.divisiones_administrativas[nivel].id,
                    nombre: cpItem.divisiones_administrativas[nivel].nombre,
                    tipo: cpItem.divisiones_administrativas[nivel].tipo,
                };
            }),
        ),
    ];
};

const setDivisionAdministrativaSelectOptionsAndValue = (
    codigosPostales,
    refDivisionesAdminOptions,
    refDivisionAdminValue,
    stringNivel,
    selectedValue = undefined,
) => {
    const divisionesAdmin =
        codigosPostales.length > 0 &&
        codigosPostales[0].divisiones_administrativas;

    refDivisionesAdminOptions.value = divisionesAdmin
        ? obtenerDivisionesAdministrativasPorNivel(stringNivel, codigosPostales)
        : [];

    refDivisionAdminValue.value =
        selectedValue === undefined
            ? (divisionesAdmin?.[stringNivel]?.id ?? null)
            : selectedValue;
};

const resetDivisionAdministrativaSelectOptionsAndValue = () => {
    for (const [index, nivel] of Object.entries(["uno", "dos", "tres"])) {
        const divisionesAdminOptions = [
            divisionesAdminUno,
            divisionesAdminDos,
            divisionesAdminTres,
        ][index];

        const divisionAdminSeleccionada = [
            divisionAdminUnoSeleccionada,
            divisionAdminDosSeleccionada,
            divisionAdminTresSeleccionada,
        ][index];

        setDivisionAdministrativaSelectOptionsAndValue(
            [],
            divisionesAdminOptions,
            divisionAdminSeleccionada,
            `nivel_${nivel}`,
            null,
        );

        form[`division_admin_${nivel}_id`] = null;
    }

    tipoLocalidadSeleccionada.value = null;
};

const handleCompleteCodigosPostales = () => {
    if (form.codigo_postal?.length === MAX_LENGTH_CODIGO_POSTAL) {
        hideResultsAutocomplete(refAutocompleteCodigoPostal);
        return;
    }
};

watch(
    () => form.codigo_postal,
    () => {
        if (form.codigo_postal?.length === 0) {
            resetDivisionAdministrativaSelectOptionsAndValue();
        }

        if (existentRecord && isInitialLoadProccessActive.value) {
            return;
        }

        buscarCodigoPostal();
    },
    { immediate: true },
);

const onChangeLocalidad = (event) => {
    let divisionNivelTresSeleccionada = null;
    divisionNivelTresSeleccionada = divisionesAdminTres.value.find(
        (item) => item.id === event.value,
    );

    tipoLocalidadSeleccionada.value = divisionNivelTresSeleccionada?.tipo ?? null;
    form.division_admin_tres_id = divisionNivelTresSeleccionada?.id ?? null;
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
        )?.nombre || "";
    const nombreDivisionAdminDos =
        divisionesAdminDos.value.find(
            (item) => item.id === divisionAdminDosSeleccionada.value,
        )?.nombre || "";
    const nombreDivisionAdminTres =
        divisionesAdminTres.value.find(
            (item) => item.id === divisionAdminTresSeleccionada.value,
        )?.nombre || "";

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
                    geocodingResult.value?.[0]?.lat ??
                    0,
                lng:
                    geocodingResult.value?.[0]?.lon ??
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
                    )?.nombre || "";

                const nombreDivisionAdminDos =
                    divisionesAdminDos.value.find(
                        (item) =>
                            item.id === divisionAdminDosSeleccionada.value,
                    )?.nombre || "";

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

watch(
    () => refAutocompleteCodigoPostal.value,
    () => {
        refAutocompleteCodigoPostal.value?.$el
            .querySelector("input")
            .setAttribute("maxlength", MAX_LENGTH_CODIGO_POSTAL);
    },
);

watch([busquedaCodigosPostales, errorUseCodigoPostal], () => {
    if (existentRecord && isInitialLoadProccessActive.value) {
        return;
    }

    if (errorUseCodigoPostal.value) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se encontraron resultados",
        });

        resetDivisionAdministrativaSelectOptionsAndValue();

        return;
    }

    if (
        busquedaCodigosPostales.value &&
        busquedaCodigosPostales.value.length === 0
    ) {
        resetDivisionAdministrativaSelectOptionsAndValue();
        return;
    }

    for (const [index, nivel] of Object.entries(["uno", "dos", "tres"])) {
        const divisionesAdminOptions = [
            divisionesAdminUno,
            divisionesAdminDos,
            divisionesAdminTres,
        ][index];

        const divisionAdminSeleccionada = [
            divisionAdminUnoSeleccionada,
            divisionAdminDosSeleccionada,
            divisionAdminTresSeleccionada,
        ][index];

        setDivisionAdministrativaSelectOptionsAndValue(
            busquedaCodigosPostales.value ?? [],
            divisionesAdminOptions,
            divisionAdminSeleccionada,
            `nivel_${nivel}`,
            nivel === "tres" ? null : undefined,
        );

        form[`division_admin_${nivel}_id`] = divisionAdminSeleccionada.value;
    }

    hideResultsAutocomplete(refAutocompleteCodigoPostal);
});

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
            <AutoComplete 
                :suggestions="sugerenciasCodigosPostales" @complete="handleCompleteCodigosPostales"
                id="codigo_postal" name="codigo_postal"
                :modelValue="form.codigo_postal" :disabled="readOnly" ref="refAutocompleteCodigoPostal"
                @update:modelValue="val => form.codigo_postal = val?.codigo || val"
                optionLabel="codigo"
                optionValue="codigo"
                fluid :invalid="!!formErrors.codigo_postal" />
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
                fluid :invalid="!!formErrors.division_admin_tres_id" />
            <Message v-if="formErrors.division_admin_tres_id" severity="error" size="small">
                {{ formErrors.division_admin_tres_id }}
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
