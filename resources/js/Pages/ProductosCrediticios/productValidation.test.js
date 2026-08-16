import { describe, expect, it } from "vitest";
import {
    countProductTabErrors,
    formatMoneyWithCents,
    tabForProductError,
} from "./productValidation";

describe("product validation presentation", () => {
    it("ubica errores anidados en su pestaña", () => {
        expect(tabForProductError("version.monto_minimo")).toBe("condiciones");
        expect(tabForProductError("version.reglas.metodos_amortizacion")).toBe(
            "reglas",
        );
        expect(tabForProductError("version.comisiones.0.modalidad_cobro")).toBe(
            "comisiones",
        );
        expect(tabForProductError("clave")).toBe("general");
    });

    it("cuenta errores por pestaña", () => {
        const errors = {
            "version.monto_minimo": "Requerido",
            "version.periodicidades.0.plazo_maximo": "Inválido",
            "version.comisiones.0.importe": "Requerido",
        };

        expect(countProductTabErrors(errors, "condiciones")).toBe(2);
        expect(countProductTabErrors(errors, "comisiones")).toBe(1);
    });

    it("siempre presenta dinero con centavos", () => {
        expect(formatMoneyWithCents("553.64")).toContain("553.64");
        expect(formatMoneyWithCents("50")).toContain("50.00");
    });
});
