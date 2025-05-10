<script setup>
import { ref, watch, computed } from "vue";
import { useToast } from "primevue/usetoast";
import { usePage, useForm } from "@inertiajs/vue3";

import "@css/flags.css";
import IntlTelInput from "@components/IntlTelInput.vue";
import MapLibreMap from "@components/MapLibre/MapLibreMap.vue";

import { useCodigoPostal } from "@composables/useCodigoPostal";
import { useGeocoding } from "@/Composables/useGeocoding";
const {
    result: geocodingResult,
    error: geocodingError,
    search: geocodingSearch,
} = useGeocoding();

import { useClientMapSetup } from "@/Composables/MapLibre/useClientMapSetup";

const { handleGeocodingResult } = useClientMapSetup();

const page = usePage();
const form = useForm({
    primer_nombre: "",
    segundo_nombre: "",
    apellido_paterno: "",
    apellido_materno: "",
    fecha_nacimiento: "",
    pais_nacimiento_id: null,
    email: "",
    sexo: "",
    telefono_codigo_pais: "",
    telefono: "",
    datos_fiscales: {
        tipo_persona: "",
        regimen_fiscal_id: null,
        curp: "",
        rfc: "",
        razon_social: "",
    },
    direcciones: [
        {
            tipo: "",
            linea_uno: "",
            linea_dos: "",
            linea_tres: "",
            codigo_postal: "",
            division_admin_uno_id: null,
            division_admin_dos_id: null,
            division_admin_tres_id: null,
            datos_adicionales: "",
        },
    ],
});

const paises = ref(page.props.paises);
const paisSeleccionado = ref();
const paisesFiltrados = ref();

const filtrarPaises = (event) => {
    setTimeout(() => {
        if (!event.query.trim().length) {
            paisesFiltrados.value = [...paises.value];
        } else {
            paisesFiltrados.value = paises.value.filter((pais) => {
                return pais.nombre_es
                    .toLowerCase()
                    .startsWith(event.query.toLowerCase());
            });
        }
    }, 250);
};

const onChangePaisNacimiento = (event) => {
    const opcionSeleccionada = event.value;

    if (opcionSeleccionada?.id === undefined) {
        form.pais_nacimiento_id = "";
        return;
    }

    form.pais_nacimiento_id = opcionSeleccionada.id;
};

const onChangePaisNumeroTelefono = ({ dialCode }) => {
    if (dialCode) {
        form.telefono_codigo_pais = dialCode.trim();
    }
};

const refAutocompleteCodigoPostal = ref(null);

const divisionesAdminUno = ref([]);
const divisionAdminUnoSeleccionada = ref(null);
const divisionesAdminDos = ref([]);
const divisionAdminDosSeleccionada = ref(null);
const divisionesAdminTres = ref([]);
const divisionAdminTresSeleccionada = ref(null);
const tipoLocalidadSeleccionada = ref(null);

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
} = useCodigoPostal(() => form.direcciones[0].codigo_postal, {
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
            ? divisionesAdmin[stringNivel]?.id
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

        form.direcciones[0][`division_admin_${nivel}_id`] = null;
    }

    tipoLocalidadSeleccionada.value = null;
};

const handleCompleteCodigosPostales = () => {
    if (form.direcciones[0].codigo_postal.length === MAX_LENGTH_CODIGO_POSTAL) {
        hideResultsAutocomplete(refAutocompleteCodigoPostal);
        return;
    }
};

watch(
    () => form.direcciones[0].codigo_postal,
    () => {
        if (form.direcciones[0].codigo_postal.length === 0) {
            resetDivisionAdministrativaSelectOptionsAndValue();
        }

        buscarCodigoPostal();
    },
);

const onChangeLocalidad = (event) => {
    let divisionNivelTresSeleccionada = null;

    divisionNivelTresSeleccionada = divisionesAdminTres.value.find(
        (item) => item.id === event.value,
    );

    tipoLocalidadSeleccionada.value = divisionNivelTresSeleccionada.tipo;
    form.direcciones[0].division_admin_tres_id =
        divisionNivelTresSeleccionada.id;
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

    return `${form.direcciones[0].codigo_postal}, ${nombreDivisionAdminTres}, ${nombreDivisionAdminDos}, ${nombreDivisionAdminUno}`;
});

