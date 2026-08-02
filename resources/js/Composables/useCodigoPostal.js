import { computed, onScopeDispose, ref, toValue, watchEffect } from "vue";

export function useCodigoPostal(cpInput, options = {}) {
    const {
        shouldFetchSugerencias = () => true,
        shouldFetchBusqueda = () => true,
        debounceMs = 300,
    } = options;

    const sugerenciasData = ref([]);
    const busquedaData = ref(null);
    const cargandoSugerencias = ref(false);
    const cargandoBusqueda = ref(false);
    const errorSugerencias = ref(null);
    const errorBusqueda = ref(null);
    const loading = computed(
        () => cargandoSugerencias.value || cargandoBusqueda.value,
    );
    const error = computed(() => errorBusqueda.value ?? errorSugerencias.value);

    let debounceTimeout = null;
    let sugerenciasController = null;
    let busquedaController = null;
    let sugerenciasRequest = 0;
    let busquedaRequest = 0;

    const fetchData = async (endpoint, codigo, requestState) => {
        requestState.controller?.abort();
        requestState.controller = new AbortController();
        const requestId = ++requestState.request.value;
        requestState.loading.value = true;

        try {
            const response = await fetch(
                `/codigos-postales/${endpoint}?codigo=${encodeURIComponent(codigo)}`,
                {
                    method: "GET",
                    credentials: "include",
                    signal: requestState.controller.signal,
                    headers: { Accept: "application/json" },
                },
            );

            if (!response.ok) {
                if (requestId !== requestState.request.value) {
                    return null;
                }

                throw new Error(
                    `Error ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            return requestId === requestState.request.value ? data.data : null;
        } catch (err) {
            if (
                err.name === "AbortError" ||
                requestId !== requestState.request.value
            ) {
                return null;
            }

            throw err;
        } finally {
            if (requestId === requestState.request.value) {
                requestState.loading.value = false;
            }
        }
    };

    watchEffect((onInvalidate) => {
        const codigo = toValue(cpInput);
        clearTimeout(debounceTimeout);

        if (!codigo) {
            sugerenciasController?.abort();
            sugerenciasController = null;
            sugerenciasRequest += 1;
            sugerenciasData.value = [];
            errorSugerencias.value = null;
            cargandoSugerencias.value = false;
            return;
        }

        if (!shouldFetchSugerencias(codigo)) {
            sugerenciasController?.abort();
            sugerenciasController = null;
            sugerenciasRequest += 1;
            sugerenciasData.value = [];
            errorSugerencias.value = null;
            cargandoSugerencias.value = false;
            return;
        }

        errorSugerencias.value = null;

        debounceTimeout = setTimeout(async () => {
            const state = {
                get controller() {
                    return sugerenciasController;
                },
                set controller(controller) {
                    sugerenciasController = controller;
                },
                request: {
                    get value() {
                        return sugerenciasRequest;
                    },
                    set value(value) {
                        sugerenciasRequest = value;
                    },
                },
                loading: cargandoSugerencias,
            };

            try {
                const data = await fetchData("sugerencias", codigo, state);
                if (data !== null) {
                    sugerenciasData.value = data;
                }
            } catch (err) {
                errorSugerencias.value =
                    err.message || "Error al buscar sugerencias";
            }
        }, debounceMs);

        onInvalidate(() => {
            clearTimeout(debounceTimeout);
            sugerenciasController?.abort();
            sugerenciasController = null;
            sugerenciasRequest += 1;
            cargandoSugerencias.value = false;
        });
    });

    const busqueda = async () => {
        const codigo = toValue(cpInput);

        if (!codigo || !shouldFetchBusqueda(codigo)) {
            busquedaController?.abort();
            busquedaController = null;
            busquedaRequest += 1;
            busquedaData.value = null;
            errorBusqueda.value = null;
            cargandoBusqueda.value = false;
            return;
        }

        errorBusqueda.value = null;
        busquedaData.value = null;

        const state = {
            get controller() {
                return busquedaController;
            },
            set controller(controller) {
                busquedaController = controller;
            },
            request: {
                get value() {
                    return busquedaRequest;
                },
                set value(value) {
                    busquedaRequest = value;
                },
            },
            loading: cargandoBusqueda,
        };

        try {
            const data = await fetchData("buscar", codigo, state);
            if (data !== null) {
                busquedaData.value = data;
            }
        } catch (err) {
            errorBusqueda.value =
                err.message || "Error al buscar el código postal";
        }
    };

    onScopeDispose(() => {
        clearTimeout(debounceTimeout);
        sugerenciasController?.abort();
        busquedaController?.abort();
        sugerenciasRequest += 1;
        busquedaRequest += 1;
    });

    return {
        sugerenciasData,
        busquedaData,
        loading,
        error,
        errorSugerencias,
        errorBusqueda,
        busqueda,
    };
}
