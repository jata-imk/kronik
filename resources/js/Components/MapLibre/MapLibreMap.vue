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
const tileError = ref(false);

const { setMap } = useMap();
provide("mapInstance", mapInstance);

onMounted(() => {
    mapInstance.value = new maplibregl.Map({
        container: mapContainer.value,
        style: {
            version: 8,
            sources: {
                osm: {
                    type: "raster",
                    tiles: ["https://tile.openstreetmap.org/{z}/{x}/{y}.png"],
                    tileSize: 256,
                    attribution: '© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>',
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
    mapInstance.value.on("error", (event) => {
        if (event.sourceId === "osm") tileError.value = true;
    });

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
    <div ref="mapContainer" class="map-container">
        <slot v-if="mapInstance" /> <!-- Solo renderizamos hijos cuando el mapa esté listo -->
        <p v-if="tileError" class="map-tile-error" role="status">El mapa base no está disponible en este momento. Revisa la conexión o intenta más tarde.</p>
    </div>
</template>
  
<style>
  .map-container {
    position: relative;
    width: 100%;
    height: 100%;
  }
  .map-tile-error {
    position: absolute;
    z-index: 2;
    left: 1rem;
    right: 1rem;
    bottom: 2.5rem;
    padding: .75rem 1rem;
    border-radius: .75rem;
    background: rgba(255, 255, 255, .94);
    color: #18334a;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .14);
  }
</style>
