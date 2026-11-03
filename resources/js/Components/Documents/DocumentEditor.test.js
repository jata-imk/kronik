import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import DocumentEditor from "./DocumentEditor.vue";

describe("consulta de contenido documental", () => {
    it("deshabilita Quill, rechaza inserción y pegado y no emite cambios", async () => {
        const wrapper = mount(DocumentEditor, { props: { readonly: true, section: "contenido_html", modelValue: "<p>Original {{cliente.nombre_completo}}</p>" }, global: { stubs: { DocumentImageLibrary: true, Button: true } } });
        expect(wrapper.get(".ql-editor").attributes("contenteditable")).toBe("false");
        expect(wrapper.findComponent({ name: "DocumentImageLibrary" }).exists()).toBe(false);
        wrapper.vm.insertVariable({ clave: "empresa.rfc" });
        const event = new Event("paste", { bubbles: true, cancelable: true });
        wrapper.get(".ql-editor").element.dispatchEvent(event);
        expect(event.defaultPrevented).toBe(true);
        expect(wrapper.get(".ql-editor").text()).toBe("Original {{cliente.nombre_completo}}");
        await wrapper.setProps({ modelValue: "<p>Otra versión</p>" });
        expect(wrapper.get(".ql-editor").text()).toBe("Otra versión");
        expect(wrapper.emitted("update:modelValue")).toBeUndefined();
        await wrapper.setProps({ readonly: false });
        expect(wrapper.get(".ql-editor").attributes("contenteditable")).toBe("true");
        wrapper.unmount();
    });
});
