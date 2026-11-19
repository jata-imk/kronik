import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { effectScope, nextTick } from "vue";
import { useGuardedForm } from "@/Composables/useGuardedForm";
import { changesResource, confirmDiscardChanges, confirmFormDiscard, forgetUnsavedChanges, hasUnsavedChanges, installUnsavedChangesGuard, registerUnsavedChanges } from "./unsavedChanges";

let cleanup;
beforeEach(() => { forgetUnsavedChanges(); history.replaceState({}, "", "/clientes/1/edit"); });
afterEach(() => { cleanup?.(); cleanup = null; vi.restoreAllMocks(); forgetUnsavedChanges(); });
it("cancelar conserva la captura y desmontar deja de bloquear", () => {
    const confirm = vi.spyOn(window, "confirm").mockReturnValue(false);
    let dirty = true;
    const dispose = registerUnsavedChanges(() => dirty);
    expect(confirmDiscardChanges()).toBe(false);
    expect(hasUnsavedChanges()).toBe(true);
    dirty = false;
    expect(confirmDiscardChanges()).toBe(true);
    expect(confirm).toHaveBeenCalledOnce();
    dispose();
    expect(hasUnsavedChanges()).toBe(false);
});
it("distingue navegación y selección de filtros y anclas", () => {
    expect(changesResource("/clientes?buscar=uno", "/clientes?buscar=dos&page=3")).toBe(false);
    expect(changesResource("/clientes/1/edit", "/clientes/1/edit#datos")).toBe(false);
    expect(changesResource("/clientes/1/edit", "/clientes/2/edit")).toBe(true);
    expect(changesResource("/admin/menubar-items?selected_item_id=1", "/admin/menubar-items?selected_item_id=2")).toBe(true);
    expect(changesResource("/productos-crediticios?producto_id=1&version_id=2&modo=edicion", "/productos-crediticios?producto_id=1&version_id=3&modo=edicion")).toBe(true);
    expect(changesResource("/originacion-politicas/1?politica_id=2", "/originacion-politicas/1?politica_id=3")).toBe(true);
});
it("cerrar un editor cancelado conserva captura y errores hasta aceptar descarte", () => {
    const form = { isDirty: true, name: "Captura", errors: { name: "Revisa el nombre" }, defaults: vi.fn() };
    const confirm = vi.spyOn(window, "confirm").mockReturnValue(false);
    expect(confirmFormDiscard(form)).toBe(false);
    expect(form.name).toBe("Captura");
    expect(form.errors.name).toBe("Revisa el nombre");
    expect(form.defaults).not.toHaveBeenCalled();
    confirm.mockReturnValue(true);
    expect(confirmFormDiscard(form)).toBe(true);
    expect(form.defaults).toHaveBeenCalledOnce();
});
it("cancela visitas de navegación/contexto, permite guardar y protege recarga", () => {
    let before;
    cleanup = installUnsavedChangesGuard({ on: (name, callback) => { before = callback; return vi.fn(); } });
    const confirm = vi.spyOn(window, "confirm").mockReturnValue(false);
    registerUnsavedChanges(() => true);
    const visit = (method, path) => before({ detail: { visit: { method, url: new URL(path, window.location.origin) } } });
    expect(visit("get", "/clientes")).toBe(false);
    expect(visit("put", "/current-team")).toBe(false);
    expect(visit("put", "/current-sucursal")).toBe(false);
    expect(visit("post", "/logout")).toBe(false);
    expect(visit("put", "/clientes/1")).toBeUndefined();
    expect(confirm).toHaveBeenCalledTimes(4);
    const event = new Event("beforeunload", { cancelable: true });
    window.dispatchEvent(event);
    expect(event.defaultPrevented).toBe(true);
    forgetUnsavedChanges();
    expect(visit("get", "/dashboard")).toBeUndefined();
});
it("solo exime filtros que conservan el formulario y protege envíos pendientes", async () => {
    let before;
    cleanup = installUnsavedChangesGuard({ on: (name, callback) => { before = callback; return vi.fn(); } });
    const scope = effectScope();
    const form = scope.run(() => useGuardedForm({ motivo: "" }));
    try {
        form.motivo = "Documento pendiente";
        form.processing = true;
        await nextTick();
        const confirm = vi.spyOn(window, "confirm").mockReturnValue(false);
        const visit = (method, preserveState) => before({ detail: { visit: { method, preserveState, url: new URL("/clientes/1/edit?page=2", window.location.origin) } } });
        expect(visit("get", true)).toBeUndefined();
        expect(visit("get", false)).toBe(false);
        expect(visit("post", false)).toBeUndefined();
        expect(confirm).toHaveBeenCalledOnce();
        const event = new Event("beforeunload", { cancelable: true });
        window.dispatchEvent(event);
        expect(event.defaultPrevented).toBe(true);
    } finally { scope.stop(); }
});
it("cancelar atrás en filtros restaura la entrada sin desmontar la captura", () => {
    cleanup = installUnsavedChangesGuard({ on: () => vi.fn() });
    const previous = history.state;
    history.pushState({ component: "Clientes/Edit" }, "", "/clientes/1/edit?page=2");
    const current = history.state;
    const go = vi.spyOn(history, "go").mockImplementation(() => {});
    const inertia = vi.fn();
    window.addEventListener("popstate", inertia);
    registerUnsavedChanges(() => true);
    vi.spyOn(window, "confirm").mockReturnValue(false);
    // Simulate the browser changing the URL before dispatching popstate.
    const replace = History.prototype.replaceState.bind(history);
    replace(previous, "", "/clientes/1/edit");
    window.dispatchEvent(new PopStateEvent("popstate", { state: previous }));
    expect(go).toHaveBeenCalledWith(1);
    expect(inertia).not.toHaveBeenCalled();
    replace(current, "", "/clientes/1/edit?page=2");
    window.dispatchEvent(new PopStateEvent("popstate", { state: current }));
    expect(inertia).not.toHaveBeenCalled();
    window.removeEventListener("popstate", inertia);
});
