import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { onMounted, onUnmounted, watch } from "vue";

export function operationalDestination(href, teamChanged = false) {
    if (teamChanged) return "/dashboard";
    const url = new URL(href, window.location.origin);
    const path = url.pathname;
    if (path.startsWith("/clientes/") && path.includes("historial-crediticio") || path.startsWith("/consultas-sic")) return "/clientes/historial-crediticio";
    for (const root of ["solicitudes", "creditos", "clientes"]) {
        if (path === `/${root}` || path.startsWith(`/${root}/`)) {
            return root === "clientes" && path === "/clientes/expedientes" ? path : `/${root}`;
        }
    }
    if (["/dashboard", "/mi-trabajo", "/evaluacion-solicitudes", "/cumplimiento"].includes(path)) {
        for (const field of ["scope", "sucursal_id", "page"]) url.searchParams.delete(field);
    }
    return url.pathname + url.search;
}

export function useOperationalContext() {
    const page = usePage();
    const key = () => JSON.stringify(page.props.contextoOperativo ?? {});
    let channel;
    let controller;
    let leaving = false;
    const leave = (teamChanged) => {
        if (leaving) return;
        leaving = true;
        router.cancel();
        window.location.replace(operationalDestination(window.location.href, teamChanged));
    };
    const refresh = async () => {
        if (controller || leaving || document.visibilityState === "hidden") return;
        controller = new AbortController();
        try {
            // Independent of the open page's permissions: a team switch may revoke them.
            const { data } = await axios.get("/contexto-operativo", { signal: controller.signal });
            const previous = page.props.contextoOperativo ?? {};
            if (data.team_id !== previous.team_id || data.sucursal_id !== previous.sucursal_id) {
                leave(data.team_id !== previous.team_id);
            }
        } catch (exception) {
            if ([401, 419].includes(exception.response?.status)) window.location.replace("/login");
            else if (exception.response?.status === 403) window.location.replace("/dashboard");
        } finally {
            controller = null;
        }
    };
    const stop = watch(key, (value, previous) => {
        if (value !== previous) {
            channel?.postMessage("context-changed");
            leave(JSON.parse(value).team_id !== JSON.parse(previous).team_id);
        }
    });
    onMounted(() => {
        if (typeof BroadcastChannel !== "undefined") {
            channel = new BroadcastChannel("kronik-operational-context");
            channel.onmessage = refresh;
            // A redirect may replace the old layout before its watcher broadcasts.
            channel.postMessage("context-changed");
        }
        for (const event of ["focus", "pageshow", "popstate"]) window.addEventListener(event, refresh);
        document.addEventListener("visibilitychange", refresh);
        refresh();
    });
    onUnmounted(() => {
        stop();
        controller?.abort();
        channel?.close();
        for (const event of ["focus", "pageshow", "popstate"]) window.removeEventListener(event, refresh);
        document.removeEventListener("visibilitychange", refresh);
    });
}
