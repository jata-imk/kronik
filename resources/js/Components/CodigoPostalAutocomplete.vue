<script setup>
import {
    crearContextoCodigoPostal,
    normalizarCodigoPostal,
    useCodigoPostal,
} from "@/Composables/useCodigoPostal";
import { useToast } from "primevue/usetoast";
import { computed, onMounted, ref, watch } from "vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    inputId: {
        type: String,
        default: "codigo_postal",
    },
    inputName: {
        type: String,
        default: "codigo_postal",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    invalid: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue", "changed", "confirmed"]);

const toast = useToast();
const autocomplete = ref(null);
const codigo = computed(() => normalizarCodigoPostal(props.modelValue));

const {
    sugerenciasData,
    busquedaData,
    busquedaCodigo,
    loading,
    errorSugerencias,
    errorBusqueda,
    busqueda,
} = useCodigoPostal(codigo, {
    shouldFetchSugerencias: (value) =>
        !props.disabled && value.length >= 3 && value.length < 5,
    shouldFetchBusqueda: (value) => !props.disabled && value.length === 5,
});

const opciones = computed(() => {
    if (codigo.value.length < 5) {
        return sugerenciasData.value;
    }

    if (busquedaCodigo.value === codigo.value && busquedaData.value?.length) {
        return [{ codigo: codigo.value }];
    }

    return [];
});

const mostrarMensajeVacio = computed(
    () =>
        !loading.value &&
        (codigo.value.length < 5 || Boolean(errorBusqueda.value)),
);

watch(errorBusqueda, (error) => {
    if (!error) {
        return;
    }

    toast.add({
        severity: "warn",
        summary: "Código postal no encontrado",
        detail: "Revise el código postal capturado.",
        life: 4000,
    });
});

watch(errorSugerencias, (error) => {
    if (!error) {
        return;
    }

    toast.add({
        severity: "error",
        summary: "No fue posible consultar códigos postales",
        detail: "Intente nuevamente.",
        life: 4000,
    });
});

const actualizarCodigo = (value) => {
    const normalized = normalizarCodigoPostal(value);

    if (typeof value !== "object") {
        busqueda("");
    }

    emit("update:modelValue", normalized);
    emit("changed", normalized);
};

const buscarOpciones = ({ query }) => {
    const normalized = normalizarCodigoPostal(query);

    if (normalized.length === 5) {
        busqueda(normalized);
    }
};

const confirmarCodigo = async ({ value }) => {
    const selectedCode = normalizarCodigoPostal(value);
    emit("update:modelValue", selectedCode);

    let resultados =
        busquedaCodigo.value === selectedCode ? busquedaData.value : null;

    if (!resultados?.length) {
        resultados = await busqueda(selectedCode);
    }

    const contexto = crearContextoCodigoPostal(selectedCode, resultados ?? []);

    if (contexto) {
        emit("confirmed", contexto);
    }
};

onMounted(() => {
    const input = autocomplete.value?.$el?.querySelector("input");

    input?.setAttribute("maxlength", "5");
    input?.setAttribute("inputmode", "numeric");
});
</script>

<template>
    <AutoComplete
        ref="autocomplete"
        :input-id="inputId"
        :name="inputName"
        :model-value="modelValue"
        :suggestions="opciones"
        option-label="codigo"
        :disabled="disabled"
        :invalid="invalid"
        :loading="loading"
        :auto-option-focus="true"
        :show-empty-message="mostrarMensajeVacio"
        empty-search-message="Sin códigos postales"
        fluid
        @complete="buscarOpciones"
        @update:model-value="actualizarCodigo"
        @option-select="confirmarCodigo"
    />
</template>
