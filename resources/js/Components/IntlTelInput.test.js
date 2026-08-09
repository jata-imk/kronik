import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import { defineComponent } from "vue";

const { initialization, intlTelInputMock } = vi.hoisted(() => {
    let resolveInitialization;

    return {
        initialization: {
            promise: new Promise((resolve) => {
                resolveInitialization = resolve;
            }),
            resolve: () => resolveInitialization(),
        },
        intlTelInputMock: vi.fn(),
    };
});

vi.mock("intl-tel-input", () => ({ default: intlTelInputMock }));
vi.mock("@config/intlTelInput", () => ({ default: {} }));
vi.mock("primevue", () => ({
    InputText: defineComponent({
        name: "InputText",
        inheritAttrs: false,
        props: {
            modelValue: { type: String, default: "" },
            disabled: { type: Boolean, default: false },
        },
        template:
            '<input :value="modelValue" :disabled="disabled" v-bind="$attrs" />',
    }),
}));

import IntlTelInput from "./IntlTelInput.vue";

describe("IntlTelInput", () => {
    it("no borra el teléfono durante la inicialización asíncrona", async () => {
        intlTelInputMock.mockImplementation((element) => ({
            promise: initialization.promise,
            setNumber(value) {
                element.value = value;
                element.dispatchEvent(new Event("input", { bubbles: true }));
            },
            getNumber: () => "",
            getSelectedCountryData: () => ({ dialCode: "52" }),
            isValidNumber: () => true,
            getValidationError: () => null,
            setDisabled: vi.fn(),
            destroy: vi.fn(),
        }));

        const wrapper = mount(IntlTelInput, {
            props: {
                modelValue: "+529341011054",
                emitE164: true,
            },
        });

        expect(wrapper.emitted("update:modelValue")).toBeUndefined();
        expect(wrapper.get("input").element.value).toBe("+529341011054");

        initialization.resolve();
        await initialization.promise;
        await wrapper.vm.$nextTick();

        expect(wrapper.emitted("update:modelValue")).toBeUndefined();
        expect(wrapper.get("input").element.value).toBe("+529341011054");
    });
});
