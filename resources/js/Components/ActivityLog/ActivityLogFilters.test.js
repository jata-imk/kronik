import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import ActivityLogFilters from "./ActivityLogFilters.vue";

const Select = {
    props: ["id", "options", "modelValue", "showClear"], emits: ["update:modelValue", "change"],
    template: '<select :id="id" :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value ? Number($event.target.value) : null); $emit(\'change\')"><option v-if="showClear" value="">Todos</option><option v-for="option in options" :key="option.id ?? option" :value="option.id ?? option">{{ option.name ?? option }}</option></select>',
};
const DatePicker = { name: "DatePicker", props: ["modelValue"], emits: ["update:modelValue", "date-select"], template: "<div />" };
function render(allowAllTeams = false) {
    return mount(ActivityLogFilters, { props: {
        filters: { team_id: 1, sucursal_id: 2, user_id: 3, event: "login", subject_type: "User", search: "Ana", date_from: "2026-10-01", date_to: "2026-10-28", per_page: 20 },
        filterOptions: { allowAllTeams, teams: [{ id: 1, name: "Actual" }, { id: 7, name: "Otro autorizado" }], sucursales: [{ id: 2, name: "Matriz" }, { id: 9, name: "Norte" }], users: [], events: [], subjectTypes: [] },
    }, global: { stubs: {
        Panel: { template: "<div><slot /></div>" }, Select, DatePicker,
        InputText: { props: ["modelValue"], template: '<input :value="modelValue" />' },
        Button: { props: ["label"], emits: ["click"], template: '<button @click="$emit(\'click\')">{{ label }}</button>' },
    } } });
}

describe("Contextos de los filtros de actividad", () => {
    it("cambiar equipo limpia dependencias y conserva búsqueda y fechas", async () => {
        const wrapper = render();
        await wrapper.get("#activity-team").setValue("7");
        expect(wrapper.emitted("apply")[0][0]).toMatchObject({ team_id: 7, sucursal_id: null, user_id: null, event: null, subject_type: null, search: "Ana", date_from: "2026-10-01" });
        expect(wrapper.get("#activity-team").findAll("option")).toHaveLength(2);
    });
    it("solo Super Admin puede quitar el equipo y seleccionar el contexto global", async () => {
        const wrapper = render(true);
        await wrapper.get("#activity-team").setValue("");
        expect(wrapper.emitted("apply")[0][0]).toMatchObject({ team_id: null, sucursal_id: null });
    });
    it("cambiar sucursal mantiene equipo y limpia opciones dependientes", async () => {
        const wrapper = render();
        await wrapper.get("#activity-branch").setValue("9");
        expect(wrapper.emitted("apply")[0][0]).toMatchObject({ team_id: 1, sucursal_id: 9, user_id: null, event: null, subject_type: null });
    });
    it("sincroniza el periodo al limpiar y conserva fechas locales al aplicar", async () => {
        const wrapper = render();
        await wrapper.setProps({ filters: { ...wrapper.props("filters"), date_from: "", date_to: "" } });
        expect(wrapper.findComponent(DatePicker).props("modelValue")).toEqual([]);
        wrapper.findComponent(DatePicker).vm.$emit("update:modelValue", [new Date(2026, 9, 28, 23), null]);
        wrapper.findComponent(DatePicker).vm.$emit("date-select");
        await wrapper.vm.$nextTick();
        await wrapper.findAll("button")[0].trigger("click");
        expect(wrapper.emitted("apply")[0][0]).toMatchObject({ date_from: "2026-10-28", date_to: "" });
    });
});
