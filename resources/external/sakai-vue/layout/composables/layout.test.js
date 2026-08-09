import { nextTick } from "vue";
import { beforeEach, describe, expect, it, vi } from "vitest";

describe("preferencia de tema", () => {
    const storageKey =
        import.meta.env.VITE_JS_LOCAL_STORAGE_KEY || "layoutConfig";

    beforeEach(() => {
        vi.resetModules();
        localStorage.clear();
        document.documentElement.classList.remove("app-dark");
        globalThis.route = vi.fn(() => ({ current: () => "dashboard" }));
        Object.defineProperty(document, "startViewTransition", {
            configurable: true,
            value: undefined,
        });
    });

    it("restaura el tema persistido y conserva los cambios", async () => {
        localStorage.setItem(
            storageKey,
            JSON.stringify({ darkTheme: true, menuMode: "static" }),
        );

        const { useLayout } = await import("./layout");
        const { layoutConfig, toggleDarkMode } = useLayout();

        expect(layoutConfig.darkTheme).toBe(true);
        expect(document.documentElement.classList.contains("app-dark")).toBe(
            true,
        );

        const transition = vi.fn((callback) => callback());
        Object.defineProperty(document, "startViewTransition", {
            configurable: true,
            value: transition,
        });
        toggleDarkMode();
        await nextTick();

        expect(transition).toHaveBeenCalledOnce();
        expect(document.documentElement.classList.contains("app-dark")).toBe(
            false,
        );
        expect(JSON.parse(localStorage.getItem(storageKey)).darkTheme).toBe(
            false,
        );
    });

    it("tolera una preferencia corrupta sin cambiar a oscuro", async () => {
        localStorage.setItem(storageKey, "{valor-inválido");

        const { useLayout } = await import("./layout");

        expect(useLayout().layoutConfig.darkTheme).toBe(false);
        expect(document.documentElement.classList.contains("app-dark")).toBe(
            false,
        );
    });
});
