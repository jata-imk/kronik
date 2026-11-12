import { mount } from "@vue/test-utils";
import { beforeEach, expect, it, vi } from "vitest";
import CapturaHistoricaPanel from "./CapturaHistoricaPanel.vue";

const { post } = vi.hoisted(() => ({ post: vi.fn() }));
vi.mock("@inertiajs/vue3", async () => {
    const { reactive } = await import("vue");
    return { useForm: (initial) => reactive({ ...initial, errors: {}, processing: false, post,
        cancel() {}, clearErrors() { this.errors = {}; }, reset() { Object.assign(this, initial); } }) };
});
beforeEach(() => { post.mockClear(); vi.stubGlobal("route", (name, id) => `${name}/${id}`); });
const checkbox = { props: ["modelValue"], emits: ["update:modelValue"], template: '<input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" />' };
function panel(puedeCompletar = false) {
    return mount(CapturaHistoricaPanel, { props: { creditoId: 7, captura: { estado: "pendiente" }, puedeCompletar },
        global: { stubs: { Checkbox: checkbox, Button: { props: ["disabled", "label"], template: '<button :disabled="disabled">{{ label }}</button>' }, Message: { template: "<p><slot /></p>" } } } });
}
it("el lector conoce la provisionalidad sin formulario ni mutaciones", async () => {
    const wrapper = panel();
    expect(wrapper.text()).toContain("Captura histórica pendiente");
    expect(wrapper.find("form").exists()).toBe(false);
    expect(post).not.toHaveBeenCalled();
    wrapper.unmount();
});
it("exige ambas confirmaciones incluso al enviar por teclado y limpia al cambiar recurso o permiso", async () => {
    const wrapper = panel(true);
    await wrapper.get("form").trigger("submit");
    expect(post).not.toHaveBeenCalled();
    for (const input of wrapper.findAll("input")) await input.setValue(true);
    await wrapper.get("form").trigger("submit");
    expect(post).toHaveBeenCalledExactlyOnceWith("creditos.captura-historica.completar/7", { preserveScroll: true });
    await wrapper.setProps({ creditoId: 8 });
    expect(wrapper.findAll("input").every((input) => !input.element.checked)).toBe(true);
    await wrapper.get("form").trigger("submit");
    expect(post).toHaveBeenCalledTimes(1);
    await wrapper.setProps({ puedeCompletar: false });
    expect(wrapper.find("form").exists()).toBe(false);
    wrapper.unmount();
});
it("una recarga parcial con captura completa retira la mutación y conserva la explicación", async () => {
    const wrapper = panel(true);
    await wrapper.setProps({ captura: { estado: "completa", completada_en: "2026-11-11T12:00:00Z" } });
    expect(wrapper.find("form").exists()).toBe(false);
    expect(wrapper.text()).toContain("Captura histórica completada");
    expect(post).not.toHaveBeenCalled();
    wrapper.unmount();
});
