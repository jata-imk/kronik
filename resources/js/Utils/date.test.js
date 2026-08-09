import { describe, expect, it } from "vitest";
import { formatDateOnly, parseDateOnly } from "./date";

describe("date-only helpers", () => {
    it("interpreta fechas del backend sin desplazarlas por zona horaria", () => {
        const date = parseDateOnly("1991-04-18");

        expect(date.getFullYear()).toBe(1991);
        expect(date.getMonth()).toBe(3);
        expect(date.getDate()).toBe(18);
    });

    it("envía fechas en el formato ISO que espera Laravel", () => {
        expect(formatDateOnly(new Date(2026, 7, 8))).toBe("2026-08-08");
    });
});
