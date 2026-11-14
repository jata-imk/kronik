import { mount } from "@vue/test-utils";
import { expect, it, vi } from "vitest";
import AtencionCartera from "./AtencionCartera.vue";
import EmbudoEtapas from "./EmbudoEtapas.vue";

vi.mock("@inertiajs/vue3", () => ({
    Link: { props: ["href"], template: '<a :href="href"><slot /></a>' },
}));

it("ofrece el crédito exacto y el listado completo conservando el tipo de atención", async () => {
    const wrapper = mount(AtencionCartera, {
        props: {
            corte: "2026-11-14",
            atencion: {
                proximos: [
                    {
                        credito_id: 9,
                        cliente: "Mariana Ejemplo",
                        folio: "CR-9",
                        fecha: "2026-12-01",
                        importe: "12240.00",
                    },
                ],
                vencidos: [
                    {
                        credito_id: 6,
                        cliente: null,
                        folio: "CR-6",
                        fecha: "2026-11-14",
                        importe: "248.00",
                        dias_atraso: 5,
                    },
                ],
            },
        },
        global: { mocks: { route: vi.fn((name, id) => `/creditos/${id}`) } },
    });
    expect(wrapper.get('a[href="/creditos/9"]').text()).toContain(
        "Mariana Ejemplo",
    );
    await wrapper.findAll("button")[1].trigger("click");
    expect(wrapper.text()).not.toContain("Mariana Ejemplo");
    expect(wrapper.get('a[href="/creditos/6"]').text()).toContain(
        "5 días de atraso",
    );
    await wrapper.findAll("button").at(-1).trigger("click");
    expect(wrapper.emitted("detalle")[0]).toEqual(["vencido"]);
    await wrapper.setProps({ atencion: null });
    expect(wrapper.findAll("a")).toHaveLength(0);
    wrapper.unmount();
});

it("representa estados actuales sin inventar conversiones y enlaza etapa con sucursal", () => {
    const routeMock = vi.fn(
        (name, params) =>
            `/solicitudes?estado=${params.estado ?? ""}&sucursal_id=${params.sucursal_id}`,
    );
    vi.stubGlobal("route", routeMock);
    try {
        const wrapper = mount(EmbudoEtapas, {
            props: {
                filtros: { scope: "all", sucursal_id: 4 },
                etapas: [
                    { estado: "borrador", etiqueta: "Borrador", total: 2 },
                    {
                        estado: "desembolsada",
                        etiqueta: "Desembolsada · QA",
                        total: 4,
                    },
                ],
            },
            global: { mocks: { route: routeMock } },
        });
        expect(
            wrapper.get('svg[role="img"]').attributes("aria-label"),
        ).toContain("Volumen actual");
        expect(wrapper.text()).toContain(
            "No es una tasa de conversión histórica",
        );
        expect(wrapper.text()).not.toContain("%");
        expect(wrapper.get('a[href*="estado=desembolsada"]').text()).toContain(
            "4",
        );
        expect(routeMock).toHaveBeenCalledWith("solicitudes.index", {
            estado: "desembolsada",
            scope: "all",
            sucursal_id: 4,
        });
        wrapper.unmount();
    } finally {
        vi.unstubAllGlobals();
    }
});
