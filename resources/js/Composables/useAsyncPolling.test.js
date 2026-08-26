import { mount } from "@vue/test-utils";
import { afterEach, describe, expect, it, vi } from "vitest";
import { defineComponent } from "vue";

import { useAsyncPolling } from "./useAsyncPolling";

function mountPolling(callback, options = {}) {
    let polling;
    const wrapper = mount(
        defineComponent({
            setup() {
                polling = useAsyncPolling(callback, options);

                return {};
            },
            template: "<div />",
        }),
    );

    return { wrapper, polling };
}

describe("useAsyncPolling", () => {
    afterEach(() => {
        vi.useRealTimers();
    });

    it("respeta el intervalo y se detiene cuando el callback termina", async () => {
        vi.useFakeTimers();
        const callback = vi
            .fn()
            .mockResolvedValueOnce(true)
            .mockResolvedValueOnce(false);
        const { polling } = mountPolling(callback, { intervalMs: 2000 });

        polling.start();
        await vi.advanceTimersByTimeAsync(0);
        expect(callback).toHaveBeenCalledTimes(1);
        expect(polling.isPolling.value).toBe(true);

        await vi.advanceTimersByTimeAsync(1999);
        expect(callback).toHaveBeenCalledTimes(1);
        await vi.advanceTimersByTimeAsync(1);

        expect(callback).toHaveBeenCalledTimes(2);
        expect(polling.isPolling.value).toBe(false);
    });

    it("no solapa una consulta mientras la anterior sigue pendiente", async () => {
        vi.useFakeTimers();
        let resolveFirst;
        const callback = vi
            .fn()
            .mockImplementationOnce(
                () =>
                    new Promise((resolve) => {
                        resolveFirst = resolve;
                    }),
            )
            .mockResolvedValueOnce(false);
        const { polling } = mountPolling(callback, { intervalMs: 2000 });

        polling.start();
        await vi.advanceTimersByTimeAsync(10000);
        expect(callback).toHaveBeenCalledOnce();

        resolveFirst(true);
        await vi.advanceTimersByTimeAsync(0);
        await vi.advanceTimersByTimeAsync(2000);

        expect(callback).toHaveBeenCalledTimes(2);
        expect(polling.isPolling.value).toBe(false);
    });

    it("vence el seguimiento y no deja nuevas consultas programadas", async () => {
        vi.useFakeTimers();
        const callback = vi.fn().mockResolvedValue(true);
        const onTimeout = vi.fn();
        const { polling } = mountPolling(callback, {
            intervalMs: 2000,
            timeoutMs: 5000,
            onTimeout,
        });

        polling.start();
        await vi.advanceTimersByTimeAsync(5000);
        const callsAtTimeout = callback.mock.calls.length;

        expect(onTimeout).toHaveBeenCalledOnce();
        expect(polling.timedOut.value).toBe(true);
        expect(polling.isPolling.value).toBe(false);

        await vi.advanceTimersByTimeAsync(10000);
        expect(callback).toHaveBeenCalledTimes(callsAtTimeout);
    });

    it("tolera errores transitorios y se detiene al alcanzar el límite", async () => {
        vi.useFakeTimers();
        const error = new Error("Sin conexión");
        const callback = vi.fn().mockRejectedValue(error);
        const onError = vi.fn();
        const { polling } = mountPolling(callback, {
            intervalMs: 1000,
            maxConsecutiveErrors: 2,
            onError,
        });

        polling.start();
        await vi.advanceTimersByTimeAsync(1000);

        expect(callback).toHaveBeenCalledTimes(2);
        expect(onError).toHaveBeenCalledWith(error);
        expect(polling.lastError.value).toBe(error);
        expect(polling.isPolling.value).toBe(false);

        polling.reset();
        expect(polling.lastError.value).toBeNull();
        expect(polling.timedOut.value).toBe(false);
    });

    it("limpia los temporizadores cuando se desmonta el consumidor", async () => {
        vi.useFakeTimers();
        const callback = vi.fn().mockResolvedValue(true);
        const { wrapper, polling } = mountPolling(callback, {
            intervalMs: 1000,
        });

        polling.start();
        await vi.advanceTimersByTimeAsync(0);
        wrapper.unmount();
        await vi.advanceTimersByTimeAsync(5000);

        expect(callback).toHaveBeenCalledOnce();
        expect(polling.isPolling.value).toBe(false);
    });
});
