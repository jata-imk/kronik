import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import DocumentFileInput from "./DocumentFileInput.vue";

const create = (props = {}) =>
    mount(DocumentFileInput, {
        props,
        global: {
            stubs: {
                Button: {
                    props: ["label"],
                    template: "<button>{{ label }}</button>",
                },
                Message: { template: '<div role="alert"><slot /></div>' },
            },
        },
    });
async function select(wrapper, file) {
    const input = wrapper.get('input[type="file"]');
    Object.defineProperty(input.element, "files", {
        configurable: true,
        value: [file],
    });
    await input.trigger("change");
}

describe("DocumentFileInput", () => {
    it("valida tamaño en español y permite corregir la selección", async () => {
        const wrapper = create();
        const oversized = new File(["pdf"], "grande.pdf");
        Object.defineProperty(oversized, "size", { value: 10485761 });
        await select(wrapper, oversized);
        expect(wrapper.get('[role="alert"]').text()).toBe(
            "El archivo no debe superar 10 MB.",
        );
        const valid = new File(["pdf"], "correcto.pdf");
        await select(wrapper, valid);
        expect(wrapper.find('[role="alert"]').exists()).toBe(false);
        expect(wrapper.emitted("update:modelValue").at(-1)).toEqual([valid]);
        expect(wrapper.emitted("clear-error")).toHaveLength(2);
    });
    it("quita el archivo y solicita borrar el error del servidor", async () => {
        const wrapper = create({
            modelValue: new File(["pdf"], "documento.pdf"),
            error: "Archivo inválido",
        });
        await wrapper.findAll("button")[1].trigger("click");
        expect(wrapper.emitted("update:modelValue").at(-1)).toEqual([null]);
        expect(wrapper.emitted("clear-error")).toHaveLength(1);
    });
    it("rechaza extensiones ajenas al visor privado", async () => {
        const wrapper = create();
        await select(wrapper, new File(["svg"], "activo.svg"));
        expect(wrapper.get('[role="alert"]').text()).toBe(
            "Selecciona un archivo PDF, JPG o PNG.",
        );
        expect(wrapper.emitted("update:modelValue").at(-1)).toEqual([null]);
    });
});