watch(
    () => queryGeocoding.value,
    async () => {
        if (!queryGeocoding.value) {
            return;
        }

        await geocodingSearch(queryGeocoding.value);

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

                await geocodingSearch(
                    `${nombreDivisionAdminDos}, ${nombreDivisionAdminUno}`,
                );

                handleGeocodingResult(geocodingResult.value);
            } catch (error) {
                console.error(error);
            }

            return;
        }

        handleGeocodingResult(geocodingResult.value);
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
        throw new Error("No se encontraron resultados");
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

        form.direcciones[0][`division_admin_${nivel}_id`] =
            divisionAdminSeleccionada.value;
    }

    hideResultsAutocomplete(refAutocompleteCodigoPostal);
});

const sexos = page.props.sexos;
const tiposPersona = page.props.tiposPersona;
const regimenesFiscales = page.props.regimenesFiscales;
const regimenesFiscalesFiltered = ref();

const onChangeTipoPersona = ({ value }) => {
    if (value === "fisica") {
        regimenesFiscalesFiltered.value = regimenesFiscales.filter(
            (regimen) => regimen.fisica,
        );
    } else {
        regimenesFiscalesFiltered.value = regimenesFiscales.filter(
            (regimen) => regimen.moral,
        );
    }
};

const toast = useToast();
const fechaHoy = new Date();
const fechaMinimaAdultos = ref(new Date());
fechaMinimaAdultos.value.setFullYear(fechaHoy.getFullYear() - 18);

const onSubmit = () => {
    form.post(route("clientes.store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            toast.add({
                severity: "success",
                summary: "Cliente creado exitosamente",
                life: 3000,
            });
        },
    });
};
</script>

