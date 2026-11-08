import { describe, expect, it } from "vitest";
import { cambiarMetodos, versionParaConsulta } from "./versionForm";

describe("datos de consulta", () => {
    it("preserva decimales, ausencia fiscal y códigos históricos sin mutar la fuente", () => {
        const source = {
            fiscalidad: null,
            tasa_ordinaria_anual: "36.12345678",
            reglas: {
                metodos_amortizacion: ["historico"],
                convencion_interes: "legacy",
            },
            comisiones: [
                {
                    momento_cobro: "firma",
                    modalidad_cobro: null,
                    fiscalidad: null,
                },
            ],
        };
        const result = versionParaConsulta(source);
        expect(result.fiscalidad).toBeNull();
        expect(result.tasa_ordinaria_anual).toBe("36.12345678");
        expect(result.comisiones[0]).toEqual(source.comisiones[0]);
        result.reglas.metodos_amortizacion.push("otro");
        expect(source.reglas.metodos_amortizacion).toEqual(["historico"]);
    });
});

it("cambiar a Bullet limpia incompatibles y conserva vencimiento sin elegir base moratoria", () => {
    const reglas = {
        metodos_amortizacion: ["capital_fijo"],
        base_moratoria: "capital_vencido",
        aplicacion_prepago: "reducir_plazo",
    };
    const combinaciones = {
        bullet: {
            bases_moratorias: [
                "saldo_insoluto",
                "capital_inicial",
                "obligacion_vencida",
            ],
            aplicaciones_prepago: ["conservar_fecha"],
        },
    };
    const nuevo = cambiarMetodos(
        reglas,
        ["capital_fijo", "bullet"],
        combinaciones,
    );
    expect(nuevo.metodos_amortizacion).toEqual(["bullet"]);
    expect(nuevo.aplicacion_prepago).toBe("conservar_fecha");
    expect(nuevo.base_moratoria).toBeNull();
    expect(reglas.base_moratoria).toBe("capital_vencido");
});

it("salir de Bullet elimina política y base incompatible sin modificar la evidencia original", () => {
    const reglas = {
        metodos_amortizacion: ["bullet"],
        politica_interes_bullet: "al_vencimiento",
        base_moratoria: "capital_inicial",
        aplicacion_prepago: "conservar_fecha",
    };
    const combinaciones = {
        actuales: {
            bases_moratorias: ["capital_vencido"],
            aplicaciones_prepago: ["reducir_plazo", "reducir_pago"],
        },
    };
    const nuevo = cambiarMetodos(
        reglas,
        ["bullet", "capital_fijo"],
        combinaciones,
    );
    expect(nuevo.metodos_amortizacion).toEqual(["capital_fijo"]);
    expect(nuevo).not.toHaveProperty("politica_interes_bullet");
    expect(nuevo.base_moratoria).toBeNull();
    expect(reglas.politica_interes_bullet).toBe("al_vencimiento");
});
