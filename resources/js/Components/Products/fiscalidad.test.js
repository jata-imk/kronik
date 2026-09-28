import { describe, expect, it } from "vitest";
import {
    emptyTaxConcept,
    normalizeTaxPolicy,
    taxConceptLabel,
} from "./fiscalidad";

describe("configuración fiscal", () => {
    it("no interpreta ausencia como exento ni tasa cero", () => {
        expect(normalizeTaxPolicy().ordinario).toEqual({
            tratamiento: "no_definido",
            tasa: null,
            base: null,
        });
        expect(taxConceptLabel(null)).toBe("Sin definir");
    });
    it("conserva la tasa decimal exacta e independiza conceptos", () => {
        const source = {
            uso: "institucional",
            ordinario: {
                tratamiento: "gravado",
                tasa: "16.12345678",
                base: "importe_concepto",
            },
        };
        const policy = normalizeTaxPolicy(source);
        expect(policy.ordinario.tasa).toBe("16.12345678");
        policy.ordinario.tasa = "0";
        expect(source.ordinario.tasa).toBe("16.12345678");
        expect(policy.moratorio).toEqual(emptyTaxConcept());
    });
});
