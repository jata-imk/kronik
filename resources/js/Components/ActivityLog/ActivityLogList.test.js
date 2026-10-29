import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import ActivityLogList from "./ActivityLogList.vue";

const { get } = vi.hoisted(() => ({ get: vi.fn() }));
vi.mock("@inertiajs/vue3", () => ({ router: { get } }));
vi.stubGlobal("route", (name) => name);
const Filters = { name: "ActivityLogFilters", props: ["filters"], emits: ["apply", "clear"], template: "<div />" };
const Paginator = { name: "Paginator", emits: ["page"], template: "<div />" };
const render = () => mount(ActivityLogList, { props: {
    activityLogs: { data: [{ id: 1 }], last_page: 3, current_page: 1, per_page: 10, total: 25 },
    filters: { team_id: 7, sucursal_id: 9, user_id: 3, search: "Ana", event: "login", subject_type: "User", date_from: "2026-10-01", date_to: "2026-10-28", per_page: 10 },
    filterOptions: {},
}, global: { stubs: { ActivityLogFilters: Filters, ActivityLogTable: true, Paginator, Button: true, Card: true } } });

describe("Navegación de la bitácora", () => {
    beforeEach(() => get.mockClear());
    it("refresca título y regreso junto con resultados al seleccionar otro contexto", () => {
        const wrapper = render();
        wrapper.findComponent(Filters).vm.$emit("apply", { team_id: 8, sucursal_id: null });
        expect(get.mock.lastCall[1]).toMatchObject({ team_id: 8, sucursal_id: null, page: 1 });
        expect(get.mock.lastCall[2].only).toContain("activityTeam");
    });
    it("limpiar conserva el equipo y restablece sucursal, periodo y tamaño de página", () => {
        const wrapper = render();
        wrapper.findComponent(Filters).vm.$emit("clear");
        expect(get.mock.lastCall[1]).toMatchObject({ team_id: 7, sucursal_id: null, user_id: null, search: "", event: null, date_from: "", date_to: "", per_page: 20, page: 1 });
    });
    it("paginar conserva el equipo, sucursal y filtros de búsqueda", () => {
        const wrapper = render();
        wrapper.findComponent(Paginator).vm.$emit("page", { first: 10, rows: 10 });
        expect(get.mock.lastCall[1]).toMatchObject({ team_id: 7, sucursal_id: 9, search: "Ana", page: 2, per_page: 10 });
    });
});
