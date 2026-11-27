import { mount } from "@vue/test-utils";
import { expect, it } from "vitest";
import PlainTextTemplateEditor from "./PlainTextTemplateEditor.vue";
it("inserta en la selección y protege consulta frente a comandos internos", async () => {
    const w = mount(PlainTextTemplateEditor, { props: { modelValue: "Hola mundo", readonly: false } });
    const input = w.get("textarea");
    input.element.setSelectionRange(5, 10);
    await input.trigger("select");
    await w.vm.insertVariable({ clave: "nombre" });
    expect(w.emitted("update:modelValue")[0]).toEqual(["Hola {nombre}"]);
    await w.setProps({ readonly: true, modelValue: "Histórica" });
    await w.vm.insertVariable({ clave: "nombre" });
    await input.setValue("Alterada");
    expect(w.emitted("update:modelValue")).toHaveLength(1);
    const paste = new Event("paste", { cancelable: true, bubbles: true });
    input.element.dispatchEvent(paste);
    expect(paste.defaultPrevented).toBe(true);
});
