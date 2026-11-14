import { mount } from "@vue/test-utils";
import { expect, it, vi } from "vitest";
import TableroFinanciero from "./TableroFinanciero.vue";
import { importeMXN, fechaCorta } from "@/Utils/formatosFinancieros";

vi.mock("@inertiajs/vue3", () => ({ Link: { template: "<a><slot /></a>" } }));

const datos = () => ({
    cobertura: { disponible: true, creditos: 1, verificados: 1 },
    saldos: {
        capital: "12000.00",
        vencido: "248.00",
        con_atraso: 1,
        clientes_atraso: 1,
    },
    cobros: {
        capital: "100.00",
        ordinario: "20.00",
        moratorio: "0.00",
        comisiones: "0.00",
        impuestos: "3.20",
        total: "123.20",
    },
    desembolsos: "12000.00",
    recuperado: "100.00",
    zona_horaria: "America/Mexico_City",
    antiguedad: [],
    proximos: [],
    movimientos_mensuales: [],
    capital_mensual: [],
});
const opciones = () => ({
    props: {
        datos: datos(),
        filtros: {
            corte: "2026-11-13",
            desde: "2026-11-01",
            hasta: "2026-11-13",
        },
    },
    global: {
        stubs: {
            SerieFinanciera: true,
            DetalleFinanciero: true,
            Button: {
                template:
                    "<button @click=\"$emit('click')\">{{ label }}</button>",
                props: ["label"],
            },
            Message: { template: "<div><slot /></div>" },
        },
    },
});

it("muestra periodos separados y abre el detalle sin recalcular cifras", async () => {
    const wrapper = mount(TableroFinanciero, opciones());
    expect(wrapper.text()).toContain("$12,000.00");
    expect(wrapper.text()).toContain("$123.20");
    expect(wrapper.text()).toContain("01/11/2026–13/11/2026");
    expect(wrapper.text()).toContain("no representan utilidad neta");
    await wrapper.findAll("button")[0].trigger("click");
    expect(wrapper.emitted("detalle")[0]).toEqual(["capital"]);
});

it("retira saldos previos cuando la lectura cambia a pendiente", async () => {
    const wrapper = mount(TableroFinanciero, opciones());
    const nuevos = {
        ...datos(),
        saldos: null,
        cobertura: { disponible: false, creditos: 1, verificados: 0 },
        desembolsos: "0.00",
    };
    await wrapper.setProps({ datos: nuevos });
    expect(wrapper.text()).toContain("requieren actualización o revisión");
    expect(wrapper.findAll("button")[0].attributes("disabled")).toBeDefined();
    expect(wrapper.text()).not.toContain("$12,000.00");
    expect(wrapper.text()).toContain("$123.20");
    await wrapper.setProps({ datos: null });
    expect(wrapper.text()).not.toContain("$123.20");
});

it("formatea centavos grandes sin perder precisión ni cambiar el día por zona horaria", () => {
    expect(importeMXN("9999999999999999.99")).toBe("$9,999,999,999,999,999.99");
    expect(importeMXN(null)).toBe("Sin dato");
    expect(fechaCorta("2026-11-13")).toBe("13/11/2026");
});
