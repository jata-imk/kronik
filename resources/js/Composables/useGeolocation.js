import { ref } from "vue";

export function useGeolocation() {
    const coords = ref(null);
    const error = ref(null);
    const loading = ref(false);

    function getCurrentPosition(options = {}) {
        loading.value = true;
        error.value = null;

        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                error.value = "Geolocalización no soportada por el navegador.";
                loading.value = false;
                reject(error.value);
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const { latitude, longitude } = pos.coords;
                    coords.value = [longitude, latitude];
                    loading.value = false;
                    resolve(coords.value);
                },
                (err) => {
                    error.value = err.message;
                    loading.value = false;
                    reject(err.message);
                },
                options,
            );
        });
    }

    return {
        coords,
        error,
        loading,
        getCurrentPosition,
    };
}
