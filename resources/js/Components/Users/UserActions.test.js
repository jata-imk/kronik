import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import UserActions from "./UserActions.vue";

const { auth, confirmation, remove, put } = vi.hoisted(() => ({ auth: { user: { id: 1 }, is_super_admin: false, permissions: {} }, confirmation: vi.fn(), remove: vi.fn(), put: vi.fn() }));
vi.mock("@inertiajs/vue3", () => ({ usePage: () => ({ props: { auth } }), router: { delete: remove, put, post: vi.fn() } }));
vi.mock("primevue/useconfirm", () => ({ useConfirm: () => ({ require: confirmation }) }));
vi.mock("primevue/usetoast", () => ({ useToast: () => ({ add: vi.fn() }) }));
vi.stubGlobal("route", (name) => name);
function render(overrides = {}) {
    return mount(UserActions, { props: { user: { id: 2, name: "Ana", status: "active", team_roles: [{ team_id: 3, role_ids: [4] }] }, team: { id: 3, name: "Operaciones" }, canRemove: true, ...overrides }, global: { stubs: { Button: { props: ["ariaLabel"], emits: ["click"], template: '<button :aria-label="ariaLabel" @click="$emit(\'click\')" />' } } } });
}
describe("Acciones de usuario", () => {
    it("separa la autorización de membresía de las acciones globales", () => {
        auth.permissions = {};
        const wrapper = render();
        expect(wrapper.findAll("button")).toHaveLength(1);
        expect(wrapper.get("button").attributes("aria-label")).toBe("Quitar a Ana del equipo");
    });
    it("explica que desactivar afecta todos los equipos y protege al responsable de la salida", async () => {
        auth.permissions = { "delete-users": true };
        const wrapper = render({ user: { id: 2, name: "Ana", status: "active", is_owner: true } });
        expect(wrapper.findAll("button")).toHaveLength(1);
        await wrapper.get("button").trigger("click");
        expect(confirmation.mock.lastCall[0].message).toContain("todos los equipos");
        confirmation.mock.lastCall[0].accept();
        expect(remove.mock.lastCall[0]).toBe("admin.users.destroy");
    });
    it("reactiva sin omitir asignaciones de otros equipos", async () => {
        auth.permissions = { "update-users": true };
        const teamRoles = [{ team_id: 3, role_ids: [4] }, { team_id: 5, role_ids: [6] }];
        const wrapper = render({ user: { id: 2, name: "Ana", status: "inactive", team_roles: teamRoles } });
        await wrapper.get('[aria-label="Reactivar cuenta de Ana"]').trigger("click");
        confirmation.mock.lastCall[0].accept();
        expect(put.mock.lastCall[1]).toMatchObject({ status: "active", team_roles: teamRoles });
    });
});
