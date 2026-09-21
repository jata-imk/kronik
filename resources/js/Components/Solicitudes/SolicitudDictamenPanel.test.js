import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import Panel from "./SolicitudDictamenPanel.vue";

vi.mock("@inertiajs/vue3", () => ({ useForm: data => ({ ...data, errors: {}, processing: false }) }));
const registro = { id: 1, revision_actual: true, resultado: "favorable", actor: { name: "Analista" }, created_at: "2026-09-20T12:00:00Z", contenido: {} };
function render(extra = {}) {
    return mount(Panel, { props: { tipo: "evaluacion", solicitud: { estado: "en_revision", lock_version: 1 }, registros: [registro], puedeRegistrar: true, ...extra }, global: { stubs: {
        Button: { props: ["label"], template: '<button>{{ label }}</button>' }, Message: { template: '<div><slot /></div>' }, Select: true, InputText: true, Textarea: true,
    } } });
}
describe("Resumen del dictamen", () => {
    it("muestra el último resultado y abre una nueva captura solo bajo petición", async () => {
        const wrapper = render();
        expect(wrapper.find("form").exists()).toBe(false);
        expect(wrapper.text()).toContain("Favorable · Último de la revisión actual");
        await wrapper.get("button").trigger("click");
        expect(wrapper.find("form").exists()).toBe(true);
        expect(wrapper.text()).toContain("conserva el anterior como evidencia");
    });
    it("abre la captura cuando el dictamen pertenece a una revisión anterior", () => {
        expect(render({ registros: [{ ...registro, revision_actual: false }] }).find("form").exists()).toBe(true);
    });
    it("explica el envío previo y no permite capturar en borrador", () => {
        const wrapper = render({ solicitud: { estado: "borrador" }, registros: [] });
        expect(wrapper.find("form").exists()).toBe(false);
        expect(wrapper.text()).toContain("envía la solicitud a revisión");
    });
});
