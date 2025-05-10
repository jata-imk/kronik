import { ref, toValue, watchEffect } from "vue";

export function useCodigoPostal(cpInput, options = {}) {
    const {
        shouldFetchSugerencias = () => true, // default: siempre hace fetch
        shouldFetchBusqueda = () => true, // default: siempre hace fetch
        debounceMs = 300, // tiempo de espera antes de hacer fetch
    } = options;

    const sugerenciasData = ref([]);
    const busquedaData = ref(null);
    const loading = ref(false);
    const error = ref(null);

    let debounceTimeout = null; // para guardar el timer

    // Función privada para hacer fetch
    const fetchData = async (endpoint, codigo) => {
        try {
            const response = await fetch(
                `/codigos-postales/${endpoint}?codigo=${codigo}`,
                {
                    method: "GET",
                    credentials: "include",
                    headers: {
                        Accept: "application/json",
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    `Error ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            return data.data;
        } catch (err) {
            console.error(`Error al consultar ${endpoint}:`, err);
            throw err;
        }
    };

    // watchEffect para sugerencias automáticas
    watchEffect((onInvalidate) => {
        const codigo = toValue(cpInput);

        // Limpiamos debounce anterior si el input cambia rápido
        if (debounceTimeout) {
            clearTimeout(debounceTimeout);
        }

        if (!codigo) {
            sugerenciasData.value = [];
            return;
        }

        if (!shouldFetchSugerencias(codigo)) {
            sugerenciasData.value = [];
            return;
        }

        loading.value = true;
        error.value = null;

        debounceTimeout = setTimeout(async () => {
            try {
                const data = await fetchData("sugerencias", codigo);
                sugerenciasData.value = data;
            } catch (err) {
                error.value = err.message || "Error en sugerencias";
            } finally {
                loading.value = false;
            }
        }, debounceMs);

        // Si el efecto se invalida (por ejemplo, el componente se destruye), cancelamos el timeout
        onInvalidate(() => {
            if (debounceTimeout) {
                clearTimeout(debounceTimeout);
            }
        });
    });

    const busqueda = async () => {
        const codigo = toValue(cpInput);

        if (!codigo) {
            // busquedaData.value = null;
            return;
        }

        if (!shouldFetchBusqueda(codigo)) {
            // busquedaData.value = null;
            return;
        }

        loading.value = true;
        error.value = null;
        try {
            const data = await fetchData("buscar", codigo);
            busquedaData.value = data;
        } catch (err) {
            error.value = err.message || "Error desconocido en búsqueda";
        } finally {
            loading.value = false;
        }
    };

    return {
        sugerenciasData,
        busquedaData,
        loading,
        error,
        busqueda,
    };
}
