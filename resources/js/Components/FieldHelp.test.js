import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import FieldHelp from "./FieldHelp.vue";

describe("ayuda contextual compartida", () => {
    it("Escape cierra la ayuda sin propagar el cierre al formulario contenedor", async () => {
        const hide = vi.fn();
        const keydown = vi.fn();
        const wrapper = mount({ components: { FieldHelp }, template: '<section @keydown="keydown"><FieldHelp title="Prefijo" description="Identifica la sucursal." /></section>', methods: { keydown } }, {
            global: { stubs: {
                Button: { template: '<button><slot /></button>' },
                Popover: { emits: ["show", "hide"], methods: { hide() { hide(); this.$emit("hide"); }, toggle() { this.$emit("show"); } }, template: '<div><slot /></div>' },
            } },
        });
        expect(wrapper.get("button").attributes("aria-label")).toBe("Ayuda: Prefijo");
        await wrapper.get("button").trigger("click");
        await wrapper.get("button").trigger("keydown", { key: "Escape", code: "Escape" });
        expect(hide).toHaveBeenCalledOnce();
        expect(keydown).not.toHaveBeenCalled();
        await wrapper.get("button").trigger("keydown", { key: "Escape", code: "Escape" });
        expect(keydown).toHaveBeenCalledOnce();
        expect(hide).toHaveBeenCalledOnce();
    });
});
