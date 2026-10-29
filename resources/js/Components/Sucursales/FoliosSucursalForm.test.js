import { mount } from "@vue/test-utils";
import { reactive } from "vue";
import { describe, expect, it, vi } from "vitest";
import FoliosSucursalForm from "./FoliosSucursalForm.vue";

const { guardar, recargar } = vi.hoisted(() => ({ guardar: vi.fn(), recargar: vi.fn() }));
vi.mock("@inertiajs/vue3", () => ({
    useForm: (data) => reactive({ ...data, errors: {}, processing: false, put: guardar, defaults: vi.fn(), reset: vi.fn(), clearErrors: vi.fn() }),
    router: { reload: recargar },
}));
vi.stubGlobal("route", (name, id) => `${name}/${id}`);

function render(editable = true) {
    return mount(FoliosSucursalForm, {
        props: { editable, sucursal: { id: 7, folios: { prefijo_folio: "MTZ", version: 4,
            siguientes: { solicitud: 124, contrato: 45, credito: 9, recibo: 16 } } } },
        global: { stubs: {
            FieldHelp: { props: ["title", "description"], template: '<span>{{ title }} {{ description }}</span>' },
            InputText: { props: ["modelValue", "disabled"], emits: ["update:modelValue"], template: '<input :value="modelValue" :disabled="disabled" @input="$emit(\'update:modelValue\', $event.target.value)" />' },
            InputNumber: { props: ["modelValue", "inputId", "disabled"], emits: ["update:modelValue", "input"], template: '<input :id="inputId" type="number" :value="modelValue" :disabled="disabled" @input="$emit(\'input\', { value: Number($event.target.value) })" />' },
            Button: { props: ["label"], template: '<button>{{ label }}</button>' },
            Message: { template: '<p><slot /></p>' },
        } },
    });
}

describe("configuración de folios por sucursal", () => {
    it("actualiza la vista previa sin reservar y usa el guardado específico de numeración", async () => {
        guardar.mockClear();
        const wrapper = render();
        expect(wrapper.text()).toContain("MTZ-SOL-000124");
        await wrapper.get("#folio-prefijo").setValue("NTE");
        await wrapper.get("#folio-credito").setValue(42);
        expect(wrapper.text()).toContain("NTE-CRE-000042");
        expect(guardar).not.toHaveBeenCalled();
        await wrapper.get("form").trigger("submit");
        expect(guardar).toHaveBeenCalledWith("admin.sucursales.folios.update/7", expect.objectContaining({ preserveScroll: true }));
        expect(wrapper.props("sucursal").folios.siguientes.credito).toBe(9);
    });

    it("permite consultar sin ofrecer edición a quien solo tiene lectura", () => {
        const wrapper = render(false);
        expect(wrapper.findAll("input").every((input) => input.element.disabled)).toBe(true);
        expect(wrapper.find("button").exists()).toBe(false);
        expect(wrapper.text()).toContain("MTZ-CTO-000045");
    });
});
