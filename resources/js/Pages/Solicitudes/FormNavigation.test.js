import { mount } from "@vue/test-utils";
import { nextTick } from "vue";
import { afterEach, expect, it } from "vitest";
import Form from "./Form.vue";
import { forgetUnsavedChanges, hasUnsavedChanges } from "@/Utils/unsavedChanges";

let wrapper;
afterEach(() => { wrapper?.unmount(); forgetUnsavedChanges(); });

it("protege la selección de cliente y reconoce el cliente precargado como base", async () => {
    const client = { id: 5, primer_nombre: "Ana", apellido_paterno: "Prueba" };
    wrapper = mount(Form, {
        props: { capacidadesCredito: {}, productos: [], clienteInicial: client },
        global: {
            mocks: { route: () => "/" },
            directives: { "empresa-operacion": {} },
            stubs: {
                AppLayout: { template: '<main><slot name="card-content" /></main>' },
                Link: true, Message: true, Button: true, InputNumber: true,
                InputText: true, Textarea: true, Select: true, PageHeader: true,
                AutoComplete: { name: "AutoComplete", props: ["modelValue"], emits: ["update:modelValue"], template: "<input />" },
            },
        },
    });
    await nextTick();
    expect(hasUnsavedChanges()).toBe(false);
    wrapper.findComponent({ name: "AutoComplete" }).vm.$emit("update:modelValue", { ...client, id: 6 });
    await nextTick();
    await nextTick();
    expect(hasUnsavedChanges()).toBe(true);
    wrapper.findComponent({ name: "AutoComplete" }).vm.$emit("update:modelValue", client);
    await nextTick();
    await nextTick();
    expect(hasUnsavedChanges()).toBe(false);
});
