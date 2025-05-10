import { useMap } from "@composables/MapLibre/useMap";

export function useGeoJsonLayers() {
    const { map } = useMap();
    const addedLayers = [];
    let sourceId = null;

    function addGeoJsonSourceWithLayers({
        id,
        data,
        layers = {
            fill: true,
            line: true,
            symbol: false,
        },
        textField = "name", // propiedad para la capa symbol
    }) {
        sourceId = id;

        if (!map.value.getSource(id)) {
            map.value.addSource(id, {
                type: "geojson",
                data,
            });
        }

        // Layer: Fill
        if (layers.fill) {
            const layerId = `${id}-fill`;
            if (!map.value.getLayer(layerId)) {
                map.value.addLayer({
                    id: layerId,
                    type: "fill",
                    source: id,
                    paint: {
                        "fill-color": "#088",
                        "fill-opacity": 0.4,
                    },
                });
                addedLayers.push(layerId);
            }
        }

        // Layer: Line
        if (layers.line) {
            const layerId = `${id}-line`;
            if (!map.value.getLayer(layerId)) {
                map.value.addLayer({
                    id: layerId,
                    type: "line",
                    source: id,
                    paint: {
                        "line-color": "#000",
                        "line-width": 2,
                    },
                });
                addedLayers.push(layerId);
            }
        }

        // Layer: Symbol
        if (layers.symbol) {
            const layerId = `${id}-symbol`;
            if (!map.value.getLayer(layerId)) {
                map.value.addLayer({
                    id: layerId,
                    type: "symbol",
                    source: id,
                    layout: {
                        "text-field": ["get", textField],
                        "text-size": 12,
                        "text-anchor": "center",
                    },
                    paint: {
                        "text-color": "#111",
                    },
                });
                addedLayers.push(layerId);
            }
        }
    }

    function updateSourceData(newData) {
        if (sourceId && map.value.getSource(sourceId)) {
            map.value.getSource(sourceId).setData(newData);
        } else {
            console.warn(`Source ${sourceId} no existe para actualizar`);
        }
    }

    function removeGeoJsonLayers() {
        for (const layerId of addedLayers.reverse()) {
            if (map.value.getLayer(layerId)) {
                map.value.removeLayer(layerId);
            }
        }
        addedLayers.length = 0;

        if (sourceId && map.value.getSource(sourceId)) {
            map.value.removeSource(sourceId);
        }
    }

    function sourceExists() {
        return !!map.value.getSource(sourceId);
    }

    return {
        addGeoJsonSourceWithLayers,
        updateSourceData,
        removeGeoJsonLayers,
        sourceExists,
    };
}
