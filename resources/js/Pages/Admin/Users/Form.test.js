import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import UserForm from "@/Components/Users/UserFormDialog.vue";

const formErrors = vi.hoisted(() => ({}));
const writes = vi.hoisted(() => ({put:vi.fn(), post:vi.fn()}));
vi.mock("@inertiajs/vue3", () => ({
    useForm: (data) => ({
        ...data,
        errors: formErrors,
        processing: false,
        put: writes.put,
        post: writes.post,
    }),
}));
vi.mock("primevue/usetoast", () => ({ useToast: () => ({ add: vi.fn() }) }));

const DialogStub = { props: ["visible"], template: "<section role='dialog'><slot /></section>" };
const MultiSelectStub = {
    name: "MultiSelect",
    props: {
        modelValue: { type: Array, default: () => [] },
        options: { type: Array, default: () => [] },
        placeholder: { type: String, default: "" },
        filter: Boolean,
    },
    template: "<div class='multi-select'>{{ placeholder }}</div>",
};
const passthrough = { template: "<div><slot /></div>" };

const props = {
    teams: [{ id: 1, name: "Operaciones" }],
    roles: [{ id: 10, name: "Promotor", team_id: 1 }],
    sucursales: [{ id: 20, nombre: "Matriz", clave: "MATRIZ" }],
    statusOptions: [{ value: "active", label: "Activo" }],
    canManageSuperAdmin: true,
    prefill: { team_id: 1, role_id: 10, sucursal_id: 20 },
};

const renderForm = (overrides = {}) => mount(UserForm, {
    props: { ...props, ...overrides },
    global: {
        stubs: {
            Dialog: DialogStub,
            MultiSelect: MultiSelectStub,
            Message: passthrough,
            Divider: passthrough,
            InputText: true,
            Select: true,
            Checkbox: true,
            Button: true,
        },
    },
});

describe("Formulario de usuarios", () => {
    it("prellena equipo, rol y sucursal desde el centro de equipo", () => {
        const wrapper = renderForm();
        const selects = wrapper.findAllComponents(MultiSelectStub);
        expect(selects[0].props("modelValue")).toEqual([1]);
        expect(selects[1].props("modelValue")).toEqual([10]);
        expect(selects[2].props("modelValue")).toEqual([20]);
    });

    it("habilita búsqueda en el selector contextual de roles", () => {
        const roleSelect = renderForm().findAllComponents(MultiSelectStub)[1];
        expect(roleSelect.props("filter")).toBe(true);
    });

    it("explica la diferencia entre acceso global y asignaciones explícitas", () => {
        const wrapper = renderForm({ user: { is_super_admin: true, team_roles: [], sucursal_ids: [] } });
        expect(wrapper.text()).toContain("todas las sucursales activas");
        expect(wrapper.text()).toContain("asignaciones explícitas");
    });
});


it("consulta conserva asignaciones vacías y no envía con Enter", async () => {
    const wrapper=renderForm({readonly:true,user:{id:9,name:"Histórico",team_roles:[],sucursal_ids:[],current_team_id:null,sucursal_principal_id:null}});
    expect(wrapper.text()).toContain("Consulta · solo lectura");
    expect(wrapper.findAllComponents(MultiSelectStub)[0].props("modelValue")).toEqual([]);
    expect(wrapper.findAllComponents(MultiSelectStub)[1].props("modelValue")).toEqual([]);
    expect(wrapper.find('button-stub[label="Guardar"]').exists()).toBe(false);
    writes.put.mockClear();writes.post.mockClear();
    await wrapper.find("form").trigger("submit");
    expect(writes.put).not.toHaveBeenCalled();expect(writes.post).not.toHaveBeenCalled();
});
