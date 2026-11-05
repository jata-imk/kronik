import { shallowMount } from "@vue/test-utils";
import { reactive } from "vue";
import { describe, expect, it, vi } from "vitest";
import Index from "./Index.vue";

const { submit, state } = vi.hoisted(() => ({ submit: vi.fn(), state:{page:null} }));
vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" }, router: { get: vi.fn() },
    usePage: () => state.page,
    useForm: data => {
        let defaults = JSON.parse(JSON.stringify(data));
        const form = reactive({ ...data, errors: {}, processing: false });
        Object.assign(form, { cancel: vi.fn(), clearErrors: () => { form.errors = {}; },
            defaults: value => { defaults = value; }, reset: () => Object.assign(form, JSON.parse(JSON.stringify(defaults))), submit });
        return form;
    },
}));
vi.mock("@/Composables/useListadoServidor", () => ({ useListadoServidor: () => ({ visit: vi.fn() }) }));
vi.mock("primevue/useconfirm", () => ({ useConfirm: () => ({}) }));
vi.mock("primevue/usetoast", () => ({ useToast: () => ({ add: vi.fn() }) }));
const version = (id = 1) => ({ id, numero: id, estado: "activa", puede_editar: false, usos_count: 0, fiscalidad: null, periodicidades: [], comisiones: [], reglas: { metodos_amortizacion: [], permite_prepago_parcial: true } });
const product = selected => ({ id: 10, clave: "QA", nombre: `Producto ${selected.id}`, versiones: [selected], ultimaVersion: selected, versionSeleccionada: selected });
function render(selected = version(), modo = "consulta") {
    state.page=reactive({props:{auth:{permissions:{"update-productos-crediticios":true,"create-productos-crediticios":true}},listado:{}}});
    return shallowMount(Index, {
        props: { productoSeleccionado: product(selected), productos: [], modoVersion: modo, capacidadesCredito: {
            catalogo: { periodicidades: [], metodos_amortizacion: [], aplicaciones_prepago: [] },
            configuracion: { opciones: { periodicidades: [], metodos_amortizacion: [], aplicaciones_prepago: [], convenciones_interes: [] }, predeterminados: {} },
        } },
        global: { renderStubDefaultSlot: true, directives: { tooltip: {} }, mocks: { route: () => "/" }, stubs: {
            AppLayout: { template: '<main><slot name="card-content" /></main>' },
            Dialog: { props: ["visible", "header"], template: '<section v-if="visible"><h2>{{ header }}</h2><slot /></section>' },
            Button: { props: ["label", "type"], template: '<button :type="type || \'button\'">{{ label }}</button>' },
        } },
    });
}
describe("editor de producto", () => {
    it("consulta una versión protegida sin envío ni controles de mutación", async () => {
        submit.mockClear();
        const wrapper = render();
        expect(wrapper.text()).toContain("Consultar versión 1");
        expect(wrapper.text()).not.toContain("Guardar borrador");
        await wrapper.get("#product-editor form").trigger("submit");
        expect(submit).not.toHaveBeenCalled();
        await wrapper.setProps({ productoSeleccionado: product(version(2)) });
        expect(wrapper.text()).toContain("Consultar versión 2");
        expect(wrapper.text()).not.toContain("Consultar versión 1");
        await wrapper.setProps({ modoVersion: null });
        expect(wrapper.find("#product-editor form").exists()).toBe(false);
    });
    it("no borra errores ni captura ante una recarga de validación del mismo borrador", async () => {
        const draft = { ...version(), estado: "borrador", puede_editar: true, monto_minimo: 5000, monto_maximo: 10000, tasa_ordinaria_anual: 36, tasa_moratoria_anual: 72 };
        const wrapper = render(draft, "edicion");
        wrapper.vm.form.nombre = "Captura sin guardar";
        wrapper.vm.form.errors.nombre = "Revisa el nombre";
        await wrapper.setProps({ productoSeleccionado: product({ ...draft }) });
        expect(wrapper.vm.form.nombre).toBe("Captura sin guardar");
        expect(wrapper.vm.form.errors.nombre).toBe("Revisa el nombre");
        await wrapper.setProps({ productoSeleccionado: product({ ...draft, estado: "activa", puede_editar: false }) });
        expect(wrapper.text()).toContain("Consultar versión 1");
        expect(wrapper.text()).not.toContain("Guardar borrador");
    });
});

it("retira edición cuando solo se recargan permisos compartidos",async()=>{
 const draft={...version(),estado:"borrador",puede_editar:true,reglas:{metodos_amortizacion:[]}};
 const w=render(draft,"edicion");expect(w.text()).toContain("Guardar borrador");
 state.page.props.auth.permissions["update-productos-crediticios"]=false;
 await w.vm.$nextTick();expect(w.text()).toContain("Consultar versión");expect(w.text()).not.toContain("Guardar borrador");
});

it("cierra alta y cancela envío al retirar creación en recarga parcial",async()=>{
 const w=render();w.vm.openCreate();await w.vm.$nextTick();expect(w.vm.editorVisible).toBe(true);
 state.page.props.auth.permissions["create-productos-crediticios"]=false;await w.vm.$nextTick();
 expect(w.vm.editorVisible).toBe(false);submit.mockClear();w.vm.submit();expect(submit).not.toHaveBeenCalled();
});

it("la respuesta de una acción tras cerrar no reabre la consulta descartada",async()=>{
 const v=version();const w=render(v);w.vm.closeEditor();await w.vm.$nextTick();
 await w.setProps({productoSeleccionado:product({...v})});expect(w.vm.editorVisible).toBe(false);
 w.vm.requestVersion(v);await w.setProps({productoSeleccionado:product({...v})});expect(w.vm.editorVisible).toBe(true);
});
