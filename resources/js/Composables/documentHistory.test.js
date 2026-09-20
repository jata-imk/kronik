import { describe, it, expect } from "vitest";
import { documentVersionChain } from "./documentHistory";
describe("historial por documento", () => {
    it("no mezcla documentos adicionales con el mismo nombre o tipo", () => {
        const documents = [
            { id: 1, nombre: "Logo" },
            { id: 2, nombre: "Logo" },
            { id: 3, reemplaza_documento_id: 1 },
            { id: 4, reemplaza_documento_id: 3 },
        ];
        expect(
            documentVersionChain(documents, documents[3]).map(
                (item) => item.id,
            ),
        ).toEqual([3, 1]);
    });
    it("termina ante referencias ausentes o cíclicas", () => {
        expect(documentVersionChain([], { reemplaza_documento_id: 9 })).toEqual(
            [],
        );
        expect(
            documentVersionChain([{ id: 1, reemplaza_documento_id: 1 }], {
                reemplaza_documento_id: 1,
            }),
        ).toHaveLength(1);
    });
});
