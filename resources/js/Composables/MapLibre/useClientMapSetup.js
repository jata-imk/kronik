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

    async function createOrUpdateMarker(lngLat, draggable = true) {
        if (!map.value) return;

        if (marker.value) {
            _updateMarkerPosition(lngLat);
            return marker.value;
        }

        marker.value = _addMarker(lngLat, { draggable });
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

    async function handleGeocodingResult(
        data,
        markerCoordinates = null,
        markerDraggable = true,
    ) {
        if (!map.value || !data) return;

        const itemGeocoding = data[0] || data;

        if (
            !itemGeocoding?.boundingbox ||
            !itemGeocoding?.lat ||
            !itemGeocoding?.lon
        ) {
            return;
        }

        fitMapToZone([
            [itemGeocoding.boundingbox[2], itemGeocoding.boundingbox[0]],
            [itemGeocoding.boundingbox[3], itemGeocoding.boundingbox[1]],
        ]);

        createOrUpdateMarker(
            [
                markerCoordinates?.lon ?? itemGeocoding.lon,
                markerCoordinates?.lat ?? itemGeocoding.lat,
            ],
            markerDraggable,
        );

        if (itemGeocoding.geojson) {
            createOrUpdateGeoJsonSourceAndLayers({
                type: "Feature",
                geometry: itemGeocoding.geojson,
            });
        }
    }

    return {
        marker,
        fitMapToZone,
        createOrUpdateMarker,
        createOrUpdateGeoJsonSourceAndLayers,
        handleGeocodingResult,
    };
}
