import { mount, flushPromises } from "@vue/test-utils";
import { reactive, nextTick } from "vue";
import { describe, expect, it, vi } from "vitest";
import axios from "axios";
import Pago from "./Pago.vue";

vi.mock("axios", () => ({ default: { post: vi.fn() } }));
vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" },
    useForm: data => {
        const form = reactive({ ...data, errors: {}, processing: false, post: vi.fn(), clearErrors: vi.fn() });
        form.data = () => ({ ...form });
        return form;
    },
}));
vi.stubGlobal("route", name => name);
function render(overrides = {}) {
    return mount(Pago, { props: { credito: { id: 1, cliente: {}, sucursal: {} }, hoy: "2026-09-30", situacion: { resumen: null, error: null }, habilitado: true, ...overrides }, global: { mocks: { route: name => name }, stubs: {
        AppLayout: { template: '<main><slot name="card-content" /></main>' },
        Link: { template: '<a><slot /></a>' },
        Button: { props: ["label", "disabled"], template: '<button :disabled="disabled">{{ label }}</button>' },
        InputText: { props: ["modelValue"], emits: ["update:modelValue"], template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />' },
        InputNumber: true, Checkbox: true, Tag: true, DistribucionPago: true,
        Message: { template: '<div><slot /></div>' },
    } } });
}
const previa = { previa_hash: "abc", asignaciones: [], despues: { capital_insoluto: "500", exigible: "0" } };
describe("Revisión de pagos QA", () => {
    it("explica bloqueos sin permitir revisión con configuración cerrada", () => {
        const wrapper = render({ habilitado: false });
        expect(wrapper.text()).toContain("ORIGINACION_PAGOS_QA_HABILITADOS");
        expect(wrapper.get('button').attributes('disabled')).toBeDefined();
        wrapper.unmount();
    });
    it("invalida la distribución al cambiar la referencia", async () => {
        axios.post.mockResolvedValueOnce({ data: previa });
        const wrapper = render();
        await wrapper.get('form').trigger('submit');
        await flushPromises();
        expect(wrapper.text()).toContain("Distribución propuesta");
        await wrapper.get('#pago-referencia').setValue("CAMBIO-001");
        expect(wrapper.text()).not.toContain("Distribución propuesta");
        wrapper.unmount();
    });
    it("descarta una respuesta que llegó después de modificar la captura", async () => {
        let resolve;
        axios.post.mockImplementationOnce(() => new Promise(done => { resolve = done; }));
        const wrapper = render();
        await wrapper.get('form').trigger('submit');
        await wrapper.get('#pago-referencia').setValue("CAMBIO-002");
        resolve({ data: previa });
        await flushPromises();
        await nextTick();
        expect(wrapper.text()).not.toContain("Distribución propuesta");
        wrapper.unmount();
    });
});
