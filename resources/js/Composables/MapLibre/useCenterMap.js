import { useMap } from "./useMap";

export function useCenterMap() {
    const { map } = useMap();

    function centerAt([lng, lat], zoom = 12) {
        if (map.value) {
            map.value.flyTo({ center: [lng, lat], zoom });
        }
    }

    return { centerAt };
}
