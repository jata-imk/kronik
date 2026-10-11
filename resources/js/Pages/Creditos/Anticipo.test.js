import { mount, flushPromises } from "@vue/test-utils";
import { reactive } from "vue";
import { describe, expect, it, vi } from "vitest";
import axios from "axios";
import Anticipo from "./Anticipo.vue";

vi.mock("axios", () => ({ default: { post: vi.fn() } }));
vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" },
    useForm: (data) => {
        const form = reactive({
            ...data,
            errors: {},
            processing: false,
            post: vi.fn(),
            clearErrors: vi.fn(),
        });
        form.data = () => ({ ...form });
        form.transform = vi.fn(() => form);
        return form;
    },
}));
vi.stubGlobal("route", (name) => name);

function render() {
    return mount(Anticipo, {
        props: {
            credito: { id: 1, cliente: {} },
            hoy: "2026-10-10",
            situacion: { resumen: null, error: null },
            habilitado: true,
            operaciones: [{ value: "liquidacion", label: "Liquidación total" }],
            opcionales: [],
        },
        global: {
            mocks: { route: (name) => name },
            stubs: {
                AppLayout: {
                    template: '<main><slot name="card-content" /></main>',
                },
                Button: {
                    props: ["label", "disabled"],
                    template:
                        '<button :disabled="disabled">{{ label }}</button>',
                },
                InputText: {
                    props: ["modelValue"],
                    emits: ["update:modelValue"],
                    template:
                        '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                },
                InputNumber: {
                    props: ["modelValue"],
                    template: "<span>{{ modelValue }}</span>",
                },
                Message: { template: "<div><slot /></div>" },
                Select: true,
                Checkbox: true,
                Tag: true,
                DistribucionPago: true,
                SerieFinanciera: true,
                DataTable: true,
                Column: true,
            },
        },
    });
}
const quote = {
    previa_hash: "cotizacion",
    importe: "3030.00",
    capital_anticipado: "3000.00",
    asignaciones: [],
    despues: { capital_insoluto: "0.00", estado_operativo: "liquidado" },
    proyeccion: { cuotas: 0, intereses: "0.00" },
    cronograma: { tabla: [] },
};

describe("Cotización de anticipos y liquidación", () => {
    it("conserva la cotización exacta al completar el total y exige confirmación", async () => {
        axios.post.mockResolvedValueOnce({ data: quote });
        const wrapper = render();
        await wrapper.get("form").trigger("submit");
        await flushPromises();
        expect(wrapper.text()).toContain(
            "Esta operación liquidará el crédito.",
        );
        expect(wrapper.get('[input-id="anticipo-importe"]').text()).toBe(
            "3030",
        );
        expect(
            wrapper.findAll("button").at(-1).attributes("disabled"),
        ).toBeDefined();
        await wrapper.get("#anticipo-referencia").setValue("CAMBIO-001");
        expect(wrapper.text()).not.toContain(
            "Esta operación liquidará el crédito.",
        );
        wrapper.unmount();
    });
    it("descarta una cotización recibida después de cambiar la fecha", async () => {
        let resolve;
        axios.post.mockImplementationOnce(
            () =>
                new Promise((done) => {
                    resolve = done;
                }),
        );
        const wrapper = render();
        await wrapper.get("form").trigger("submit");
        await wrapper.get("#anticipo-fecha").setValue("2026-10-09");
        resolve({ data: quote });
        await flushPromises();
        expect(wrapper.text()).not.toContain(
            "Esta operación liquidará el crédito.",
        );
        wrapper.unmount();
    });
});
