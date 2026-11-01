import { mount } from "@vue/test-utils";
import { defineComponent } from "vue";
import { beforeEach, describe, expect, it, vi } from "vitest";
import axios from "axios";
import { operationalDestination, useOperationalContext } from "./useOperationalContext";
vi.mock("axios", () => ({ default: { get: vi.fn() } }));
vi.mock("@inertiajs/vue3", () => ({ router: { cancel: vi.fn() }, usePage: () => ({ props: { contextoOperativo: { team_id: 1, sucursal_id: 2 } } }) }));
beforeEach(() => vi.clearAllMocks());
describe("Restauración de contexto operativo", () => {
    it("sale de formularios y limpia filtros anteriores sin cambiar recursos globales", () => {
        expect(operationalDestination("/solicitudes/2/edit?scope=all&page=8")).toBe("/solicitudes");
        expect(operationalDestination("/creditos/3/pagos/create")).toBe("/creditos");
        expect(operationalDestination("/consultas-sic/4")).toBe("/clientes/historial-crediticio");
        expect(operationalDestination("/clientes/expedientes?scope=all")).toBe("/clientes/expedientes");
        expect(operationalDestination("/mi-trabajo?scope=all&sucursal_id=2&page=3&buscar=Folio")).toBe("/mi-trabajo?buscar=Folio");
        expect(operationalDestination("/productos-crediticios?producto_id=8&page=3")).toBe("/productos-crediticios?producto_id=8&page=3");
        expect(operationalDestination("/admin/roles?team=4", true)).toBe("/dashboard");
    });
    it("consulta contexto sin depender de autorización de la página abierta y agrupa focos simultáneos", () => {
        axios.get.mockImplementation(() => new Promise(() => {}));
        const wrapper = mount(defineComponent({ setup() { useOperationalContext(); }, template: "<div />" }));
        window.dispatchEvent(new Event("focus"));
        window.dispatchEvent(new Event("popstate"));
        expect(axios.get).toHaveBeenCalledTimes(1);
        expect(axios.get.mock.calls[0][0]).toBe("/contexto-operativo");
        const signal = axios.get.mock.calls[0][1].signal;
        wrapper.unmount();
        expect(signal.aborted).toBe(true);
        window.dispatchEvent(new Event("focus"));
        expect(axios.get).toHaveBeenCalledTimes(1);
    });
});
