import { effectScope, nextTick, ref } from "vue";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { useCodigoPostal } from "./useCodigoPostal";

const response = (data) =>
    Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ data }),
    });

const flushPromises = async () => {
    await Promise.resolve();
    await Promise.resolve();
};

describe("useCodigoPostal", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        globalThis.fetch = vi.fn();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it("usa sugerencias para el autocompletado y buscar para el detalle", async () => {
        const codigo = ref("970");
        fetch
            .mockImplementationOnce(() => response([{ codigo: "97000" }]))
            .mockImplementationOnce(() =>
                response({ codigo: "97000", municipio: "Mérida" }),
            );

        const scope = effectScope();
        const postal = scope.run(() =>
            useCodigoPostal(codigo, {
                shouldFetchSugerencias: (value) => value.length >= 3,
                shouldFetchBusqueda: (value) => value.length === 5,
            }),
        );

        await vi.advanceTimersByTimeAsync(300);
        expect(fetch).toHaveBeenNthCalledWith(
            1,
            "/codigos-postales/sugerencias?codigo=970",
            expect.objectContaining({ method: "GET" }),
        );
        expect(postal.sugerenciasData.value).toEqual([{ codigo: "97000" }]);

        codigo.value = "97000";
        await nextTick();
        await postal.busqueda();

        expect(fetch).toHaveBeenLastCalledWith(
            "/codigos-postales/buscar?codigo=97000",
            expect.objectContaining({ method: "GET" }),
        );
        expect(postal.busquedaData.value).toEqual({
            codigo: "97000",
            municipio: "Mérida",
        });

        scope.stop();
    });

    it("invalida una sugerencia pendiente cuando cambia el prefijo", async () => {
        const codigo = ref("970");
        let resolveFirst;
        const firstRequest = new Promise((resolve) => {
            resolveFirst = resolve;
        });

        fetch
            .mockImplementationOnce(() => firstRequest)
            .mockImplementationOnce(() => response([{ codigo: "97100" }]));

        const scope = effectScope();
        const postal = scope.run(() =>
            useCodigoPostal(codigo, {
                shouldFetchSugerencias: (value) => value.length >= 3,
            }),
        );

        await vi.advanceTimersByTimeAsync(300);
        codigo.value = "971";
        await nextTick();

        resolveFirst({
            ok: true,
            json: () => Promise.resolve({ data: [{ codigo: "97000" }] }),
        });
        await flushPromises();
        expect(postal.sugerenciasData.value).toEqual([]);

        await vi.advanceTimersByTimeAsync(300);
        expect(postal.sugerenciasData.value).toEqual([{ codigo: "97100" }]);

        scope.stop();
    });

    it("no consulta buscar hasta que se solicita explícitamente", async () => {
        const codigo = ref("97000");
        fetch.mockImplementation(() => response({ codigo: "97000" }));

        const scope = effectScope();
        const postal = scope.run(() =>
            useCodigoPostal(codigo, {
                shouldFetchSugerencias: () => false,
                shouldFetchBusqueda: (value) => value.length === 5,
            }),
        );

        await vi.advanceTimersByTimeAsync(300);
        expect(fetch).not.toHaveBeenCalled();

        await postal.busqueda();
        expect(fetch).toHaveBeenCalledTimes(1);

        scope.stop();
    });
});
