import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import AppMenu from "./AppMenu.vue";

const page = vi.hoisted(() => ({
    props: {
        auth: {
            is_super_admin: false,
            permissions: {},
            user: { current_team: { id: 1 } },
        },
    },
}));

vi.mock("@inertiajs/vue3", () => ({ usePage: () => page }));

const AppMenuItemStub = {
    props: ["item"],
    template: `
        <li>
            <span>{{ item.label }}</span>
            <ul v-if="item.items">
                <li v-for="child in item.items" :key="child.label">{{ child.label }}</li>
            </ul>
        </li>
    `,
};

const renderMenu = () => mount(AppMenu, { global: { stubs: { AppMenuItem: AppMenuItemStub } } });

describe("AppMenu", () => {
    beforeEach(() => {
        page.props.auth.is_super_admin = false;
        page.props.auth.permissions = {};
    });

    it("oculta CRM y administración cuando faltan permisos", () => {
        const wrapper = renderMenu();
        expect(wrapper.text()).not.toContain("CRM");
        expect(wrapper.text()).not.toContain("Panel de superusuario");
    });

    it("muestra clientes con permiso de lectura", () => {
        page.props.auth.permissions = { "read-clientes": true };
        const items = renderMenu().findAllComponents(AppMenuItemStub).map((item) => item.props("item"));
        const modules = items.find((item) => item.label === "Módulos");
        expect(modules.items[0].label).toBe("CRM");
        expect(modules.items[0].items.map((item) => item.label)).toContain("Clientes");
    });

    it("mantiene el panel para Super Admin aunque el equipo no tenga roles", () => {
        page.props.auth.is_super_admin = true;
        expect(renderMenu().text()).toContain("Panel de superusuario");
    });

    it("muestra el panel a un administrador funcional", () => {
        page.props.auth.permissions = { "access-admin": true };
        expect(renderMenu().text()).toContain("Panel de superusuario");
    });
});
