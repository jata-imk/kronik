import { describe, expect, it } from "vitest";
import {
    commissionCatReason,
    commissionIncludesCat,
    isConditionalCommission,
    isSelectableOptionalCommission,
} from "./commissionRules";

describe("reglas de comisiones y CAT", () => {
    it.each(["inicio", "firma", "desembolso_descuento", "cada_pago"])(
        "incluye una comisión obligatoria de %s en el CAT base",
        (momento_cobro) => {
            expect(
                commissionIncludesCat({ obligatoria: true, momento_cobro }),
            ).toBe(true);
        },
    );

    it("excluye opcionales y cargos condicionados", () => {
        expect(
            commissionIncludesCat({
                obligatoria: false,
                momento_cobro: "inicio",
            }),
        ).toBe(false);
        expect(
            commissionIncludesCat({
                obligatoria: true,
                momento_cobro: "liquidacion",
            }),
        ).toBe(false);
        expect(
            commissionCatReason({
                obligatoria: false,
                momento_cobro: "cada_pago",
            }),
        ).toContain("cliente puede elegir");
    });

    it("sólo ofrece opcionales determinísticas en el simulador", () => {
        expect(
            isSelectableOptionalCommission({
                obligatoria: false,
                momento_cobro: "cada_pago",
            }),
        ).toBe(true);
        expect(
            isSelectableOptionalCommission({
                obligatoria: false,
                momento_cobro: "evento",
            }),
        ).toBe(false);
        expect(isConditionalCommission({ momento_cobro: "evento" })).toBe(true);
    });
});
