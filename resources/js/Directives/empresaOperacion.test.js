import { mount } from "@vue/test-utils";
import { nextTick, reactive } from "vue";
import { describe, expect, it, vi } from "vitest";
import empresaOperacion from "./empresaOperacion";

const { page } = vi.hoisted(() => ({ page: { props: {} } }));
vi.mock("@inertiajs/vue3", () => ({ usePage: () => page }));

describe("restricción operativa de empresa", () => {
    it("deshabilita la acción y la restaura al activar, conservando otras restricciones", async () => {
        page.props = reactive({ empresaOperacion: { motivo: "Activa la empresa.", capacidades: { originar: false } } });
        const click = vi.fn();
        const wrapper = mount({ props: { disabled: Boolean }, template: '<button v-empresa-operacion="\'originar\'" :disabled="disabled" @click="click">Enviar</button>', methods: { click } },
            { global: { directives: { "empresa-operacion": empresaOperacion } } });
        await nextTick();
        expect(wrapper.attributes("title")).toBe("Activa la empresa.");
        expect(wrapper.element.disabled).toBe(true);
        wrapper.element.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
        expect(click).not.toHaveBeenCalled();
        page.props.empresaOperacion.capacidades.originar = true;
        await nextTick();
        expect(wrapper.element.disabled).toBe(false);
        await wrapper.setProps({ disabled: true });
        expect(wrapper.element.disabled).toBe(true);
        wrapper.unmount();
    });
});
