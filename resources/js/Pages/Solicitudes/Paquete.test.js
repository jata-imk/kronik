import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import Paquete from "./Paquete.vue";

vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" },
    router: { reload: vi.fn() },
    useForm: (data) => ({ ...data, errors: {}, processing: false, post: vi.fn() }),
}));
vi.stubGlobal("route", (name) => name);
function render(overrides = {}) {
    return mount(Paquete, {
        props: {
            solicitud: { id: 1, cliente_id: 2, cliente: "Cliente QA", sucursal: "Matriz", estado: "aprobada", lock_version: 4 },
            preparacion: { requisitos: [], puede_preparar: true }, actual: null,
            paquetes: { data: [], last_page: 1 }, plantillas: [], can: { preparar: true }, ...overrides,
        },
        global: { mocks: { route: (name) => name }, stubs: {
            AppLayout: { template: '<main><slot name="card-content" /></main>' },
            Button: { props: ["label", "disabled"], template: '<button :disabled="disabled">{{ label }}</button>' },
            Message: { template: '<div><slot /></div>' }, Tag: true,
            Drawer: true, PrivateDocumentViewer: true, DataTable: true, Column: true,
        } },
    });
}
describe("Paquete contractual QA", () => {
    it("distingue prueba de formalización y no asume fiscalidad cero", () => {
        const wrapper = render();
        expect(wrapper.text()).toContain("No definido · no equivale a tasa cero");
        expect(wrapper.text()).toContain("no formaliza el crédito");
        expect(wrapper.text()).toContain("Aún no hay paquetes");
        expect(wrapper.text()).toContain("Firma · próxima entrega");
        wrapper.unmount();
    });
    it("muestra el bloqueo con explicación y deshabilita la preparación", () => {
        const wrapper = render({ preparacion: { puede_preparar: false, requisitos: [{ clave: "vigencia_aprobacion", cumplido: false, mensaje: "Renueva la aprobación desde la solicitud." }] } });
        expect(wrapper.get("button").attributes("disabled")).toBeDefined();
        expect(wrapper.text()).toContain("Vigencia de aprobación");
        expect(wrapper.text()).toContain("Renueva la aprobación desde la solicitud.");
        wrapper.unmount();
    });
    it("no muestra preparación a lectores sin permiso", () => {
        const wrapper = render({ can: { preparar: false } });
        expect(wrapper.findAll("button").some((button) => button.text() === "Preparar paquete QA")).toBe(false);
        wrapper.unmount();
    });
});
