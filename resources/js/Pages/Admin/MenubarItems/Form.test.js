import { flushPromises, shallowMount } from "@vue/test-utils";
import { reactive } from "vue";
import { beforeEach, expect, it, vi } from "vitest";
import Form from "./Form.vue";
const api = vi.hoisted(() => ({ post: vi.fn(), put: vi.fn(), get: vi.fn() }));
vi.mock("axios", () => ({ default: { get: api.get, isCancel: error => error?.code === "ERR_CANCELED" } }));
vi.mock("primevue/usetoast", () => ({ useToast: () => ({ add: vi.fn() }) }));
vi.mock("@inertiajs/vue3", () => ({
    useForm: data => {
        const initial = JSON.stringify(data);
        const form = reactive({ ...data, errors: {}, processing: false, post: api.post, put: api.put, cancel: vi.fn() });
        Object.defineProperty(form, "isDirty", { get: () => JSON.stringify(Object.fromEntries(Object.keys(data).map(key => [key, form[key]]))) !== initial });
        return form;
    },
}));
const catalogo = {
    acciones: [{ key: "clientes.show", label: "Información del cliente", group: "Clientes", required: ["cliente"], params: { cliente: "{cliente}" } }],
    pantallas: [
        { key: "clientes.index", label: "Clientes · Listado", module_id: 1 },
        { key: "clientes.edit", label: "Clientes · Editar", module_id: 1 },
        { key: "productos-crediticios.index", label: "Productos · Listado", module_id: 2 },
    ],
    grupos: [{ id: 5, label: "Cliente", parent_id: null }, { id: 6, label: "Documentos", parent_id: 5 }],
};
const global = { mocks: { route: name => name }, stubs: { Dialog: { template: "<section><slot/><slot name='footer'/></section>" } } };
beforeEach(() => { vi.clearAllMocks(); window.route = name => name; });
it("agregado rápido selecciona la pantalla exacta y parámetros del destino", async () => {
    const wrapper = shallowMount(Form, { props: { catalogo, initialScreen: "clientes.edit" }, global });
    await flushPromises();
    expect(wrapper.vm.selectedScreens).toEqual(["clientes.edit"]);
    wrapper.vm.selectedAction = "clientes.show";
    expect(wrapper.vm.form.value).toBe("clientes.show");
    expect(JSON.parse(wrapper.vm.form.params)).toEqual({ cliente: "{cliente}" });
    expect(wrapper.vm.form.modules).toEqual({ 1: { routes: ["clientes.edit"] } });
    wrapper.vm.selectedScreens = ["productos-crediticios.index"];
    await flushPromises();
    expect(wrapper.vm.form.modules).toEqual({ 2: { routes: ["productos-crediticios.index"] } });
    expect(JSON.parse(wrapper.vm.form.params)).toEqual({ cliente: "{cliente}" });
    wrapper.unmount();
});
it("retirar todas las pantallas envía una lista vacía y guardar es explícito", async () => {
    const item = { id: 9, label: "Guardada", type: "route:name", value: "clientes.show", modules: [{ id: 1, menubar_item_module: { routes: ["clientes.edit"] } }] };
    const wrapper = shallowMount(Form, { props: { catalogo, item }, global });
    wrapper.vm.selectedScreens = [];
    await flushPromises();
    expect(wrapper.vm.form.modules).toEqual({});
    expect(api.put).not.toHaveBeenCalled();
    await wrapper.get("form").trigger("submit");
    expect(api.put).toHaveBeenCalledOnce();
    expect(api.post).not.toHaveBeenCalled();
    wrapper.unmount();
});
it("cerrar una edición sucia exige descarte y consulta nunca envía cambios", async () => {
    const wrapper = shallowMount(Form, { props: { catalogo }, global });
    wrapper.vm.form.label = "Sin guardar";
    wrapper.vm.requestClose();
    expect(wrapper.emitted("close")).toBeUndefined();
    expect(wrapper.find('button-stub[label="Descartar cambios"]').exists()).toBe(true);
    await wrapper.setProps({ readonly: true });
    await wrapper.get("form").trigger("submit");
    expect(api.post).not.toHaveBeenCalled();
    wrapper.vm.requestClose();
    expect(wrapper.emitted("close")).toHaveLength(1);
    wrapper.unmount();
});
it("consulta conserva configuración avanzada y no carga selectores de captura", async () => {
    const item = { id: 1, label: "Histórica", type: "route:dynamic", value: '[{"condition_type":"legacy"}]', params: { cliente: "{cliente}" }, modules: [{ id: 1, menubar_item_module: { routes: ["clientes.legacy"] } }] };
    const original = JSON.stringify(item);
    const wrapper = shallowMount(Form, { props: { readonly: true, item }, global });
    await flushPromises();
    expect(api.get).not.toHaveBeenCalled();
    expect(wrapper.vm.form.value).toBe(item.value);
    expect(wrapper.vm.form.modules[1].routes).toEqual(["clientes.legacy"]);
    expect(JSON.stringify(item)).toBe(original);
    wrapper.unmount();
});
