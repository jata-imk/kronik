import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import Desembolso from "./Desembolso.vue";
vi.mock("@inertiajs/vue3", () => ({ Link: { template: "<a><slot /></a>" }, useForm: data => ({ ...data, errors: {}, post: vi.fn() }) }));
vi.stubGlobal("route", name => name);
function render(overrides = {}) {
    return mount(Desembolso, { props: { solicitud: { id: 1, lock_version: 7, cliente: { primer_nombre: "QA" }, sucursal: { nombre: "Matriz" } },
        preparacion: { permitido: true, requisitos: [], resumen: { fecha: "2026-09-29", importe: "9400.00", monto: "10000.00", capital: "11000.00", retenido: "600.00", financiado: "1000.00", separado: "50.00" } }, puedeRegistrar: true, ...overrides },
        global: { mocks: { route: name => name }, stubs: { AppLayout: { template: '<main><slot name="card-content" /></main>' }, Link: { template: '<a><slot /></a>' }, Drawer: true, Tag: true,
            Button: { props: ["label", "disabled"], template: '<button :disabled="disabled">{{ label }}</button>' }, Message: { template: '<p><slot /></p>' } } } });
}
describe("Desembolso QA", () => {
    it("distingue importe transferido capital financiado y cargos separados", () => {
        const wrapper = render();
        expect(wrapper.text()).toContain("9,400.00");
        expect(wrapper.text()).toContain("11,000.00");
        expect(wrapper.text()).toContain("ni se marcan pagados aquí");
        expect(wrapper.text()).toContain("No envía dinero al banco");
    });
    it("muestra bloqueos y no permite confirmar antes de tiempo", () => {
        const wrapper = render({ preparacion: { permitido: false, requisitos: [{ clave: "fecha", cumplido: false, mensaje: "Espera a la fecha firmada." }], resumen: null } });
        expect(wrapper.text()).toContain("Espera a la fecha firmada");
        expect(wrapper.get("button").attributes("disabled")).toBeDefined();
    });
    it("orienta al lector sin permiso sin ofrecer una acción", () => {
        const wrapper = render({ puedeRegistrar: false });
        expect(wrapper.findAll("button")).toHaveLength(0);
        expect(wrapper.text()).toContain("Solicita el permiso de desembolso");
    });
});
