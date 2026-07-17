import { useClientMapSetup } from "@/Composables/MapLibre/useClientMapSetup";

import { reactive, watch } from "vue";

export function useDireccionMapConnector() {
    const { handleGeocodingResult, marker } = useClientMapSetup();

    const readOnly = reactive({
        value: null,
    });

    const existentRecord = reactive({
        value: null,
    });

    const initialLoad = reactive({
        value: null,
    });

    const isInitialLoadProccessActive = reactive({
        value: null,
    });

    const form = reactive({
        value: null,
    });

    const queryGeocoding = reactive({
        value: null,
    });

    const geocodingResult = reactive({
        value: null,
    });

    const geocodingError = reactive({
        value: null,
    });

    const map = reactive({
        value: null,
    });

    const setConfig = (config) => {
        readOnly.value = config.readOnly;
        existentRecord.value = config.existentRecord;
        isInitialLoadProccessActive.value = config.isInitialLoadProccessActive;
    };
    const setFormConfig = (config) => {
        form.value = config.form;
        initialLoad.value = config.initialLoad;
        queryGeocoding.value = config.queryGeocoding;
        geocodingResult.value = config.geocodingResult;
        geocodingError.value = config.geocodingError;
    };

    const setMapConfig = ({ map }) => {
        // maplibregl.value = map;
    };

    watch(
        () => marker.value,
        () => {
            if (!marker.value || !form.value) return;

            marker.value.on("dragend", () => {
                const coordenadas = marker.value.getLngLat();
                form.value.coordenadas = {
                    lat: coordenadas.lat,
                    lng: coordenadas.lng,
                };
            });
        },
    );

    watch(
        () => [geocodingResult.value, geocodingError.value],
        () => {
            if (!queryGeocoding.value) {
                return;
            }

            if (!geocodingResult.value && !geocodingError.value) {
                return;
            }

            if (geocodingError.value) {
                return;
            }

            handleGeocodingResult(
                geocodingResult.value,
                existentRecord.value &&
                    isInitialLoadProccessActive.value &&
                    form.value?.coordenadas
                    ? {
                          lat: form.value?.coordenadas.lat,
                          lon: form.value?.coordenadas.lng,
                      }
                    : null,
                !readOnly.value,
            );

            isInitialLoadProccessActive.value = false;
        },
    );

    return {
        setConfig,
        setFormConfig,
        setMapConfig,
    };
}
