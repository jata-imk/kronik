import { shallowMount } from "@vue/test-utils";
import { nextTick, reactive } from "vue";
import { expect, it } from "vitest";
import FormularioDatosFiscales from "./FormularioDatosFiscales.vue";

const genericos = ["XAXX010101000", "XEXX010101000"];
const options = {
    tiposPersona: [],
    regimenesFiscales: [{ id: 1, fisica: true, moral: false }],
    rfcsGenericosPermitidos: genericos,
};
function render(form, readOnly = false) {
    return shallowMount(FormularioDatosFiscales, { props: { form, readOnly, initialLoad: options },
        global: { renderStubDefaultSlot: true } });
}

it.each(genericos.flatMap(rfc => ["fisica", "moral"].map(tipo_persona => ({ rfc, tipo_persona }))))(
    "informa identidad pendiente y permite capturar los 13 caracteres de $rfc para $tipo_persona",
    async ({ rfc, tipo_persona }) => {
        const form = reactive({ tipo_persona, rfc, regimen_fiscal_id: null });
        const w = render(form);
        expect(w.text()).toContain("RFC genérico: identidad fiscal propia pendiente.");
        expect(w.find('input-text-stub[id="rfc"]').attributes("maxlength")).toBe("13");
        form.rfc = tipo_persona === "fisica" ? "GALA910418AB8" : "KDE260101AB1";
        await nextTick();
        expect(w.text()).not.toContain("identidad fiscal propia pendiente");
    },
);

it("consulta muestra el aviso sin normalizar RFC ni limpiar el régimen por watchers", async () => {
    const form = reactive({ tipo_persona: "fisica", rfc: "XAXX010101000", regimen_fiscal_id: 1 });
    const w = render(form, true);
    expect(w.text()).toContain("identidad fiscal propia pendiente");
    form.tipo_persona = "moral";
    form.rfc = " valor histórico ";
    await nextTick();
    expect(form.rfc).toBe(" valor histórico ");
    expect(form.regimen_fiscal_id).toBe(1);
    expect(w.find('input-text-stub[id="rfc"]').attributes("disabled")).toBe("true");
});

it("edición conserva la normalización y limpieza de un régimen incompatible", async () => {
    const form = reactive({ tipo_persona: "fisica", rfc: "", regimen_fiscal_id: 1 });
    render(form);
    form.tipo_persona = "moral";
    form.rfc = " xexx010101000 ";
    await nextTick();
    expect(form.rfc).toBe("XEXX010101000 ");
    expect(form.regimen_fiscal_id).toBeNull();
});
