import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import TeamRoleCards from "./TeamRoleCards.vue";

vi.stubGlobal("route", (name, params) => `${name}?team=${params.team}`);
const render = (roles = []) => mount(TeamRoleCards, {
    props: { teamId: 7, roles },
    global: { mocks: { route: (name, params) => `${name}?team=${params.team}` }, stubs: {
        Link: { props: ["href"], template: '<a :href="href"><slot /></a>' },
        CollectionSummary: { props: ["items"], template: '<span>{{ items.join(", ") }}</span>' },
    } },
});

describe("Tarjetas de roles del equipo", () => {
    it("coloca primero la configuración completa y conserva el equipo seleccionado", () => {
        const wrapper = render([{ id: 2, name: "Auditor", members_count: 1, modules: ["Actividad"] }]);
        expect(wrapper.findAll("li")).toHaveLength(2);
        expect(wrapper.find("li a").attributes("href")).toBe("admin.roles.index?team=7");
        expect(wrapper.get("a").text()).toContain("todos los roles de este equipo");
        expect(wrapper.findAll("li")[1].text()).toContain("1 persona activa");
        expect(wrapper.text()).toContain("Actividad");
    });
    it("mantiene el acceso completo y explica un resumen sin integrantes activos", () => {
        const wrapper = render();
        expect(wrapper.findAll("li")).toHaveLength(1);
        expect(wrapper.get("a").attributes("aria-label")).toBe("Administrar roles");
        expect(wrapper.text()).toContain("No hay roles con integrantes activos");
    });
});
