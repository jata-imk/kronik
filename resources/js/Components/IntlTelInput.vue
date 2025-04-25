<script setup>
import intlTelInputDefaultOptions from "@config/intlTelInput";
import intlTelInput from "intl-tel-input";

import "intl-tel-input/build/css/intlTelInput.css";

import { InputText } from "primevue";
import { onMounted, onUnmounted, ref, watch } from "vue";

const props = defineProps({
    intlTelInputOptions: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits([
    "changeNumber",
    "changeCountry",
    "changeValidity",
    "changeErrorCode",
]);

const input = ref();
const countryDropdown = ref();
const instance = ref();
const wasPreviouslyValid = ref(false);

const isValid = () => {
    if (instance.value) {
        return props.intlTelInputOptions.strictMode
            ? instance.value.isValidNumberPrecise()
            : instance.value.isValidNumber();
    }

    return null;
};

const updateValidity = () => {
    const isCurrentlyValid = isValid();

    if (wasPreviouslyValid.value !== isCurrentlyValid) {
        wasPreviouslyValid.value = isCurrentlyValid;

        emit("changeValidity", !!isCurrentlyValid);
        emit(
            "changeErrorCode",
            isCurrentlyValid ? null : instance.value.getValidationError(),
        );
    }
};

const updateValue = () => {
    emit("changeNumber", {
        number: instance.value._getFullNumber() ?? null,
        numberWithoutCountryCode: instance.value.getNumber() ?? null,
        isValid: isValid(),
    });
    updateValidity();
};

const updateCountry = () => {
    emit(
        "changeCountry",
        instance.value?.getSelectedCountryData() ?? {
            areaCodes: null,
            dialCode: null,
            iso2: null,
            name: null,
            nationalPrefix: null,
            nodeById: null,
            priority: null,
        },
    );
    updateValue();
    updateValidity();
};

onMounted(() => {
    if (input.value) {
        countryDropdown.value = input.value;
        instance.value = intlTelInput(input.value.$el, {
            ...intlTelInputDefaultOptions,
            ...props.intlTelInputOptions,
        });

        if (props.value) {
            instance.value.setNumber(props.value);
        }

        if (props.disabled) {
            instance.value.setDisabled(props.disabled);
        }
    }
});

watch(
    () => props.disabled,
    (newValue) => instance.value?.setDisabled(newValue),
);

onUnmounted(() => instance.value?.destroy());

defineExpose({ instance, input });
</script>

<template>
    <InputText
        ref="input"
        type="tel"
        @change="updateValue"
        @countrychange="updateCountry"
        v-bind="$attrs" />
</template>