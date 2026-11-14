import { mount } from "@vue/test-utils";
import { expect, it, vi } from "vitest";
import Show from "./Show.vue";

vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" },
    router: { get: vi.fn() },
}));

it("consulta Bullet explica el interés y el atraso de obligaciones sin ofrecer mutaciones", async () => {
    const wrapper = mount(Show, {
        props: {
            credito: {
                id: 1,
                cliente: {},
                sucursal: {},
                responsable: {},
                capital_inicial: "12000.00",
            },
            desembolso: {
                importe: "12000.00",
                created_at: "2027-01-01T12:00:00Z",
            },
            cronograma: { version: 2, tabla: { tabla: [] } },
            cronogramas: [],
            movimientos: { data: [] },
            pagos: { data: [] },
            puedePagar: false,
            puedeAnticipar: false,
            puedeVerContrato: false,
            situacion: {
                error: null,
                resumen: {
                    capital_insoluto: "8880.00",
                    capital_vencido: "0.00",
                    exigible: "114.83",
                    dias_atraso: 1,
                    interes_devengado_pendiente: "120.64",
                    interes_no_exigible: "5.92",
                    interes_vencido: "114.72",
                    obligacion_vencida: "114.72",
                },
            },
        },
        global: {
            mocks: { route: () => "/" },
            stubs: {
                AppLayout: {
                    template: '<main><slot name="card-content" /></main>',
                },
                PageHeader: true,
                SimulationTaxSummary: true,
                CapturaHistoricaPanel: true,
                Tag: true,
                Select: true,
                DataTable: true,
                Column: true,
                Message: { template: "<div><slot /></div>" },
            },
        },
    });
    expect(wrapper.get('[aria-label="Intereses Bullet"]').text()).toContain(
        "Interés aún no exigible",
    );
    expect(wrapper.text()).toContain("1 días de atraso de obligaciones");
    expect(wrapper.text()).not.toContain("Registrar pago QA");
    expect(
        wrapper
            .findAll("a")
            .some((link) => link.text() === "Anticipos y liquidación"),
    ).toBe(false);
    await wrapper.setProps({
        situacion: { error: "La evidencia requiere revisión.", resumen: null },
    });
    expect(wrapper.find('[aria-label="Intereses Bullet"]').exists()).toBe(
        false,
    );
    expect(wrapper.text()).not.toContain("114.72");
    wrapper.unmount();
});
