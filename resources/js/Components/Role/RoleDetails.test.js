import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import RoleDetails from "./RoleDetails.vue";
import RoleModules from "./RoleModules.vue";

const permission = { id: 1, module_id: 1, name: "read clientes", etiqueta: "Consultar clientes", descripcion: "Consultar el expediente según la política de acceso." };
const module = { id: 1, name: "clientes", etiqueta: "Clientes", descripcion: "Gestión de clientes." };
const help = { props: ["title", "description"], template: '<button :aria-label="`Ayuda: ${title}`">{{ description }}</button>' };
const stubs = { ContextHelp: help, Avatar: true, Tag: { props: ["value"], template: "<span>{{ value }}</span>" } };

describe("Roles legibles", () => {
    it("presenta etiquetas y ayuda sin exponer claves técnicas", () => {
        const wrapper = mount(RoleDetails, { props: { role: { name: "Tesorería" }, selectedRoleAvatarLabel: "TE", permissionsByModule: { Clientes: [permission] } }, global: { stubs } });
        expect(wrapper.text()).toContain("Consultar clientes");
        expect(wrapper.text()).not.toContain("read clientes");
        expect(wrapper.get('[aria-label="Ayuda: Consultar clientes"]').text()).toContain("política de acceso");
        expect(wrapper.findComponent({ name: "Avatar" }).attributes("class")).toContain("shrink-0");
    });
    it("deshabilita la matriz para quien solamente consulta", () => {
        const wrapper = mount(RoleModules, { props: { modules: [module], permissions: [permission], selectedPermissions: [1], readonly: true }, global: { stubs: { ContextHelp: help, Listbox: { props: ["disabled", "optionLabel"], template: '<section :data-disabled="disabled" :data-label="optionLabel" />' } } } });
        expect(wrapper.text()).toContain("Clientes");
        expect(wrapper.get("section").attributes("data-disabled")).toBe("true");
        expect(wrapper.get("section").attributes("data-label")).toBe("etiqueta");
    });
});
