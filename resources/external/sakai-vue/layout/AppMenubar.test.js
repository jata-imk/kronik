import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import AppMenubar from "./AppMenubar.vue";

const page = vi.hoisted(() => ({
    props: {
        menubarAdmin: false,
        menubarItems: [],
    },
}));

vi.mock("@inertiajs/vue3", () => ({
    usePage: () => page,
}));

const MenubarStub = {
    props: ["model"],
    template: `
        <nav>
            <template v-for="item in model" :key="item.label">
                <slot name="item" :item="item" :props="{ action: { class: 'action' } }" />
            </template>
            <slot name="end" />
        </nav>
    `,
};

const renderMenubar = () => mount(AppMenubar, {
    global: {
        stubs: {
            Menubar: MenubarStub,
            MenubarQuickAdd: true,
            Button: true,
            SplitButton: true,
        },
    },
});

describe("AppMenubar", () => {
    beforeEach(() => {
        page.props.menubarAdmin = false;
        page.props.menubarItems = [];
        page.props.auth = {};
        globalThis.route = vi.fn(() => "/admin/menubar-items");
    });

    it("renderiza las acciones con URL como enlaces navegables", () => {
        page.props.menubarItems = [
            {
                label: "Editar cliente",
                icon: "pi pi-pencil",
                url: "https://app.test/clientes/15/edit",
            },
            { label: "Clientes", items: [] },
        ];

        const wrapper = renderMenubar();

        expect(wrapper.get("a").attributes("href")).toBe(
            "https://app.test/clientes/15/edit",
        );
        expect(wrapper.get("a").text()).toContain("Editar cliente");
        expect(wrapper.findAll("nav > span")).toHaveLength(1);
    });

    it.each([
        ["consulta sin permiso", {}, false],
        ["permiso explícito", { permissions: { "read-menubar-items": true } }, true],
        ["Super Admin global", { is_super_admin: true }, true],
    ])("controla el acceso a configurar menubar para %s", (_perfil, auth, visible) => {
        page.props.auth = auth;
        expect(renderMenubar().find("split-button-stub").exists()).toBe(visible);
    });
});
