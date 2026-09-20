import { describe, expect, it } from "vitest";

import { normalizeTemplateKey } from "./templateKey";

describe("clave de plantilla", () => {
    it("normaliza acentos, espacios y signos a una clave legible", () => {
        expect(normalizeTemplateKey("  Contrato de Crédito: PyME  ")).toBe(
            "contrato-de-credito-pyme",
        );
    });

    it("respeta el límite aceptado por el servidor", () => {
        expect(normalizeTemplateKey("a".repeat(90))).toHaveLength(80);
    });
});
