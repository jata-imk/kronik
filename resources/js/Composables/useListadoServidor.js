import { router, usePage } from "@inertiajs/vue3";
import { onUnmounted, watch } from "vue";

export function useListadoServidor(filters, metadata = () => usePage().props.listado) {
    const initial = metadata()?.filters?.columnas ?? {};
    for (const [key, value] of Object.entries(initial)) {
        if (filters.value[key]) filters.value[key].value = value;
    }
    let timer;
    let visiting = false;
    let synchronizing = false;
    function visit(extra = {}) {
        clearTimeout(timer);
        if (!metadata()) return;
        const url = new URL(window.location.href);
        const query = Object.fromEntries(url.searchParams.entries());
        delete query.buscar;
        router.get(url.pathname, { ...query, columnas: JSON.stringify(Object.fromEntries(
            Object.entries(filters.value).map(([key, filter]) => [key, filter.value]),
        )), page: 1, ...extra }, {
            preserveState: true, preserveScroll: true, replace: true,
            onStart: () => { visiting = true; }, onFinish: () => { visiting = false; },
        });
    }
    watch(filters, () => { if (synchronizing) return; clearTimeout(timer); timer = setTimeout(() => visit(), 300); }, { deep: true, flush: "sync" });
    // Browser history restores the query and the visible filter controls together.
    watch(() => metadata()?.filters?.columnas, (values) => {
        if (visiting || !values) return;
        clearTimeout(timer);
        synchronizing = true;
        try {
            for (const key of Object.keys(filters.value)) filters.value[key].value = values[key] ?? null;
        } finally { synchronizing = false; }
    }, { flush: "sync" });
    onUnmounted(() => clearTimeout(timer));
    return { visit, sort: (event) => visit({ orden: event.sortField, direccion: event.sortOrder === -1 ? "desc" : "asc" }) };
}
