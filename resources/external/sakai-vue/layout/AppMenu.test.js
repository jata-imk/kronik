import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import AppMenu from "./AppMenu.vue";

const page = vi.hoisted(() => ({ props: { navigation: [] } }));
vi.mock("@inertiajs/vue3", () => ({ usePage: () => page }));
const Item = { props: ["item"], template: '<li>{{ item.label }}<span v-for="child in item.items" :key="child.label">{{ child.label }}</span></li>' };
const render = () => mount(AppMenu, { global: { stubs: { AppMenuItem: Item } } });

describe("AppMenu", () => {
    it("renderiza exclusivamente las entradas autorizadas por servidor", () => {
        page.props.navigation = [{ label: "Módulos", items: [{ label: "Productos crediticios", to: "productos-crediticios.index" }] }];
        expect(render().text()).toContain("Productos crediticios");
        expect(render().text()).not.toContain("Administración");
        expect(render().text()).not.toContain("Ejemplos");
    });
    it("no inventa permisos ni rutas si falta navegación", () => {
        page.props.navigation = undefined;
        expect(render().findAllComponents(Item)).toHaveLength(0);
    });
});
