import { mount } from "@vue/test-utils";
import { reactive, nextTick } from "vue";
import { describe, it, expect, vi } from "vitest";
import Form from "./Form.vue";

vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" },
    useForm: (data) => reactive({ ...data, errors: {}, processing: false }),
}));
const select = {
    props: ["options", "modelValue", "inputId", "disabled"],
    emits: ["update:modelValue"],
    template:
        '<select :id="inputId" :disabled="disabled" :value="modelValue" @change="$emit(\'update:modelValue\', Number($event.target.value) || $event.target.value)"><option v-for="o in options" :value="o.id ?? o.value">{{ o.label }}</option></select>',
};
const product = (id, metodo, maximo = 24) => ({
    id,
    numero: 1,
    producto: { nombre: `Producto ${id}` },
    monto_minimo: "100",
    monto_maximo: "10000",
    capacidades: {
        problemas: [],
        opciones: {
            metodos_amortizacion: [
                {
                    value: metodo,
                    label: `Etiqueta ${metodo}`,
                    ayuda: "Ayuda recibida del backend",
                },
            ],
            periodicidades: [
                {
                    value: "mensual",
                    label: "Mensual",
                    plazo_minimo: 1,
                    plazo_maximo: maximo,
                    ayuda: "Calendario contractual",
                },
            ],
        },
    },
});
function render() {
    return mount(Form, {
        props: {
            capacidadesCredito: {},
            productos: [
                product(1, "cuota_nivelada"),
                product(2, "capital_fijo", 3),
            ],
            solicitud: {
                id: 1,
                cliente_id: 5,
                producto_version_id: 1,
                metodo: "cuota_nivelada",
                periodicidad: "mensual",
                plazo: 12,
                cliente: { primer_nombre: "Ana" },
                sucursal: { nombre: "Matriz" },
            },
        },
        global: {
            mocks: { route: () => "/" },
            directives: { "empresa-operacion": {} },
            stubs: {
                AppLayout: {
                    template: '<main><slot name="card-content" /></main>',
                },
                Select: select,
                Message: { template: "<div><slot /></div>" },
                InputText: {
                    props: ["modelValue", "disabled"],
                    template:
                        '<input :value="modelValue" :disabled="disabled" />',
                },
                Button: true,
                InputNumber: true,
                Textarea: true,
            },
        },
    });
}
describe("capacidades de solicitud", () => {
    it("muestra etiquetas y ayudas del backend sin reconstruir métodos", () => {
        const wrapper = render();
        expect(wrapper.get("#sol-metodo").text()).toBe(
            "Etiqueta cuota_nivelada",
        );
        expect(wrapper.text()).toContain("Ayuda recibida del backend");
    });
    it("conserva periodicidad compatible y limpia método y plazo incompatibles al cambiar producto", async () => {
        const wrapper = render();
        await wrapper.get("#sol-producto").setValue("2");
        await nextTick();
        expect(wrapper.get("#sol-periodicidad").element.value).toBe("mensual");
        expect(wrapper.get("#sol-plazo").element.value).toBe("");
        expect(wrapper.text()).toContain(
            "selección anterior no está permitida",
        );
        expect(wrapper.get("#sol-metodo").text()).toBe("Etiqueta capital_fijo");
    });
    it("explica duración y política Bullet desde capacidades y limpia la ayuda al cambiar de producto", async () => {
        const wrapper = render();
        const bullet = product(3, "bullet");
        bullet.capacidades.opciones.politicas_interes_bullet = [
            {
                value: "al_vencimiento",
                label: "Intereses al vencimiento",
                ayuda: "Sin capitalización",
            },
        ];
        await wrapper.setProps({
            productos: [product(1, "cuota_nivelada"), bullet],
        });
        await wrapper.get("#sol-producto").setValue("3");
        expect(wrapper.text()).toContain(
            "Número de periodos hasta vencer el capital",
        );
        expect(wrapper.text()).toContain(
            "Intereses al vencimiento. Sin capitalización",
        );
        await wrapper.get("#sol-producto").setValue("1");
        expect(wrapper.text()).not.toContain(
            "Intereses al vencimiento. Sin capitalización",
        );
    });
});
