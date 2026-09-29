import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import SimulationTaxSummary from "./SimulationTaxSummary.vue";

const render = (simulation) =>
    mount(SimulationTaxSummary, {
        props: { simulation },
        global: { stubs: { Message: { template: "<div><slot /></div>" } } },
    });

describe("resumen fiscal", () => {
    it("no muestra impuesto cero cuando no fue calculado", () => {
        const wrapper = render({
            fiscalidad: {
                estado: "no_calculada",
                leyenda:
                    "Escenario anterior a impuestos. No significa exención ni tasa cero.",
            },
        });
        expect(wrapper.text()).toContain("No significa exención");
        expect(wrapper.text()).not.toContain("$0.00");
        expect(wrapper.find("details").exists()).toBe(false);
    });
    it("identifica QA y explica el desglose sin volver a sumar lo financiado", () => {
        const wrapper = render({
            fiscalidad: {
                estado: "proyeccion",
                uso: "prueba",
                leyenda: "Proyección",
            },
            total_impuestos: "16.00",
            totales: {
                impuestos_financiados: "16.00",
                impuestos_retenidos: "0.00",
                impuestos_pago_separado: "0.00",
            },
            tabla: [
                {
                    numero: 0,
                    fecha: "2026-01-01",
                    impuestos: "16.00",
                    impuestos_detalle: [
                        {
                            concepto: "Apertura",
                            tratamiento: "gravado",
                            importe_concepto: "100.00",
                            tasa: "16",
                            impuesto: "16.00",
                        },
                    ],
                },
            ],
        });
        expect(wrapper.text()).toContain("Configuración de prueba / QA");
        expect(wrapper.text()).toContain("no se vuelven a sumar");
        expect(wrapper.text()).toContain("Apertura · Gravado");
        expect(wrapper.text()).toContain("$100.00 × 16 %");
    });
});
