import { mount } from "@vue/test-utils";
import { nextTick, reactive } from "vue";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import AppMenu from "./AppMenu.vue";
import { useLayout } from "./composables/layout";

const inertia = vi.hoisted(() => ({ page: null, routeName: "dashboard" }));
vi.mock("@inertiajs/vue3", () => ({
    usePage: () => inertia.page,
    Link: {
        props: ["href"],
        template: '<a :href="href" @click.prevent><slot /></a>',
    },
}));

const navigation = () => [
    {
        label: "Operación",
        items: [
            {
                label: "Clientes",
                items: [
                    {
                        label: "Listado",
                        to: "clientes.index",
                        activeRoutes: ["clientes.show"],
                    },
                    {
                        label: "Expedientes",
                        items: [{ label: "KYC", to: "expedientes.index" }],
                    },
                ],
            },
            {
                label: "Originación",
                items: [{ label: "Solicitudes", to: "solicitudes.index" }],
            },
        ],
    },
];

let wrapper;
const group = (label) =>
    wrapper.findAll("button, a").find((node) => node.text().startsWith(label));
const submenu = (label) =>
    group(label).element.parentElement.querySelector("ul");
const visible = (label) => submenu(label).style.display !== "none";
const render = () => {
    wrapper = mount(AppMenu, {
        attachTo: document.body,
        global: { mocks: { route: globalThis.route } },
    });
    return wrapper;
};
const navigate = async (url, routeName) => {
    inertia.routeName = routeName;
    inertia.page.url = url;
    await nextTick();
};

beforeEach(() => {
    inertia.routeName = "clientes.show";
    inertia.page = reactive({
        url: "/clientes/17",
        props: { navigation: navigation() },
    });
    useLayout().syncMenuNavigation("/reset", "", null);
    useLayout().closeMenu();
    globalThis.route = vi.fn((name) =>
        name
            ? `/${name.replaceAll(".", "/")}`
            : {
                  current: (pattern) =>
                      pattern
                          ? new RegExp(
                                `^${pattern.replaceAll(".", "\\.").replaceAll("*", ".*")}$`,
                            ).test(inertia.routeName)
                          : inertia.routeName,
              },
    );
});
afterEach(() => {
    wrapper?.unmount();
    document.body.innerHTML = "";
});

