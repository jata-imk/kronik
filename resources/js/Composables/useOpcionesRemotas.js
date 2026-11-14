import axios from "axios";
import { onUnmounted, ref, watch } from "vue";

export function useOpcionesRemotas(initial, url, selected = () => null) {
    const options = ref(initial() ?? []);
    const error = ref("");
    const loading = ref(false);
    let controller;
    let revision = 0;
    watch(initial, (value) => { revision++; controller?.abort(); options.value = value ?? []; error.value = ""; loading.value = false; });
    async function search(event) {
        controller?.abort();
        controller = new AbortController();
        const current = ++revision;
        error.value = "";
        loading.value = true;
        try {
            const response = await axios.get(url(), { params: { buscar: event.value ?? event.query ?? "" }, signal: controller.signal });
            if (current === revision) {
                const retained = options.value.filter(item => item.id === selected());
                options.value = [...new Map([...retained, ...response.data].map(item => [item.id, item])).values()];
            }
        } catch (exception) {
            if (current === revision && !axios.isCancel(exception)) error.value = "No fue posible buscar las opciones. Intenta de nuevo.";
        } finally {
            if (current === revision) loading.value = false;
        }
    }
    onUnmounted(() => { revision++; controller?.abort(); });
    return { options, error, loading, search };
}
