import { mount } from "@vue/test-utils";
import { defineComponent, reactive, ref } from "vue";
import { afterEach, describe, expect, it, vi } from "vitest";
import { useListadoServidor } from "./useListadoServidor";

const mocks = vi.hoisted(() => ({ get: vi.fn(), page: null }));
vi.mock("@inertiajs/vue3", () => ({ router: { get: mocks.get }, usePage: () => mocks.page }));
afterEach(() => { vi.useRealTimers(); mocks.get.mockClear(); });

function render() {
    mocks.page = reactive({ props: { listado: { filters: { columnas: { global: "Inicial" } } } } });
    const filters = ref({ global: { value: null }, status_label: { value: null } });
    const wrapper = mount(defineComponent({ setup() { useListadoServidor(filters); return {}; }, template: "<div />" }));
    return { filters, wrapper };
}

describe("Listado en servidor", () => {
    it("agrupa escritura rápida, reinicia página y conserva el equipo seleccionado", async () => {
        vi.useFakeTimers();
        window.history.replaceState({}, "", "/admin/roles?team=12&page=4&selected_role_id=9");
        const { filters, wrapper } = render();
        filters.value.global.value = "O";
        filters.value.global.value = "Objetivo";
        await vi.advanceTimersByTimeAsync(300);
        expect(mocks.get).toHaveBeenCalledTimes(1);
        const query = mocks.get.mock.calls[0][1];
        expect(query).toMatchObject({ team: "12", selected_role_id: "9", page: 1 });
        expect(JSON.parse(query.columnas).global).toBe("Objetivo");
        wrapper.unmount();
    });
    it("restaura filtros de navegación histórica sin lanzar otra consulta", async () => {
        vi.useFakeTimers();
        const { filters, wrapper } = render();
        mocks.page.props.listado.filters.columnas = { global: "Histórico", status_label: "Activo" };
        await vi.advanceTimersByTimeAsync(500);
        expect(filters.value.global.value).toBe("Histórico");
        expect(mocks.get).not.toHaveBeenCalled();
        wrapper.unmount();
    });
    it("cancela búsquedas pendientes al salir de la página", async () => {
        vi.useFakeTimers();
        const { filters, wrapper } = render();
        filters.value.global.value = "Pendiente";
        wrapper.unmount();
        await vi.advanceTimersByTimeAsync(500);
        expect(mocks.get).not.toHaveBeenCalled();
    });
});
