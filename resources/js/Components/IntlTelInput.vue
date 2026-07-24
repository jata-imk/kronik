<script setup>
import intlTelInputDefaultOptions from "@config/intlTelInput";
import intlTelInput from "intl-tel-input";

import "intl-tel-input/build/css/intlTelInput.css";

import { InputText } from "primevue";
import { onMounted, onUnmounted, ref, watch } from "vue";

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    emitE164: {
        type: Boolean,
        default: false,
    },
    intlTelInputOptions: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits([
    "update:modelValue",
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
    if (!instance.value) {
        return;
    }

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
    if (!instance.value) {
        return;
    }

    const number = instance.value.getNumber() || "";
    const nationalNumber = input.value?.$el?.value ?? "";

    emit("update:modelValue", props.emitE164 ? number : nationalNumber);
    emit("changeNumber", {
        number,
        numberWithoutCountryCode: nationalNumber,
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

        if (props.modelValue) {
            instance.value.setNumber(props.modelValue);
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

watch(
    () => props.modelValue,
    (newValue) => {
        if (!instance.value) {
            return;
        }

        if (!newValue) {
            instance.value.setNumber("");
            return;
        }

        if (
            instance.value.getNumber() !== newValue &&
            input.value?.$el?.value !== newValue
        ) {
            instance.value.setNumber(newValue);
        }
    },
);

onUnmounted(() => instance.value?.destroy());

defineExpose({ instance, input });
</script>

<template>
    <InputText
        ref="input"
        type="tel"
        :disabled="disabled"
        :model-value="modelValue"
        @input="updateValue"
        @change="updateValue"
        @countrychange="updateCountry"
        v-bind="$attrs" />
</template>
