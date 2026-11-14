import { router } from "@inertiajs/vue3";
import { onUnmounted, ref, watch } from "vue";

export function useTableroConsulta(filters, url) {
    const consulta = ref({ ...filters() });
    const cargando = ref(false);
    const errores = ref({});
    let cancelar;
    let revision = 0;
    watch(filters, (valor) => {
        consulta.value = { ...valor };
    });

    function consultar(detalle = null) {
        cancelar?.();
        const actual = ++revision;
        let recibida = false;
        cargando.value = true;
        errores.value = {};
        const { scope, sucursal_id, corte, desde, hasta } = consulta.value;
        router.get(
            url(),
            {
                scope,
                sucursal_id: scope === "all" ? sucursal_id : undefined,
                corte,
                desde,
                hasta,
                detalle,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onCancelToken: (token) => {
                    cancelar = () => token.cancel();
                },
                onError: (valor) => {
                    if (actual === revision) errores.value = valor;
                },
                onSuccess: () => {
                    recibida = true;
                },
                onFinish: () => {
                    if (actual !== revision) return;
                    if (!recibida && !Object.keys(errores.value).length)
                        errores.value = {
                            consulta:
                                "No se pudo completar la consulta. Revisa tu conexión y vuelve a intentarlo.",
                        };
                    cargando.value = false;
                },
            },
        );
    }
    onUnmounted(() => {
        revision++;
        cancelar?.();
    });
    return { consulta, cargando, errores, consultar };
}
