import { mount } from "@vue/test-utils";
import { expect, it, vi } from "vitest";
import SerieFinanciera from "./SerieFinanciera.vue";

vi.mock("./GraficaFinanciera.vue", () => ({
    default: { template: "<div />" },
}));
vi.mock("@sakai-vue/layout/composables/layout", () => ({
    useLayout: () => ({ layoutConfig: {} }),
}));

it("conserva los centavos originales en la tabla aunque la escala de la gráfica use números", () => {
    const wrapper = mount(SerieFinanciera, {
        props: {
            titulo: "Capital",
            filas: [{ etiqueta: "Octubre", capital: "9999999999999999.99" }],
            series: [{ clave: "capital", nombre: "Capital pendiente" }],
        },
    });
    expect(wrapper.get("tbody").text()).toContain("$9,999,999,999,999,999.99");
    wrapper.unmount();
});
