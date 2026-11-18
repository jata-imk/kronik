const forms = new Set();
export const discardMessage = "Hay cambios sin guardar. ¿Quieres descartarlos y salir de esta pantalla?";
export function registerUnsavedChanges(isDirty) {
    forms.add(isDirty);
    return () => forms.delete(isDirty);
}
export const hasUnsavedChanges = () => [...forms].some(isDirty => isDirty());
export const forgetUnsavedChanges = () => forms.clear();
export const confirmDiscardChanges = () => !hasUnsavedChanges() || window.confirm(discardMessage);

export function changesResource(from, to) {
    const previous = new URL(from, window.location.origin);
    const next = new URL(to, window.location.origin);
    if (previous.origin !== next.origin || previous.pathname !== next.pathname) return true;
    const fields = new Set([...previous.searchParams.keys(), ...next.searchParams.keys()]);
    return [...fields].filter(key => key.startsWith("selected_") || ["modo", "cliente_id", "credito_id", "pago_id", "team_id"].includes(key))
        .some(key => previous.searchParams.get(key) !== next.searchParams.get(key));
}

// Install before Inertia's popstate listener. Preserve its history payload.
export function installUnsavedChangesGuard(router) {
    const marker = "__kronikPosition";
    const epoch = window.history.state?.[marker]?.epoch ?? crypto.randomUUID();
    let position = window.history.state?.[marker]?.index ?? 0;
    let restoring = false;
    let currentHref = window.location.href;
    const push = window.history.pushState.bind(window.history);
    const replace = window.history.replaceState.bind(window.history);
    window.history.pushState = (state, title, url) => { push({ ...state, [marker]: { epoch, index: ++position } }, title, url); currentHref = window.location.href; };
    window.history.replaceState = (state, title, url) => { replace({ ...state, [marker]: { epoch, index: position } }, title, url); currentHref = window.location.href; };
    window.history.replaceState(window.history.state, "", window.location.href);
    const onPopstate = event => {
        if (restoring) { restoring = false; event.stopImmediatePropagation(); return; }
        const destination = event.state?.[marker];
        if (destination?.epoch === epoch && destination.index !== position) {
            if (changesResource(currentHref, window.location.href) && !confirmDiscardChanges()) {
                event.stopImmediatePropagation();
                restoring = true;
                window.history.go(position - destination.index);
                return;
            }
            position = destination.index;
            currentHref = window.location.href;
        }
    };
    window.addEventListener("popstate", onPopstate, true);
    const onBeforeUnload = event => {
        if (hasUnsavedChanges()) { event.preventDefault(); event.returnValue = ""; }
    };
    window.addEventListener("beforeunload", onBeforeUnload);
    const stop = router.on("before", event => {
        const visit = event.detail.visit;
        const changingContext = /\/(current-team|current-sucursal|logout)$/.test(visit.url.pathname);
        if ((visit.method === "get" && changesResource(window.location.href, visit.url.href) || changingContext) && !confirmDiscardChanges()) return false;
    });
    return () => {
        stop();
        window.removeEventListener("beforeunload", onBeforeUnload);
        window.removeEventListener("popstate", onPopstate, true);
        window.history.pushState = push;
        window.history.replaceState = replace;
    };
}
