import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import Panel from "./SolicitudFirmaPanel.vue";

vi.mock("@inertiajs/vue3", () => ({ Link: { template: "<a><slot /></a>" }, useForm: data => ({ ...data, errors: {}, post: vi.fn() }) }));
function render(overrides = {}) {
    return mount(Panel, { props: {
        solicitud: { id: 1, estado: "aprobada", lock_version: 5 }, actual: { id: 2 },
        firmas: { data: [], last_page: 1 }, requisitos: { permitido: true, requisitos: [] }, can: { recibirFirma: true },
        ...overrides,
    }, global: { stubs: {
        Button: { props: ["label", "disabled"], template: '<button :disabled="disabled">{{ label }}</button>' },
        Message: { template: '<p><slot /></p>' }, Tag: true, Drawer: true, PrivateDocumentViewer: true,
    } } });
}
describe("Firma autógrafa digitalizada", () => {
    it("explica que recibir no equivale a aprobar y bloquea requisitos pendientes", () => {
        const wrapper = render({ requisitos: { permitido: false, requisitos: [{ clave: "qa", cumplido: false, mensaje: "Solicita habilitación QA al administrador." }] } });
        expect(wrapper.text()).toContain("Cargarlo no lo aprueba");
        expect(wrapper.text()).toContain("Solicita habilitación QA");
        expect(wrapper.get("button").attributes("disabled")).toBeDefined();
    });
    it("no ofrece duplicar una recepción pendiente", () => {
        const wrapper = render({ pendiente: true });
        expect(wrapper.text()).toContain("No necesitas volver a subirla");
        expect(wrapper.findAll("button")).toHaveLength(0);
    });
    it("no ofrece recepción a lectores y distingue formalización QA de desembolso", () => {
        const wrapper = render({ can: {}, formalizacion: { id: 1 } });
        expect(wrapper.text()).toContain("Formalizada en QA");
        expect(wrapper.text()).toContain("No hay desembolso");
        expect(wrapper.findAll("button")).toHaveLength(0);
    });
    it("permite consultar pero no revisar una copia histórica", () => {
        const wrapper = render({ can: { revisarFirma: true }, firmas: { data: [{ id: 1, estado: "recibida", actual: false, fecha_firma: "2026-09-29" }] } });
        expect(wrapper.text()).toContain("Contrato histórico");
        expect(wrapper.text()).toContain("Ver copia firmada");
        expect(wrapper.text()).not.toContain("Revisar firma");
    });
});
