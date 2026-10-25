import { mount } from "@vue/test-utils";
import { reactive } from "vue";
import { describe, expect, it, vi } from "vitest";
import TeamGeneralActions from "./TeamGeneralActions.vue";

const { put, remove, requireConfirmation } = vi.hoisted(() => ({ put: vi.fn(), remove: vi.fn(), requireConfirmation: vi.fn() }));
vi.mock("@inertiajs/vue3", () => ({ useForm: (data) => reactive({ ...data, errors: {}, processing: false, put, delete: remove, reset: vi.fn() }) }));
vi.mock("primevue/useconfirm", () => ({ useConfirm: () => ({ require: requireConfirmation }) }));
vi.stubGlobal("route", (name, id) => `${name}/${id}`);

function render(capabilities = {}) {
    return mount(TeamGeneralActions, { props: { team: { id: 2, name: "Operaciones", activo: true }, capabilities, candidates: [{ id: 3, name: "Integrante" }] }, global: { stubs: {
        Card: { template: '<section><slot name="title" /><slot name="content" /></section>' },
        ContextHelp: true, Message: true,
        Button: { props: ["label", "disabled"], emits: ["click"], template: '<button :disabled="disabled" @click="$emit(\'click\')">{{ label }}</button>' },
        Select: { props: ["options", "modelValue"], emits: ["update:modelValue"], template: '<select @change="$emit(\'update:modelValue\', 3)"><option value=""></option><option value="3">Integrante</option></select>' },
    } } });
}

describe("Responsable del equipo", () => {
    it("no ofrece transferencia ni cambios de estado sin los permisos del equipo seleccionado", () => {
        const wrapper = render();
        expect(wrapper.findAll("button")).toHaveLength(0);
        expect(wrapper.text()).toContain("persona de contacto");
    });
    it("confirma antes de transferir y usa el equipo seleccionado", async () => {
        const wrapper = render({ "update-teams": true });
        await wrapper.get("select").setValue("3");
        await wrapper.get("button").trigger("click");
        expect(requireConfirmation.mock.lastCall[0].message).toContain("informativo");
        requireConfirmation.mock.lastCall[0].accept();
        expect(put.mock.lastCall[0]).toBe("teams.responsible.update/2");
    });
});
