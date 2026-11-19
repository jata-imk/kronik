import { mount } from "@vue/test-utils";
import PrimeVue from "primevue/config";
import { beforeEach, expect, it, vi } from "vitest";
import Model from "./MenubarModel.vue";
const visit = vi.hoisted(() => vi.fn());
vi.mock("@inertiajs/vue3", () => ({ router: { visit } }));
beforeEach(() => {
    vi.stubGlobal("matchMedia", vi.fn(() => ({ matches: false, addEventListener: vi.fn(), removeEventListener: vi.fn() })));
});
it("vista previa conserva ARIA nativo y grupos pero elimina enlaces y comandos", async () => {
    const command = vi.fn();
    const wrapper = mount(Model, { attachTo: document.body, props: { preview: true, model: [
        { label: "Cliente", icon: "pi pi-users", items: [{ label: "Expediente", icon: "pi pi-folder", url: "/clientes/1/expediente", command }] },
    ] }, global: { plugins: [PrimeVue] } });
    expect(wrapper.findAll("a[href]")).toHaveLength(0);
    expect(wrapper.find(".p-menubar-root-list > li > div .pi-users").exists()).toBe(false);
    const group = wrapper.get('[role="menuitem"][aria-label="Cliente"]');
    await group.get(".p-menubar-item-content").trigger("click");
    expect(group.attributes("aria-expanded")).toBe("true");
    const submenu = wrapper.get('[role="menu"]');
    expect(document.getElementById(submenu.attributes("aria-labelledby"))).not.toBeNull();
    await wrapper.get('[role="menuitem"][aria-label="Expediente"] .p-menubar-item-content').trigger("click");
    expect(command).not.toHaveBeenCalled();
    expect(visit).not.toHaveBeenCalled();
    wrapper.unmount();
});
it("enlaces internos conservan href y navegan con Inertia", async () => {
    visit.mockClear();
    const wrapper = mount(Model, { props: { model: [{ label: "Clientes", url: "/clientes" }] }, global: { plugins: [PrimeVue] } });
    const anchor = wrapper.get('a[href="/clientes"]');
    expect(anchor.attributes("href")).toBe("/clientes");
    await anchor.trigger("click", { button: 0 });
    expect(visit).toHaveBeenCalledWith(new URL("/clientes", window.location.origin).href);
    wrapper.unmount();
});
