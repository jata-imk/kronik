import { mount } from "@vue/test-utils";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent } from "vue";
import CodigoPostalAutocomplete from "./CodigoPostalAutocomplete.vue";

const { toastAdd } = vi.hoisted(() => ({ toastAdd: vi.fn() }));

vi.mock("primevue/usetoast", () => ({
    useToast: () => ({ add: toastAdd }),
}));

const response = (data) =>
    Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ data }),
    });

const resultados = [
    {
        id: 154940,
        codigo: "97306",
        pais: { id: 25, nombre_es: "México", codigo_iso: "MX" },
        divisiones_administrativas: {
            nivel_uno: { id: 31, nombre: "Yucatán", tipo: "estado" },
            nivel_dos: { id: 2347, nombre: "Mérida", tipo: "municipio" },
            nivel_tres: {
                id: 157450,
                nombre: "Chichí Suárez",
                tipo: "Colonia",
            },
        },
    },
    {
        id: 154941,
        codigo: "97306",
        pais: { id: 25, nombre_es: "México", codigo_iso: "MX" },
        divisiones_administrativas: {
            nivel_uno: { id: 31, nombre: "Yucatán", tipo: "estado" },
            nivel_dos: { id: 2347, nombre: "Mérida", tipo: "municipio" },
            nivel_tres: { id: 157451, nombre: "Sitpach", tipo: "Pueblo" },
        },
    },
];

const AutoCompleteStub = defineComponent({
    name: "AutoComplete",
    props: {
        modelValue: { type: [String, Object], default: "" },
        suggestions: { type: Array, default: () => [] },
        showEmptyMessage: { type: Boolean, default: true },
    },
    emits: ["complete", "update:model-value", "option-select"],
    template: "<div><input /></div>",
});

const mountComponent = (props = {}) =>
    mount(CodigoPostalAutocomplete, {
        props: { modelValue: "", ...props },
        global: {
            stubs: { AutoComplete: AutoCompleteStub },
        },
    });

const flushPromises = async () => {
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
};

describe("CodigoPostalAutocomplete", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        globalThis.fetch = vi.fn();
        toastAdd.mockReset();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it("muestra un solo CP exacto sin confirmar el domicilio automáticamente", async () => {
        fetch.mockImplementation(() => response(resultados));
        const wrapper = mountComponent({ modelValue: "97306" });
        const autocomplete = wrapper.findComponent({ name: "AutoComplete" });

        expect(autocomplete.props("showEmptyMessage")).toBe(false);
        autocomplete.vm.$emit("complete", { query: "97306" });

        await flushPromises();

        expect(autocomplete.props("suggestions")).toEqual([
            { codigo: "97306" },
        ]);
        expect(wrapper.emitted("confirmed")).toBeUndefined();

        autocomplete.vm.$emit("option-select", {
            value: { codigo: "97306" },
        });
        await flushPromises();

        expect(wrapper.emitted("confirmed")).toHaveLength(1);
        expect(fetch).toHaveBeenCalledTimes(1);
        expect(wrapper.emitted("confirmed")[0][0]).toMatchObject({
            codigo: "97306",
            divisionAdminUno: { nombre: "Yucatán" },
            divisionAdminDos: { nombre: "Mérida" },
            localidades: [
                {
                    codigoPostalId: 154940,
                    divisionAdminTresId: 157450,
                    nombre: "Chichí Suárez",
                },
                {
                    codigoPostalId: 154941,
                    divisionAdminTresId: 157451,
                    nombre: "Sitpach",
                },
            ],
        });
    });

    it("trata seleccionar una sugerencia como confirmación", async () => {
        fetch
            .mockImplementationOnce(() => response([{ codigo: "97306" }]))
            .mockImplementationOnce(() => response(resultados));

        const wrapper = mountComponent({ modelValue: "973" });
        await vi.advanceTimersByTimeAsync(300);

        const autocomplete = wrapper.findComponent({ name: "AutoComplete" });
        expect(autocomplete.props("suggestions")).toEqual([
            { codigo: "97306" },
        ]);

        autocomplete.vm.$emit("option-select", {
            value: { codigo: "97306" },
        });
        await flushPromises();

        expect(fetch).toHaveBeenLastCalledWith(
            "/codigos-postales/buscar?codigo=97306",
            expect.objectContaining({ method: "GET" }),
        );
        expect(wrapper.emitted("confirmed")).toHaveLength(1);
    });

    it("normaliza la captura y avisa que los datos derivados deben limpiarse", () => {
        const wrapper = mountComponent();
        const autocomplete = wrapper.findComponent({ name: "AutoComplete" });

        expect(wrapper.find("input").attributes()).toMatchObject({
            maxlength: "5",
            inputmode: "numeric",
        });

        autocomplete.vm.$emit("update:model-value", "97a3068");

        expect(wrapper.emitted("update:modelValue")[0]).toEqual(["97306"]);
        expect(wrapper.emitted("changed")[0]).toEqual(["97306"]);
    });

    it("muestra el estado vacío sólo cuando la búsqueda exacta falla", async () => {
        fetch.mockResolvedValue({
            ok: false,
            status: 404,
            statusText: "Not Found",
        });
        const wrapper = mountComponent({ modelValue: "99999" });
        const autocomplete = wrapper.findComponent({ name: "AutoComplete" });

        expect(autocomplete.props("showEmptyMessage")).toBe(false);
        autocomplete.vm.$emit("complete", { query: "99999" });
        await flushPromises();

        expect(autocomplete.props("showEmptyMessage")).toBe(true);
        expect(autocomplete.props("suggestions")).toEqual([]);
        expect(toastAdd).toHaveBeenCalledWith(
            expect.objectContaining({
                summary: "Código postal no encontrado",
            }),
        );
    });
});
