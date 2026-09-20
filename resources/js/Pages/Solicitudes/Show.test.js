import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import Show from "./Show.vue";

vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" },
    router: { reload: vi.fn() },
    useForm: (data) => ({ ...data, errors: {}, processing: false, post: vi.fn(), patch: vi.fn() }),
}));
vi.stubGlobal("route", (name) => name);
const layout = { template: '<main><slot name="card-header" /><slot name="card-content" /></main>' };
const button = { props: ["label"], template: '<button>{{ label }}</button>' };
const passthrough = { template: '<div><slot /></div>' };
const solicitud = {
    id: 1, lock_version: 0, estado: "borrador", cliente_id: 5,
    cliente: { primer_nombre: "Ana", apellido_paterno: "Prueba" },
    sucursal: { nombre: "Matriz" }, responsable: { name: "Analista" },
    producto_version_id: null, monto: null, plazo: null, periodicidad: null,
    metodo: null, destino: null, fecha_estimada: null, eventos: [],
};
function render(overrides = {}, can = { update: true, assign: true, sic: true }) {
    return mount(Show, {
        props: { solicitud: { ...solicitud, ...overrides }, can, responsables: [] },
        global: { mocks: { route: (name) => name }, stubs: { AppLayout: layout, Button: button, Message: passthrough, Select: true, DataTable: true, Column: true } },
    });
}

describe("Detalle de solicitud", () => {
    it("orienta la captura sin representar aprobación", () => {
        const wrapper = render();
        expect(wrapper.text()).toContain("Captura: 0/7 datos");
        expect(wrapper.text()).toContain("No representa avance de aprobación");
        expect(wrapper.text()).toContain("Enviar a revisión");
        expect(wrapper.text()).toContain("Expediente y documentos del cliente");
    });
    it("oculta acciones no autorizadas", () => {
        const wrapper = render({}, { update: false, assign: false, sic: false });
        expect(wrapper.text()).not.toContain("Enviar a revisión");
        expect(wrapper.text()).not.toContain("Asignar responsable");
        expect(wrapper.text()).not.toContain("Historial SIC");
    });
    it("no ofrece editar o reenviar condiciones ya enviadas", () => {
        const wrapper = render({ estado: "en_revision" });
        expect(wrapper.text()).toContain("En revisión");
        expect(wrapper.text()).not.toContain("Enviar a revisión");
        expect(wrapper.text()).not.toContain("Completar borrador");
        expect(wrapper.text()).toContain("no hay aprobación ni autorización de desembolso");
    });
    it("orienta la corrección de una devolución", () => {
        const wrapper = render({ estado: "devuelta" });
        expect(wrapper.text()).toContain("Devuelta para corrección");
        expect(wrapper.text()).toContain("Corregir solicitud");
        expect(wrapper.text()).toContain("Enviar a revisión");
    });
    it("oculta mutaciones en una solicitud cerrada incluso con permisos", () => {
        const wrapper = render({ estado: "rechazada" }, { update: true, assign: true, review: true, cancel: true });
        expect(wrapper.text()).toContain("Rechazada");
        expect(wrapper.text()).not.toContain("Enviar a revisión");
        expect(wrapper.text()).not.toContain("Asignar responsable");
        expect(wrapper.text()).not.toContain("Devolución o cierre");
    });
});
