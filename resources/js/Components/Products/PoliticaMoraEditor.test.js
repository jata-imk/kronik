import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import PoliticaMoraEditor from "./PoliticaMoraEditor.vue";

const render = (props) => mount(PoliticaMoraEditor, { props, global: { stubs: {
    Button: { props: ["label"], template: '<button>{{ label }}</button>' },
    Select: true, Message: { template: '<div><slot /></div>' },
} } });

describe("Política de atraso", () => {
    it("no asigna modalidades silenciosamente a una versión histórica", async () => {
        const wrapper = render({ modelValue: null });
        expect(wrapper.text()).toContain("sin definir");
        expect(wrapper.emitted("update:modelValue")).toBeUndefined();
        await wrapper.get("button").trigger("click");
        expect(wrapper.emitted("update:modelValue")[0][0]).toEqual({ gracia: "efectiva", intereses: null });
    });
    it("explica la sustitución y muestra errores sin ocultar el campo", () => {
        const wrapper = render({ modelValue: { gracia: "retroactiva", intereses: "sustituye" }, errors: { "version.politica_mora.gracia": "Revisa la gracia" } });
        expect(wrapper.text()).toContain("no se cobran ambos intereses");
        expect(wrapper.text()).toContain("no se admitirán abonos parciales");
        expect(wrapper.text()).toContain("Revisa la gracia");
    });
    it("consulta inmutable sin controles de edición", () => {
        const wrapper = render({ modelValue: { gracia: "efectiva", intereses: "ambos" }, readonly: true });
        expect(wrapper.text()).toContain("A · Gracia efectiva");
        expect(wrapper.text()).toContain("Ambos intereses");
        expect(wrapper.text()).not.toContain("no se admitirán abonos parciales");
        expect(wrapper.findAll("button")).toHaveLength(0);
        expect(wrapper.findAll("select-stub")).toHaveLength(0);
    });
});
