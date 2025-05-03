import { ref } from "vue";

import { useMap } from "@composables/MapLibre/useMap";
import { useMarker } from "@composables/MapLibre/useMarker";
import { useFitBounds } from "@composables/MapLibre/useFitBounds";
import { useGeoJsonLayers } from "@composables/MapLibre/useGeoJsonLayers";

export function useClientMapSetup() {
    const { map } = useMap();

    const marker = ref(null);

    const {
        addMarker: _addMarker,
        updateMarkerPosition: _updateMarkerPosition,
        removeMarker: _removeMarker,
    } = useMarker();
    const { fitBounds } = useFitBounds();
    const { addGeoJsonSourceWithLayers, updateSourceData, sourceExists } =
        useGeoJsonLayers();

    async function fitMapToZone(bounds) {
        fitBounds(bounds);
    }

    async function createOrUpdateMarker(lngLat) {
        if (!map.value) return;

        if (marker.value) {
            _updateMarkerPosition(lngLat);
            return marker.value;
        }

        marker.value = _addMarker(lngLat, { draggable: true });
        return marker;
    }

    async function createOrUpdateGeoJsonSourceAndLayers(data) {
        if (!map.value) return;

        if (!sourceExists("division_geojson")) {
            addGeoJsonSourceWithLayers({
                id: "division_geojson",
                data,
                layers: {
                    fill: true,
                    line: true,
                },
            });

            return;
        }

        updateSourceData(data);
    }

    async function handleGeocodingResult(data) {
        const itemGeocoding = data[0] || data;

        fitMapToZone([
            [itemGeocoding.boundingbox[2], itemGeocoding.boundingbox[0]],
            [itemGeocoding.boundingbox[3], itemGeocoding.boundingbox[1]],
        ]);

        createOrUpdateMarker([itemGeocoding.lon, itemGeocoding.lat]);

        createOrUpdateGeoJsonSourceAndLayers({
            type: "Feature",
            geometry: itemGeocoding.geojson,
        });
    }

    return {
        fitMapToZone,
        createOrUpdateMarker,
        createOrUpdateGeoJsonSourceAndLayers,
        handleGeocodingResult,
    };
}
