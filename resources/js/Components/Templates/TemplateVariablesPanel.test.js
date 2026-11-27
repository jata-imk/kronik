import { mount } from "@vue/test-utils";
import { expect, it } from "vitest";
import TemplateVariablesPanel from "./TemplateVariablesPanel.vue";
const variables = [{ clave: "nombre", nombre: "Primer nombre", origen: "Cliente", formato: "Texto" }, { clave: "fecha_vencimiento", nombre: "Fecha", origen: "Evento", formato: "Fecha" }];
it("agrupa y busca las variables aprobadas sin cambiar su sintaxis", async () => {
    const w = mount(TemplateVariablesPanel, { props: { variables } });
    expect(w.findAll("h4")).toHaveLength(1);
    expect(w.findAll("button")).toHaveLength(2);
    await w.get("input").setValue("Primer");
    expect(w.findAll("button")).toHaveLength(1);
    expect(w.get("code").text()).toBe("{nombre}");
    await w.get("button").trigger("click");
    expect(w.emitted("insert")[0][0].clave).toBe("nombre");
    await w.setProps({ readonly: true });
    await w.get("button").trigger("click");
    expect(w.emitted("insert")).toHaveLength(1);
});

