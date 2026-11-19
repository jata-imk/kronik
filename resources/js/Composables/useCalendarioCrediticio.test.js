import { describe, expect, it } from "vitest";
import { ref } from "vue";
import { useCalendarioCrediticio } from "./useCalendarioCrediticio";

describe("calendario institucional", () => {
    it("muestra primero la excepción y distingue las fechas sin cobertura", () => {
        const version = ref({ configuracion: {
            desde: "2026-11-14", hasta: "2026-11-18", semana_habil: [1, 2, 3, 4, 5],
            feriados: [{ fecha: "2026-11-16", nombre: "Feriado" }],
            excepciones: [{ fecha: "2026-11-16", nombre: "Apertura", habil: true }],
        } });
        const vista = useCalendarioCrediticio(version, "2026-11-15");
        expect(vista.detalle.value.tipo).toBe("descanso_semanal");
        vista.seleccionado.value = "2026-11-16";
        expect(vista.detalle.value.tipo).toBe("apertura");
        vista.moverMes(1);
        expect(vista.detalle.value.tipo).toBe("fuera");
    });
});
