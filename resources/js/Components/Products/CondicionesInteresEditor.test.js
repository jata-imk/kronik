import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import Editor from "./CondicionesInteresEditor.vue";

const catalogo = {
    politicas_interes_bullet: [
        { value: "periodicos", label: "Intereses periódicos" },
        { value: "al_vencimiento", label: "Intereses al vencimiento" },
    ],
    bases_ordinarias: [{ value: "saldo_insoluto", label: "Saldo insoluto" }],
    bases_moratorias: [
        { value: "capital_vencido", label: "Capital vencido" },
        { value: "saldo_insoluto", label: "Saldo insoluto" },
        { value: "capital_inicial", label: "Capital inicial" },
        { value: "obligacion_vencida", label: "Obligación vencida impagada" },
    ],
};
const combinaciones = {
    bullet: {
        bases_moratorias: [
            "saldo_insoluto",
            "capital_inicial",
            "obligacion_vencida",
        ],
        aviso: "Solo borrador",
    },
    actuales: { bases_moratorias: ["capital_vencido"] },
};
const reglas = {
    metodos_amortizacion: ["bullet"],
    base_ordinaria: "saldo_insoluto",
    base_moratoria: "obligacion_vencida",
    politica_interes_bullet: "periodicos",
};
const render = (readonly = false) =>
    shallowMount(Editor, {
        props: { reglas: { ...reglas }, catalogo, combinaciones, readonly },
        global: {
            renderStubDefaultSlot: true,
            stubs: {
                Select: {
                    name: "Select",
                    props: ["options", "modelValue"],
                    template: "<div />",
                },
            },
        },
    });

describe("condiciones de intereses", () => {
    it("ofrece las tres bases solo para Bullet y resuelve igual al ordinario en valor efectivo", async () => {
        const w = render();
        const mora = w.findAllComponents({ name: "Select" })[2];
        expect(mora.attributes("aria-label")).toBe("Base moratoria");
        expect(mora.props("options").map((o) => o.value)).toEqual(
            combinaciones.bullet.bases_moratorias,
        );
        await w.find("button-stub").trigger("click");
        expect(w.emitted("update:reglas")[0][0].base_moratoria).toBe(
            "saldo_insoluto",
        );
        expect(reglas.base_moratoria).toBe("obligacion_vencida");
        await w.setProps({
            reglas: { metodos_amortizacion: ["capital_fijo"] },
        });
        expect(w.text()).not.toContain("Pago de intereses Bullet");
    });
    it("consulta conserva valores ausentes y desconocidos sin controles ni emisiones", async () => {
        const w = render(true);
        expect(w.findAllComponents({ name: "Select" })).toHaveLength(0);
        expect(w.find("button-stub").exists()).toBe(false);
        expect(w.text()).toContain("Obligación vencida impagada");
        await w.setProps({
            reglas: {
                metodos_amortizacion: ["bullet"],
                base_moratoria: "codigo_historico",
            },
        });
        expect(w.text()).toContain("codigo_historico");
        expect(w.text()).toContain("Sin registrar");
        expect(w.emitted("update:reglas")).toBeUndefined();
    });
});
