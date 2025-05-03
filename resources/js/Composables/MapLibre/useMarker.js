import { ref } from "vue";

import maplibregl from "maplibre-gl";

import { useMap } from "@composables/MapLibre/useMap";

export function useMarker() {
    const { map } = useMap(); // Necesita estar dentro de <MapLibreMap>
    const marker = ref(null);

    function addMarker(
        lngLat,
        { draggable = false, popupContent = null } = {},
    ) {
        if (!map?.value) {
            console.warn("[useMarker] No se encontró instancia del mapa.");
            return;
        }

        const m = new maplibregl.Marker({ draggable })
            .setLngLat(lngLat)
            .addTo(map.value);

        if (popupContent) {
            const popup = new maplibregl.Popup().setHTML(popupContent);
            m.setPopup(popup);
        }

        marker.value = m;
        return marker.value;
    }

    const updateMarkerPosition = (lngLat) => {
        if (marker.value) {
            marker.value.setLngLat(lngLat);
        }
    };

    function removeMarker() {
        if (marker.value) {
            marker.value.remove();
            marker.value = null;
        }
    }

    return {
        marker,
        addMarker,
        updateMarkerPosition,
        removeMarker,
    };
}
