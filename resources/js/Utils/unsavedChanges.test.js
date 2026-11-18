import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { changesResource, confirmDiscardChanges, forgetUnsavedChanges, hasUnsavedChanges, installUnsavedChangesGuard, registerUnsavedChanges } from "./unsavedChanges";

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
it("cancelar atrás restaura la entrada sin entregar el evento a Inertia", () => {
    cleanup = installUnsavedChangesGuard({ on: () => vi.fn() });
    const previous = history.state;
    history.pushState({ component: "Clientes/Edit" }, "", "/clientes/2/edit");
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
    replace(current, "", "/clientes/2/edit");
    window.dispatchEvent(new PopStateEvent("popstate", { state: current }));
    expect(inertia).not.toHaveBeenCalled();
    window.removeEventListener("popstate", inertia);
});
