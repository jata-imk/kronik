import { ref } from "vue";

export function useGeocoding() {
    const loading = ref(false);
    const error = ref(null);
    const result = ref(null);

    const search = async (query) => {
        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            const response = await fetch(
                `/geocoding/search?query=${encodeURIComponent(query)}`,
            );

            if (!response.ok) {
                throw new Error("No se encontraron resultados");
            }

            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            result.value = data;
        } catch (err) {
            error.value = err.message || "Error al buscar ubicación";
        } finally {
            loading.value = false;
        }
    };

    return {
        loading,
        error,
        result,
        search,
    };
}
