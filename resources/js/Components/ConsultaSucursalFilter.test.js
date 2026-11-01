import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import ConsultaSucursalFilter from "./ConsultaSucursalFilter.vue";

const render = (modelValue) =>
    mount(ConsultaSucursalFilter, {
        props: { modelValue, sucursales: [{ id: 2, nombre: "Remota" }] },
        global: {
            stubs: {
                Select: {
                    props: ["options", "modelValue"],
                    template: "<select />",
                },
                Message: { template: "<div><slot /></div>" },
            },
        },
    });
describe("Alcance de consulta", () => {
    it("ofrece sucursal explícita solo en consulta institucional", () => {
        expect(render({ scope: "current" }).findAll("select")).toHaveLength(1);
        expect(render({ scope: "all" }).findAll("select")).toHaveLength(2);
    });
    it("explica la ausencia de contexto sin anunciar una cartera vacía", () => {
        expect(
            render({ scope: "current", requiereSucursal: true }).text(),
        ).toContain("Selecciona una sucursal activa");
        expect(
            render({ scope: "all", requiereSucursal: true }).text(),
        ).not.toContain("Selecciona una sucursal activa");
    });
});
