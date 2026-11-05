import { shallowMount } from "@vue/test-utils";
import { reactive } from "vue";
import { describe, expect, it, vi } from "vitest";
import Page from "./PoliticaOriginacion.vue";
const { post, state } = vi.hoisted(() => ({ post: vi.fn(), state:{page:null} }));
vi.mock("@inertiajs/vue3", () => ({
    usePage: () => state.page, Link: { template: "<a><slot /></a>" }, router: { get: vi.fn() },
    useForm: data => {
        let defaults = data;
        const f = reactive({ ...data, errors: {} });
        Object.assign(f, { cancel() {}, defaults: value => { defaults = value; }, reset: () => Object.assign(f, JSON.parse(JSON.stringify(defaults))), clearErrors: () => { f.errors = {}; }, transform: () => ({ post }) });
        return f;
    },
}));
const policy = numero => ({ id: numero, numero, condiciones: { modalidad: "individual", sic: "manual_permitido", monto_maximo: "10000.00", vigencia_dias: numero, referencia_validacion: "Referencia histórica " + numero, criterio_capacidad: "Criterio humano " + numero, documentos: ["ine"] } });
function render(puedeCrear = false) {
    state.page=reactive({props:{auth:{permissions:{"manage-origination-productos-crediticios":true,"read-productos-crediticios":true}}}});
    return shallowMount(Page, { props: { version: { id: 10, numero: 2, producto: { id: 3, nombre: "Producto" } }, politica: policy(3), politicaSeleccionada: policy(1), historial: { data: [policy(1), policy(3)], current_page: 1, per_page: 10, total: 2 }, tiposDocumento: [], puedeCrear, modoPolitica: "consulta" }, global: { renderStubDefaultSlot: true, mocks: { route: () => "/" }, stubs: {
        AppLayout: { template: '<main><slot name="card-content" /></main>' },
        Button: { props: ["label"], template: '<button type="button">{{ label }}</button>' },
    } } });
}
describe("consulta y creación de políticas", () => {
    it("consulta la seleccionada sin sustituirla por la actual ni enviar", async () => {
        post.mockClear();
        const w = render();
        expect(w.text()).toContain("Consulta · política 1 · versión de producto 2");
        expect(w.vm.form.referencia_validacion).toBe("Referencia histórica 1");
        expect(w.text()).not.toContain("Guardar nueva versión de política");
        await w.get("form").trigger("submit");
        expect(post).not.toHaveBeenCalled();
        await w.setProps({ politicaSeleccionada: policy(3) });
        expect(w.vm.form.referencia_validacion).toBe("Referencia histórica 3");
    });
    it("crea a partir de la actual y exige una referencia y confirmación nuevas", async () => {
        const w = render(true);
        await w.findAll("button").find(b => b.text() === "Nueva versión de política").trigger("click");
        expect(w.vm.form.version_anterior).toBe(3);
        expect(w.vm.form.vigencia_dias).toBe(3);
        expect(w.vm.form.referencia_validacion).toBe("");
        expect(w.vm.form.confirmacion).toBe(false);
        await w.setProps({ puedeCrear: false });
        expect(w.text()).not.toContain("Guardar nueva versión de política");
    });
});

it("política descarta captura cuando se retira gestión en props compartidas",async()=>{
    const w=render(true);
    await w.findAll("button").find(b=>b.text()==="Nueva versión de política").trigger("click");
    w.vm.form.referencia_validacion="Captura pendiente";
    state.page.props.auth.permissions["manage-origination-productos-crediticios"]=false;
    await w.vm.$nextTick();
    expect(w.text()).not.toContain("Guardar nueva versión de política");
    expect(w.vm.form.referencia_validacion).toBe("Referencia histórica 1");
});

it("política requiere lectura vigente además de gestión",async()=>{
    const w=render(true);await w.findAll("button").find(b=>b.text()==="Nueva versión de política").trigger("click");
    state.page.props.auth.permissions["read-productos-crediticios"]=false;await w.vm.$nextTick();
    expect(w.text()).not.toContain("Guardar nueva versión de política");
    post.mockClear();await w.get("form").trigger("submit");expect(post).not.toHaveBeenCalled();
});
