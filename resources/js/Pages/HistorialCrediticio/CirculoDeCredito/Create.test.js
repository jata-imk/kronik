import { mount } from "@vue/test-utils";
import { reactive, nextTick } from "vue";
import { expect, it, vi } from "vitest";
import Create from "./Create.vue";

const state = vi.hoisted(() => ({ form: null }));
vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" }, router: { get: vi.fn() },
    useForm: data => (state.form = reactive({ ...data, errors: {}, post: vi.fn(), reset: vi.fn(), clearErrors: vi.fn() })),
}));
vi.mock("@sakai-vue/layout/AppLayout.vue", () => ({ default: { template: "<main><slot name='card-content' /></main>" } }));

it("cambia el escenario por servicio y exige reconfirmar sin conservar una selección incompatible", async () => {
    const wrapper = mount(Create, {
        props: { cliente: { id: 1, primer_nombre: "Ana" }, habilitado: true, consentimientos: [], revisiones: [],
            servicios: [], escenariosQa: {
                fico_score_v2: [{ clave: "cdc_fico_score_v2_01", nombre: "FICO 720" }],
                fintech: [{ clave: "cdc_fintech_07", nombre: "Fintech 404" }],
            } },
        global: { mocks: { route: name => name }, directives: { "empresa-operacion": {} },
            stubs: { PageHeader: true, Select: true, Checkbox: true, Button: true, Message: true, Tag: true } },
    });
    state.form.api = "fico_score_v2";
    await nextTick();
    expect(state.form.escenario_qa).toBe("cdc_fico_score_v2_01");
    state.form.confirmacion_qa = true;
    const key = state.form.idempotency_key;
    state.form.api = "fintech";
    await nextTick();
    expect(state.form.escenario_qa).toBe("cdc_fintech_07");
    expect(state.form.confirmacion_qa).toBe(false);
    expect(state.form.idempotency_key).not.toBe(key);
    expect(wrapper.text()).toContain("el expediente del cliente se conserva");
});
