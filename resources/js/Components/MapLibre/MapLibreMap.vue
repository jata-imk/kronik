<script setup>
import { ref, onMounted, onUnmounted, provide } from "vue";

import maplibregl from "maplibre-gl";
import "maplibre-gl/dist/maplibre-gl.css";

import { useMap } from "@/Composables/MapLibre/useMap";
import { useCenterMap } from "@/Composables/MapLibre/useCenterMap";
import { useFitBounds } from "@/Composables/MapLibre/useFitBounds";

const emit = defineEmits(["mapLoaded"]); // Emitimos evento cuando el mapa está listo

const mapContainer = ref(null);
const mapInstance = ref(null);

const { setMap } = useMap();

onMounted(() => {
    mapInstance.value = new maplibregl.Map({
        container: "map",
        style: {
            version: 8,
            sources: {
                osm: {
                    type: "raster",
                    tiles: ["https://a.tile.openstreetmap.org/{z}/{x}/{y}.png"],
                    tileSize: 256,
                },
            },
            layers: [
                {
                    id: "osm-layer",
                    type: "raster",
                    source: "osm",
                },
            ],
        },
    });

    setMap(mapInstance.value);
    provide("mapInstance", mapInstance); // 🔥 Aquí damos el mapa a todos los hijos

    // En la primer carga centrar el mapa en Mexico
    mapInstance.value.on("load", () => {
        window.map = mapInstance.value;
        useCenterMap().centerAt([-102.0077097, 23.6585116], 14);

        useFitBounds().fitBounds([
            [-118.599188, 14.3811832], // [west, south]
            [-86.493266, 32.7187133], // [east, north]
        ]);

        emit("mapLoaded");
    });
});

onUnmounted(() => {
    if (mapInstance.value) {
        mapInstance.value.remove();
    }
});

defineExpose({
    map: mapInstance,
});
</script>
  
<template>
    <div ref="mapContainer" id="map" class="map-container">
        <slot v-if="mapInstance" /> <!-- Solo renderizamos hijos cuando el mapa esté listo -->
    </div>
</template>
  
<style>
  .map-container {
    width: 100%;
    height: 100%;
  }
</style>