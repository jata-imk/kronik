import { shallowMount } from "@vue/test-utils";
import { reactive, ref } from "vue";
import { expect, it, vi } from "vitest";
import FormCliente from "./FormCliente.vue";

const state = vi.hoisted(() => ({ page: null, post: vi.fn(), patch: vi.fn() }));
vi.mock("@inertiajs/vue3", () => ({
    usePage: () => state.page,
    useForm: data => reactive({ ...data, errors: {}, processing: false, transform: vi.fn(), post: state.post, patch: state.patch }),
}));
vi.mock("primevue/usetoast", () => ({ useToast: () => ({ add: vi.fn() }) }));
vi.mock("@components/MapLibre/MapLibreMap.vue", () => ({ default: { template: "<div />" } }));
vi.mock("@/Composables/MapLibre/useDireccionMapConnector", () => ({ useDireccionMapConnector: vi.fn() }));
vi.mock("@/Composables/useTelefonoInternacional", () => ({ useTelefonoInternacional: () => ({ telefonoInternacional: ref(""), onChangeNumber: vi.fn() }) }));

it("consulta no envía el formulario por teclado y reevalúa el modo ante recargas", async () => {
    state.post.mockClear();
    state.patch.mockClear();
    state.page = reactive({ props: { readOnly: true, cliente: { id: 1, direcciones: [], datos_fiscales: { rfc: "XAXX010101000" } },
        paises: [], sexos: [], tiposPersona: [], regimenesFiscales: [], rfcsGenericosPermitidos: ["XAXX010101000", "XEXX010101000"] } });
    const w = shallowMount(FormCliente, { global: { mocks: { route: () => "/clientes" }, directives: { "empresa-operacion": {} } } });
    await w.get("form").trigger("submit");
    expect(state.post).not.toHaveBeenCalled();
    expect(state.patch).not.toHaveBeenCalled();
    state.page.props.readOnly = false;
    await w.vm.$nextTick();
    await w.get("form").trigger("submit");
    expect(state.post).toHaveBeenCalledTimes(1);
    state.page.props.readOnly = true;
    await w.vm.$nextTick();
    await w.get("form").trigger("submit");
    expect(state.post).toHaveBeenCalledTimes(1);
});
