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

describe("AppMenubar", () => {
    beforeEach(() => {
        page.props.menubarAdmin = false;
        page.props.menubarItems = [];
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

        const wrapper = mount(AppMenubar, {
            global: {
                stubs: {
                    Menubar: MenubarStub,
                    MenubarQuickAdd: true,
                    Button: true,
                    SplitButton: true,
                },
            },
        });

        expect(wrapper.get("a").attributes("href")).toBe(
            "https://app.test/clientes/15/edit",
        );
        expect(wrapper.get("a").text()).toContain("Editar cliente");
        expect(wrapper.findAll("nav > span")).toHaveLength(1);
    });
});
