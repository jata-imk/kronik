import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import SolicitudAprobacion from "./SolicitudAprobacion.vue";

vi.mock("@inertiajs/vue3", () => ({ useForm: data => ({ ...data, errors: {}, processing: false, post: vi.fn() }) }));
const props = { solicitud: { id: 1, estado: "en_revision", lock_version: 0 }, puedeAprobar: true, estado: { puede_aprobar: false, requisitos: [{ clave: "sic", cumplido: false, mensaje: "Se requiere SIC integrado válido." }], politica: null } };
function render(extra = {}) {
    return mount(SolicitudAprobacion, { props: { ...props, ...extra }, global: { stubs: {
        Button: { props: ["label", "disabled"], template: '<button :disabled="disabled">{{ label }}</button>' }, Textarea: true, Message: true,
    } } });
}
describe("Requisitos de aprobación", () => {
    it("explica el bloqueo y deshabilita confirmación", () => {
        const wrapper = render();
        expect(wrapper.text()).toContain("Se requiere SIC integrado válido.");
        expect(wrapper.get("button").element.disabled).toBe(true);
    });
    it("no ofrece aprobación sin permiso", () => {
        expect(render({ puedeAprobar: false }).find("button").exists()).toBe(false);
    });
    it("habilita la confirmación solo con requisitos satisfechos", () => {
        const wrapper = render({ estado: { ...props.estado, puede_aprobar: true, requisitos: [{ clave: "sic", cumplido: true, mensaje: "No debe mostrarse como error" }] } });
        expect(wrapper.get("button").element.disabled).toBe(false);
        expect(wrapper.text()).not.toContain("No debe mostrarse como error");
    });
    it("no presenta aprobación como pendiente después de resolver", () => {
        expect(render({ solicitud: { ...props.solicitud, estado: "aprobada" } }).text()).toBe("");
    });
});
