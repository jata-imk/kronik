import { onScopeDispose, ref, toValue, watchEffect } from "vue";

export function useCodigoPostal(cpInput, options = {}) {
    const {
        shouldFetchSugerencias = () => true,
        shouldFetchBusqueda = () => true,
        debounceMs = 300,
    } = options;

    const sugerenciasData = ref([]);
    const busquedaData = ref(null);
    const loading = ref(false);
    const error = ref(null);

    let debounceTimeout = null;
    let activeController = null;
    let activeRequest = 0;

    const fetchData = async (endpoint, codigo) => {
        activeController?.abort();
        activeController = new AbortController();
        const requestId = ++activeRequest;
        loading.value = true;

        try {
            const response = await fetch(
                `/codigos-postales/${endpoint}?codigo=${encodeURIComponent(codigo)}`,
                {
                    method: "GET",
                    credentials: "include",
                    signal: activeController.signal,
                    headers: { Accept: "application/json" },
                },
            );

            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            return requestId === activeRequest ? data.data : null;
        } catch (err) {
            if (err.name === "AbortError") {
                return null;
            }

            throw err;
        } finally {
            if (requestId === activeRequest) {
                loading.value = false;
            }
        }
    };

    watchEffect((onInvalidate) => {
        const codigo = toValue(cpInput);
        clearTimeout(debounceTimeout);

        if (!codigo) {
            activeController?.abort();
            sugerenciasData.value = [];
            loading.value = false;
            return;
        }

        if (!shouldFetchSugerencias(codigo)) {
            activeController?.abort();
            sugerenciasData.value = [];
            loading.value = false;
            return;
        }

        error.value = null;

        debounceTimeout = setTimeout(async () => {
            try {
                const data = await fetchData("sugerencias", codigo);
                if (data !== null) {
                    sugerenciasData.value = data;
                }
            } catch (err) {
                error.value = err.message || "Error al buscar sugerencias";
            }
        }, debounceMs);

        onInvalidate(() => clearTimeout(debounceTimeout));
    });

    const busqueda = async () => {
        const codigo = toValue(cpInput);

        if (!codigo || !shouldFetchBusqueda(codigo)) {
            return;
        }

        error.value = null;
        busquedaData.value = null;

        try {
            const data = await fetchData("buscar", codigo);
            if (data !== null) {
                busquedaData.value = data;
            }
        } catch (err) {
            error.value = err.message || "Error al buscar el código postal";
        }
    };

    onScopeDispose(() => {
        clearTimeout(debounceTimeout);
        activeController?.abort();
    });

    return {
        sugerenciasData,
        busquedaData,
        loading,
        error,
        busqueda,
    };
}
