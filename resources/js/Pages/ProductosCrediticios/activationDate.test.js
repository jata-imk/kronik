import { describe, expect, it } from "vitest";
import { dateInTimeZone, calendarDate } from "./activationDate";

describe("fecha empresarial de activación", () => {
    it("conserva el día civil elegido por DatePicker sin serializarlo como instante", () => {
        expect(calendarDate(new Date(2026, 1, 28))).toBe("2026-02-28");
        expect(calendarDate("2026-02-28")).toBe("2026-02-28");
        expect(calendarDate(new Date("inválida"))).toBe("");
    });
    it("usa la fecha de la zona empresarial aunque UTC esté en otro día", () => {
        const instant = new Date("2026-08-23T03:30:00Z");

        expect(dateInTimeZone(instant, "America/Mexico_City")).toBe(
            "2026-08-22",
        );
        expect(dateInTimeZone(instant, "UTC")).toBe("2026-08-23");
    });
});
