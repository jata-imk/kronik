import { describe, expect, it } from "vitest";
import { versionParaConsulta } from "./versionForm";

describe("datos de consulta", () => {
    it("preserva decimales, ausencia fiscal y códigos históricos sin mutar la fuente", () => {
        const source = { fiscalidad: null, tasa_ordinaria_anual: "36.12345678", reglas: { metodos_amortizacion: ["historico"], convencion_interes: "legacy" }, comisiones: [{ momento_cobro: "firma", modalidad_cobro: null, fiscalidad: null }] };
        const result = versionParaConsulta(source);
        expect(result.fiscalidad).toBeNull();
        expect(result.tasa_ordinaria_anual).toBe("36.12345678");
        expect(result.comisiones[0]).toEqual(source.comisiones[0]);
        result.reglas.metodos_amortizacion.push("otro");
        expect(source.reglas.metodos_amortizacion).toEqual(["historico"]);
    });
});