describe("expansión del sidebar", () => {
    it("permite cerrar y volver a abrir Clientes dentro de una ruta descendiente", async () => {
        render();
        expect(visible("Clientes")).toBe(true);
        await group("Clientes").trigger("click");
        expect(visible("Clientes")).toBe(false);
        expect(group("Clientes").attributes("aria-expanded")).toBe("false");
        await group("Clientes").trigger("click");
        expect(visible("Clientes")).toBe(true);
    });

    it("conserva selección y descripción accesible al cerrar el grupo", async () => {
        render();
        await group("Clientes").trigger("click");
        expect(group("Clientes").element.tagName).toBe("BUTTON");
        expect(group("Clientes").classes()).toContain("active-route");
        const description = group("Clientes").attributes("aria-describedby");
        expect(wrapper.get(`#${description}`).text()).toBe(
            "Página actual: Listado",
        );
        expect(wrapper.get('a[aria-current="page"]').text()).toBe("Listado");
        expect(submenu("Clientes").id).toBe(
            group("Clientes").attributes("aria-controls"),
        );
        expect(submenu("Clientes").hasAttribute("inert")).toBe(true);
    });

    it("abre y cierra grupos sin ruta activa y cierra el hermano conservando selección", async () => {
        render();
        await group("Originación").trigger("click");
        expect(visible("Clientes")).toBe(false);
        expect(visible("Originación")).toBe(true);
        expect(group("Clientes").classes()).toContain("active-route");
        expect(group("Originación").classes()).not.toContain("active-route");
        await group("Originación").trigger("click");
        expect(visible("Originación")).toBe(false);
        await group("Originación").trigger("click");
        expect(visible("Originación")).toBe(true);
    });

    it("abre ancestros anidados y descarta descendientes al cerrar un ancestro", async () => {
        inertia.routeName = "expedientes.index";
        inertia.page.url = "/expedientes";
        render();
        expect(visible("Clientes")).toBe(true);
        expect(visible("Expedientes")).toBe(true);
        await group("Clientes").trigger("click");
        await group("Clientes").trigger("click");
        expect(visible("Clientes")).toBe(true);
        expect(visible("Expedientes")).toBe(false);
        await group("Expedientes").trigger("click");
        expect(visible("Clientes")).toBe(true);
        expect(visible("Expedientes")).toBe(true);
    });

    it("no confunde claves hermanas como 0-1 y 0-10", async () => {
        inertia.page.props.navigation[0].items = Array.from(
            { length: 11 },
            (_, i) => ({
                label: `Grupo ${i}`,
                items: [{ label: `Destino ${i}`, to: `destino${i}` }],
            }),
        );
        render();
        await group("Grupo 10").trigger("click");
        expect(visible("Grupo 10")).toBe(true);
        expect(visible("Grupo 1")).toBe(false);
    });

    it("autoabre al navegar y actualiza la selección en ambas direcciones", async () => {
        render();
        await group("Clientes").trigger("click");
        await navigate("/solicitudes", "solicitudes.index");
        expect(visible("Originación")).toBe(true);
        expect(visible("Clientes")).toBe(false);
        expect(group("Clientes").classes()).not.toContain("active-route");
        expect(wrapper.get('a[aria-current="page"]').text()).toBe(
            "Solicitudes",
        );
        await navigate("/clientes/17", "clientes.show");
        expect(visible("Clientes")).toBe(true);
        expect(visible("Originación")).toBe(false);
    });

    it("reabre al cambiar de página dentro del mismo grupo", async () => {
        render();
        await group("Clientes").trigger("click");
        await navigate("/clientes/18", "clientes.show");
        expect(visible("Clientes")).toBe(true);
    });

    it("conserva el cierre tras filtros, props equivalentes y remontaje de Inertia", async () => {
        render();
        await group("Clientes").trigger("click");
        await navigate("/clientes/17?tab=general#datos", "clientes.show");
        inertia.page.props.navigation = navigation();
        await nextTick();
        expect(visible("Clientes")).toBe(false);
        wrapper.unmount();
        render();
        expect(visible("Clientes")).toBe(false);
    });

    it("descarta la expansión al cambiar el árbol autorizado y no inventa entradas", async () => {
        render();
        await group("Originación").trigger("click");
        inertia.page.props.navigation = [
            {
                label: "Cuenta",
                items: [{ label: "Perfil", to: "profile.show" }],
            },
        ];
        await nextTick();
        expect(wrapper.text()).not.toContain("Clientes");
        expect(wrapper.text()).not.toContain("Originación");
        expect(wrapper.find('[aria-current="page"]').exists()).toBe(false);
        inertia.page.props.navigation = undefined;
        await nextTick();
        expect(wrapper.findAll("button, a")).toHaveLength(0);
    });

    it("mantiene móvil y overlay abiertos al alternar y los cierra al elegir destino", async () => {
        const { layoutState } = useLayout();
        render();
        layoutState.staticMenuMobileActive = true;
        layoutState.overlayMenuActive = true;
        await group("Clientes").trigger("click");
        await group("Clientes").trigger("click");
        expect(layoutState.staticMenuMobileActive).toBe(true);
        expect(layoutState.overlayMenuActive).toBe(true);
        await group("Listado").trigger("click");
        expect(layoutState.staticMenuMobileActive).toBe(false);
        expect(layoutState.overlayMenuActive).toBe(false);
    });

    it("conserva foco al alternar y no ejecuta comandos de grupos deshabilitados", async () => {
        const command = vi.fn();
        inertia.page.props.navigation[0].items[1].disabled = true;
        inertia.page.props.navigation[0].items[1].command = command;
        render();
        group("Clientes").element.focus();
        await group("Clientes").trigger("click");
        expect(document.activeElement).toBe(group("Clientes").element);
        expect(group("Originación").attributes("disabled")).toBeDefined();
        await group("Originación").trigger("click");
        expect(command).not.toHaveBeenCalled();
        expect(visible("Originación")).toBe(false);
    });
});
