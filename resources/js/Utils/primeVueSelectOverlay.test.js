import { describe, expect, it, vi } from "vitest";
import { guardPrimeVueSelectOverlay } from "./primeVueSelectOverlay";

describe("PrimeVue Select orientation guard", () => {
    it("does not position an absent or detached overlay", () => {
        const align = vi.fn();
        const select = { methods: { alignOverlay: align } };
        guardPrimeVueSelectOverlay(select);
        select.methods.alignOverlay.call({ overlay: null, $el: { isConnected: true } });
        select.methods.alignOverlay.call({ overlay: {}, $el: { isConnected: false } });
        expect(align).not.toHaveBeenCalled();
    });
    it("retains original positioning and applies only once", () => {
        const align = vi.fn(() => "positioned");
        const select = { methods: { alignOverlay: align } };
        guardPrimeVueSelectOverlay(select);
        const guarded = select.methods.alignOverlay;
        guardPrimeVueSelectOverlay(select);
        expect(select.methods.alignOverlay).toBe(guarded);
        const instance = { overlay: {}, $el: { isConnected: true } };
        expect(guarded.call(instance, "event")).toBe("positioned");
        expect(align).toHaveBeenCalledWith("event");
        expect(align.mock.contexts[0]).toBe(instance);
    });
});
