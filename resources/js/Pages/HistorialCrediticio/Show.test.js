import { mount } from "@vue/test-utils";
import { expect, it, vi } from "vitest";
import Show from "./Show.vue";

vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" }, router: { reload: vi.fn() },
    useForm: data => ({ ...data, errors: {}, processing: false }),
}));

it("presenta nombres y ejecución del sistema sin IDs de actor ni controles de edición", () => {
    const wrapper = mount(Show, {
        props: {
            consulta: { id: 1, estado_operacion: "completada", ambiente: "qa", cliente: { id: 1, primer_nombre: "Ana" } },
            resultado: { estado: "sin_score", scores: [], secciones: {} },
            eventos: [
                { id: 1, estado: "preparada", actor: { name: "José" }, automatico: false },
                { id: 2, estado: "completada", actor: { name: "José" }, automatico: true },
                { id: 3, estado: "incierta", actor: null, automatico: true },
                { id: 4, estado: "conciliada_sin_resultado", actor: null, automatico: false },
            ],
            puedeConciliar: false,
        },
        global: { mocks: { route: name => name }, stubs: {
            AppLayout: { template: "<main><slot name='card-content' /></main>" },
            PageHeader: true, ResumenSic: true, SerieFinanciera: true, Message: { template: "<div><slot /></div>" }, Tag: true,
        } },
    });
    expect(wrapper.text()).toContain("Preparada · José");
    expect(wrapper.text()).toContain("Completada · Sistema · solicitado por José");
    expect(wrapper.text()).toContain("Resultado incierto · Sistema");
    expect(wrapper.text()).toContain("Conciliada sin resultado · Usuario no disponible");
    expect(wrapper.text()).not.toContain("actor #");
    expect(wrapper.find("form").exists()).toBe(false);
});
