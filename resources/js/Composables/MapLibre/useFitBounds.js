import { getBoundsFromPoints } from "@/Utils/Maplibre/Helpers";
import { useMap } from "./useMap";

export function useFitBounds() {
    const { map } = useMap();

    /**
     * Ajusta el mapa para mostrar todos los puntos dentro de un bounding box.
     * @param {Array} bounds - Array de dos coordenadas [[lngMin, latMin], [lngMax, latMax]]
     * @param {Object} options - Opcional. padding, duration, etc.
     */
    function fitBounds(bounds, options = { padding: 40, duration: 1000 }) {
        if (!map.value) {
            console.warn("[useFitBounds] Mapa aún no está disponible.");
            return;
        }

        map.value.fitBounds(bounds, options);
    }

    /**
     * Ajusta el mapa para mostrar todos los puntos en una lista de puntos.
     * @param {Array} points - Array de puntos [[lng, lat]]
     */
    function fitToPoints(points) {
        const bounds = getBoundsFromPoints(points);

        useFitBounds().fitBounds(bounds);
    }

    return { fitBounds, fitToPoints };
}