<template>
    <div class="card p-4 max-w-3xl mx-auto">
        <h2 class="text-2xl font-bold mb-4">Formulario de clientes</h2>

        <form
            @submit.prevent="onSubmit"
            class="grid gap-4"
        >
            <h3 class="text-lg font-medium text-gray-900">Información personal</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="primer_nombre">Primer Nombre</label>
                    <InputText
                        id="primer_nombre" name="primer_nombre"
                        v-model="form.primer_nombre"
                        fluid :invalid="!!form.errors.primer_nombre " />
                    <Message v-if="form.errors.primer_nombre" severity="error" size="small">
                        {{ form.errors.primer_nombre }}
                    </Message
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="primer_nombre">Segundo Nombre</label>
                    <InputText
                        id="segundo_nombre" name="segundo_nombre"
                        v-model="form.segundo_nombre"
                        fluid :invalid="!!form.errors.segundo_nombre " />
                    <Message v-if="form.errors.segundo_nombre" severity="error" size="small">
                        {{ form.errors.segundo_nombre }}
                    </Message
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="apellido_paterno">Primer Apellido</label>
                    <InputText
                        id="apellido_paterno" name="apellido_paterno"
                        v-model="form.apellido_paterno"
                        fluid :invalid="!!form.errors.apellido_paterno " />
                    <Message v-if="form.errors.apellido_paterno" severity="error" size="small">
                        {{ form.errors.apellido_paterno }}
                    </Message
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="apellido_materno">Segundo Apellido</label>
                    <InputText
                        id="apellido_materno" name="apellido_materno"
                        v-model="form.apellido_materno"
                        fluid :invalid="!!form.errors.apellido_materno " />
                    <Message v-if="form.errors.apellido_materno" severity="error" size="small">
                        {{ form.errors.apellido_materno }}
                    </Message
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="fecha_nacimiento">Fecha de nacimiento</label>
                    <DatePicker
                        id="fecha_nacimiento" name="fecha_nacimiento"
                        v-model="form.fecha_nacimiento"
                        fluid
                        :maxDate="fechaMinimaAdultos" :invalid="!!form.errors.fecha_nacimiento"
                        showIcon iconDisplay="input" />
                    <Message v-if="form.errors.fecha_nacimiento" severity="error" size="small">
                        {{ form.errors.fecha_nacimiento }}
                    </Message
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="pais_nacimiento_id">País de nacimiento</label>
                    <AutoComplete 
                        dropdown :suggestions="paisesFiltrados" @complete="filtrarPaises"
                        id="pais_nacimiento_id" name="pais_nacimiento_id"
                        v-model="paisSeleccionado"
                        @change="onChangePaisNacimiento"
                        optionLabel="nombre_es"
                        optionValue="id"
                        fluid :invalid="!!form.errors.pais_nacimiento_id" >
                        <template #option="slotProps">
                            <div class="flex items-center">
                                <img :alt="slotProps.option.nombre_es" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png" :class="`mr-2 flag flag-${slotProps.option.codigo_iso.toLowerCase()}`" style="width: 18px" />
                                <div>{{ slotProps.option.nombre_es }}</div>
                            </div>
                        </template>
                        <template #dropdownicon>
                            <i class="pi pi-map" />
                        </template>
                    </AutoComplete>
                    <Message v-if="form.errors.pais_nacimiento_id" severity="error" size="small">
                        {{ form.errors.pais_nacimiento_id }}
                    </Message
                    >

                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="sexo">Sexo</label>
                    <Select
                        id="sexo" name="sexo"
                        v-model="form.sexo"
                        :options="sexos"
                        optionLabel="label"
                        optionValue="value"
                        fluid :invalid="!!form.errors.sexo" />
                    <Message v-if="form.errors.sexo" severity="error" size="small">
                        {{ form.errors.sexo }}
                    </Message
                    >
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <InputText
                        id="email" name="email"
                        v-model="form.email"
                        fluid :invalid="!!form.errors.email" />
                    <Message v-if="form.errors.email" severity="error" size="small">
                        {{ form.errors.email }}
                    </Message
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="telefono">Número de teléfono</label>
                    <IntlTelInput
                        id="telefono" name="telefono"
                        v-model="form.telefono"
                        @changeCountry="onChangePaisNumeroTelefono"
                        fluid :invalid="!!form.errors.telefono" />
                    <Message v-if="form.errors.telefono" severity="error" size="small">
                        {{ form.errors.telefono }}
                    </Message
                    >
                </div>
            </div>

            <Divider />

            <h3 class="text-lg font-medium text-gray-900">Información fiscal</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="tipo_persona">Tipo de persona</label>
                    <Select
                        id="tipo_persona" name="tipo_persona"
                        v-model="form.datos_fiscales.tipo_persona"
                        :options="tiposPersona"
                        optionLabel="label"
                        optionValue="value"
                        @change="onChangeTipoPersona"
                        fluid :invalid="!!form.errors['datos_fiscales.tipo_persona']" />
                    <Message v-if="form.errors['datos_fiscales.tipo_persona']" severity="error" size="small">
                        {{ form.errors['datos_fiscales.tipo_persona'] }}
                    </Message
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="regimen_fiscal_id">Regimen fiscal</label>
                    <Select
                        :disabled="!form.datos_fiscales.tipo_persona"
                        id="regimen_fiscal_id" name="regimen_fiscal_id"
                        v-model="form.datos_fiscales.regimen_fiscal_id"
                        :options="regimenesFiscalesFiltered"
                        optionLabel="descripcion"
                        optionValue="id"
                        fluid :invalid="!!form.errors['datos_fiscales.regimen_fiscal_id']" />
                    <Message v-if="form.errors['datos_fiscales.regimen_fiscal_id']" severity="error" size="small">
                        {{ form.errors['datos_fiscales.regimen_fiscal_id'] }}
                    </Message
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="curp">CURP</label>
                    <InputText
                        id="curp" name="curp"
                        v-model="form.datos_fiscales.curp"
                        fluid :invalid="!!form.errors['datos_fiscales.curp']" />
                    <Message v-if="form.errors['datos_fiscales.curp']" severity="error" size="small">
                        {{ form.errors['datos_fiscales.curp'] }}
                    </Message
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="rfc">RFC</label>
                    <InputText
                        id="rfc" name="rfc"
                        v-model="form.datos_fiscales.rfc"
                        fluid :invalid="!!form.errors['datos_fiscales.rfc']" />
                    <Message v-if="form.errors['datos_fiscales.rfc']" severity="error" size="small">
                        {{ form.errors['datos_fiscales.rfc'] }}
                    </Message
                    >
                </div>

                <div v-if="form.datos_fiscales.tipo_persona === 'moral'">
                    <label class="block text-sm font-medium mb-1" for="razon_social">Razón social</label>
                    <InputText
                        id="razon_social" name="razon_social"
                        v-model="form.datos_fiscales.razon_social"
                        fluid :invalid="!!form.errors['datos_fiscales.razon_social']" />
                    <Message v-if="form.errors['datos_fiscales.razon_social']" severity="error" size="small">
                        {{ form.errors['datos_fiscales.razon_social'] }}
                    </Message
                    >
                </div>
            </div>

            <Divider />

            <h3 class="text-lg font-medium text-gray-900">Domicilio</h3>

            <div class="grid grid-cols-4 gap-4">
                <div class="col-span-4">
                    <label class="block text-sm font-medium mb-1" for="direcciones.0.linea_uno">Dirección</label>
                    <input type="hidden" v-model="form.direcciones[0].tipo" name="direcciones.0.tipo" value="personal">
                    <InputText
                        id="direcciones.0.linea_uno" name="direcciones.0.linea_uno"
                        v-model="form.direcciones[0].linea_uno"
                        fluid :invalid="!!form.errors['direcciones.0.linea_uno']" />
                    <Message size="small" severity="secondary" variant="simple">Llene con la calle, el número exterior y los cruzamientos.</Message>
                    <Message v-if="form.errors['direcciones.0.linea_uno']" severity="error" size="small">
                    

                        {{ form.errors['direcciones.0.linea_uno'] }}
                    </Message>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1" for="direcciones.0.codigo_postal">Código Postal</label>
                    <AutoComplete 
                        :suggestions="sugerenciasCodigosPostales" @complete="handleCompleteCodigosPostales"
                        id="direcciones.0.codigo_postal" name="direcciones.0.codigo_postal"
                        :modelValue="form.direcciones[0].codigo_postal" ref="refAutocompleteCodigoPostal"
                        @update:modelValue="val => form.direcciones[0].codigo_postal = val?.codigo || val"
                        optionLabel="codigo"
                        optionValue="codigo"
                        fluid :invalid="!!form.errors['direcciones.0.codigo_postal']" />
                    <Message v-if="form.errors['direcciones.0.codigo_postal']" severity="error" size="small">
                        {{ form.errors['direcciones.0.codigo_postal'] }}
                    </Message
                    >
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="direcciones.0.division_admin_uno_id">Estado</label>
                    <Select
                        id="direcciones.0.division_admin_uno_id" name="direcciones.0.division_admin_uno_id"
                        v-model="divisionAdminUnoSeleccionada"
                        :options="divisionesAdminUno"
                        optionLabel="nombre"
                        optionValue="id"
                        disabled
                        fluid :invalid="!!form.errors['direcciones.0.division_admin_uno_id']" />
                    <Message v-if="form.errors['direcciones.0.division_admin_uno_id']" severity="error" size="small">
                        {{ form.errors['direcciones.0.division_admin_uno_id'] }}
                    </Message>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="direcciones.0.division_admin_dos_id">Municipio</label>
                    <Select
                        id="direcciones.0.division_admin_dos_id" name="direcciones.0.division_admin_dos_id"
                        v-model="divisionAdminDosSeleccionada"
                        :options="divisionesAdminDos"
                        optionLabel="nombre"
                        optionValue="id"
                        disabled
                        fluid :invalid="!!form.errors['direcciones.0.division_admin_dos_id']" />
                    <Message v-if="form.errors['direcciones.0.division_admin_dos_id']" severity="error" size="small">
                        {{ form.errors['direcciones.0.division_admin_dos_id'] }}
                    </Message>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1" for="direcciones.0.division_admin_tres_id">Localidad</label>
                    <Select
                        id="direcciones.0.division_admin_tres_id" name="direcciones.0.division_admin_tres_id"
                        v-model="divisionAdminTresSeleccionada"
                        @change="onChangeLocalidad"
                        :options="divisionesAdminTres"
                        optionLabel="nombre"
                        optionValue="id"
                        fluid :invalid="!!form.errors['direcciones.0.division_admin_tres_id']" />
                    <Message v-if="form.errors['direcciones.0.division_admin_tres_id']" severity="error" size="small">
                        {{ form.errors['direcciones.0.division_admin_tres_id'] }}
                    </Message>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Tipo Localidad</label>
                    <InputText v-model="tipoLocalidadSeleccionada" disabled fluid />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="direcciones.0.linea_dos">Número interior / Departamento</label>
                    <InputText
                        id="direcciones.0.linea_dos" name="direcciones.0.linea_dos"
                        v-model="form.direcciones[0].linea_dos"
                        fluid :invalid="!!form.errors['direcciones.0.linea_dos']" />
                    <Message v-if="form.errors['direcciones.0.linea_dos']" severity="error" size="small">
                        {{ form.errors['direcciones.0.linea_dos'] }}
                    </Message>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="direcciones.0.linea_tres">Datos adicionales (Referencias)</label>
                <InputText
                    id="direcciones.0.linea_tres" name="direcciones.0.linea_tres"
                    v-model="form.direcciones[0].linea_tres"
                    fluid :invalid="!!form.errors['direcciones.0.linea_tres']" />
                <Message v-if="form.errors['direcciones.0.linea_tres']" severity="error" size="small">
                    {{ form.errors['direcciones.0.linea_tres'] }}
                </Message>
            </div>

            <div class="mt-4 w-full h-[400px]">
                <MapLibreMap />
            </div>

            <Button label="Guardar Cliente" type="submit" :disabled="form.processing" :loading="form.processing" />
        </form>
    </div>
</template>

