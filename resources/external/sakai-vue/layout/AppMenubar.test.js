import { shallowMount } from "@vue/test-utils";
import { beforeEach, expect, it, vi } from "vitest";
import AppMenubar from "./AppMenubar.vue";
const page = vi.hoisted(() => ({ props: {} }));
vi.mock("@inertiajs/vue3", () => ({ usePage: () => page, router: { visit: vi.fn(), reload: vi.fn() } }));
beforeEach(() => { page.props = { menubarItems: [], menubarAdmin: null }; globalThis.route = () => "/admin/menubar-items"; });
const mountBar = () => shallowMount(AppMenubar, { global: { stubs: { MenubarModel: { name: "MenubarModel", props: ["model"], template: '<nav><slot name="end"/></nav>' }, MenubarQuickAdd: true } } });
it("filtra acciones del sistema por identidad y conserva etiquetas personalizadas", () => {
    page.props.menubarItems = [{ kind: "system", label: "Regresar" }, { kind: "group", label: "Administración", items: [{ label: "Abrir" }] }];
    const wrapper = mountBar();
    expect(wrapper.findComponent({ name: "MenubarModel" }).props("model")).toEqual([page.props.menubarItems[1]]);
    expect(wrapper.find("button-stub").exists()).toBe(false);
});
it("configurar ofrece únicamente las capacidades autorizadas", () => {
    page.props.menubarAdmin = { canRead: true, canCreate: false };
    const wrapper = mountBar();
    expect(wrapper.find("button-stub[label='Configurar']").exists()).toBe(true);
    expect(wrapper.find("menu-stub").attributes("model")).toBeDefined();
    expect(wrapper.find("split-button-stub").exists()).toBe(false);
});
