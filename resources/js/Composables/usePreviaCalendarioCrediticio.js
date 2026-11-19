import axios from "axios";
import { onBeforeUnmount, ref, watch } from "vue";

export function usePreviaCalendarioCrediticio(version, escenario) {
    const resultado = ref(null);
    const error = ref(null);
    const cargando = ref(false);
    let solicitud;

    function limpiar() {
        solicitud?.abort();
        solicitud = undefined;
        resultado.value = null;
        error.value = null;
        cargando.value = false;
    }

    async function calcular() {
        limpiar();
        if (!version.value) return;
        const activa = new AbortController();
        solicitud = activa;
        cargando.value = true;
        try {
            const respuesta = await axios.post(route("productos-crediticios.calendario.previa", version.value.id), escenario, { signal: activa.signal });
            if (solicitud === activa) resultado.value = respuesta.data;
        } catch (e) {
            if (solicitud !== activa) return;
            const primerError = Object.values(e.response?.data?.errors ?? {}).flat()[0];
            error.value = primerError ?? "No se pudo calcular la previa. Intenta de nuevo.";
        } finally {
            if (solicitud === activa) cargando.value = false;
        }
    }

    watch([version, escenario], limpiar, { deep: true });
    onBeforeUnmount(limpiar);
    return { resultado, error, cargando, calcular };
}
