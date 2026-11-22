import { flushPromises, shallowMount } from "@vue/test-utils";
import { afterEach, expect, it, vi } from "vitest";
import Expediente from "./Expediente.vue";
import { forgetUnsavedChanges, hasUnsavedChanges } from "@/Utils/unsavedChanges";

vi.mock("primevue/usetoast", () => ({ useToast: () => ({ add: vi.fn() }) }));
vi.mock("primevue/useconfirm", () => ({ useConfirm: () => ({ require: vi.fn() }) }));
let wrapper;
afterEach(() => { wrapper?.unmount(); forgetUnsavedChanges(); vi.restoreAllMocks(); });
function render() {
    wrapper = shallowMount(Expediente, {
        props: { cliente: { id: 1, documentos: [] }, resumen: {}, opciones: { moneda: "MXN" }, can: {} },
        global: { directives: { "empresa-operacion": {} }, stubs: { AppLayout: true } },
    });
    return wrapper.vm;
}
it("abrir referencias y garantías existentes establece una base limpia", async () => {
    const vm = render();
    vm.openReference({ id: 2, tipo: "personal", nombre: "Referencia sintética", telefono: "5555555555" });
    await flushPromises();
    expect(hasUnsavedChanges()).toBe(false);
    vm.closeEditor("reference");
    vm.openGuarantee({ id: 3, propietario_cliente_id: 1, tipo: "prendaria", descripcion: "Garantía sintética", valor_estimado: "1500.00", moneda: "MXN" });
    await flushPromises();
    expect(hasUnsavedChanges()).toBe(false);
    vm.closeEditor("guarantee");
    vm.openReference();
    await flushPromises();
    expect(vm.referenceForm.nombre).toBe("");
    expect(hasUnsavedChanges()).toBe(false);
});
it("descarte rechazado conserva el diálogo y aceptar limpia archivos y captura", async () => {
    const vm = render();
    vm.openDocumentUpload({ id: 4, tipo: "identificacion", nombre: "Documento sintético" });
    await flushPromises();
    expect(hasUnsavedChanges()).toBe(false);
    vm.documentForm.archivo = new File(["sintético"], "evidencia.pdf", { type: "application/pdf" });
    vm.documentForm.errors.archivo = "Revisa el archivo";
    await flushPromises();
    const confirm = vi.spyOn(window, "confirm").mockReturnValue(false);
    vm.closeEditor("document");
    expect(vm.documentDialog).toBe(true);
    expect(vm.documentForm.errors.archivo).toBe("Revisa el archivo");
    confirm.mockReturnValue(true);
    vm.closeEditor("document");
    await flushPromises();
    expect(vm.documentDialog).toBe(false);
    expect(vm.documentForm.archivo).toBe(null);
    expect(hasUnsavedChanges()).toBe(false);
    vm.openGeneration();
    await flushPromises();
    expect(hasUnsavedChanges()).toBe(false);
    vm.closeEditor("generation");
});
