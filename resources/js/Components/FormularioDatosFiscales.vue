<script setup>
import { ref } from "vue";

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
});

const tiposPersona = props.initialLoad?.tiposPersona ?? [];
const regimenesFiscales = props.initialLoad?.regimenesFiscales ?? [];
const regimenesFiscalesFiltered = ref(regimenesFiscales);

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
</script>

<template>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1" for="tipo_persona">Tipo de persona</label>
            <Select
                id="tipo_persona" name="tipo_persona"
                v-model="form.tipo_persona" :disabled="readOnly"
                :options="tiposPersona"
                optionLabel="label"
                optionValue="value"
                @change="onChangeTipoPersona"
                fluid :invalid="!!formErrors.tipo_persona" />
            <Message v-if="formErrors.tipo_persona" severity="error" size="small">
                {{ formErrors.tipo_persona }}
            </Message>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1" for="regimen_fiscal_id">Regimen fiscal</label>
            <Select
                :disabled="!form.tipo_persona || readOnly"
                id="regimen_fiscal_id" name="regimen_fiscal_id"
                v-model="form.regimen_fiscal_id"
                :options="regimenesFiscalesFiltered"
                optionLabel="descripcion"
                optionValue="id"
                fluid :invalid="!!formErrors.regimen_fiscal_id" />
            <Message v-if="formErrors.regimen_fiscal_id" severity="error" size="small">
                {{ formErrors.regimen_fiscal_id }}
            </Message>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1" for="curp">CURP</label>
            <InputText
                id="curp" name="curp"
                v-model="form.curp" :disabled="readOnly"
                fluid :invalid="!!formErrors.curp" />
            <Message v-if="formErrors.curp" severity="error" size="small">
                {{ formErrors.curp }}
            </Message>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1" for="rfc">RFC</label>
            <InputText
                id="rfc" name="rfc"
                v-model="form.rfc" :disabled="readOnly"
                fluid :invalid="!!formErrors.rfc" />
            <Message v-if="formErrors.rfc" severity="error" size="small">
                {{ formErrors.rfc }}
            </Message>
        </div>

        <div v-if="form.tipo_persona === 'moral'">
            <label class="block text-sm font-medium mb-1" for="razon_social">Razón social</label>
            <InputText
                id="razon_social" name="razon_social"
                v-model="form.razon_social" :disabled="readOnly"
                fluid :invalid="!!formErrors.razon_social" />
            <Message v-if="formErrors.razon_social" severity="error" size="small">
                {{ formErrors.razon_social }}
            </Message>
        </div>
    </div>
</template>
