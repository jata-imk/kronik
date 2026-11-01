import { mount } from "@vue/test-utils";
import { defineComponent, nextTick, ref } from "vue";
import { describe, expect, it, vi } from "vitest";
import { useOpcionesRemotas } from "./useOpcionesRemotas";
import axios from "axios";
vi.mock("axios", () => ({ default: { get: vi.fn(), isCancel: error => error.code === "ERR_CANCELED" } }));
function render() {
    const initial = ref([{ id: 1, name: "Seleccionada" }]);
    let api;
    const wrapper = mount(defineComponent({ setup() { api = useOpcionesRemotas(() => initial.value, () => "/opciones", () => 1); return {}; }, template: "<div />" }));
    return { initial, wrapper, api };
}
describe("Opciones remotas", () => {
    it("descarta respuestas tardías y conserva la selección fuera de los resultados", async () => {
        let firstResolve;
        axios.get.mockImplementationOnce(() => new Promise(resolve => { firstResolve = resolve; })).mockResolvedValueOnce({ data: [{ id: 3 }] });
        const { api, wrapper } = render();
        const first = api.search({ value: "Anterior" });
        await api.search({ value: "Actual" });
        firstResolve({ data: [{ id: 2 }] }); await first;
        expect(api.options.value.map(item => item.id)).toEqual([1, 3]);
        expect(axios.get.mock.calls[0][1].signal.aborted).toBe(true);
        wrapper.unmount();
    });
    it("un cambio de contexto invalida resultados pendientes y limpia errores", async () => {
        let resolve;
        axios.get.mockImplementationOnce(() => new Promise(done => { resolve = done; }));
        const { api, initial, wrapper } = render();
        const pending = api.search({ query: "Sucursal A" });
        initial.value = [{ id: 4 }]; await nextTick();
        resolve({ data: [{ id: 2 }] }); await pending;
        expect(api.options.value).toEqual([{ id: 4 }]);
        expect(api.loading.value).toBe(false);
        wrapper.unmount();
    });
    it("muestra un error español recuperable y cancela al salir", async () => {
        axios.get.mockRejectedValueOnce(new Error("network"));
        const { api, wrapper } = render();
        await api.search({ value: "Buscar" });
        expect(api.error.value).toContain("No fue posible buscar");
        wrapper.unmount();
    });
});
