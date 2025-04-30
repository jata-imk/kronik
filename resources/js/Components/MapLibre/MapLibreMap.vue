<script setup>
import { ref, onMounted, onUnmounted, provide } from "vue";
import maplibregl from "maplibre-gl";
import "maplibre-gl/dist/maplibre-gl.css";

const emit = defineEmits(["mapLoaded"]); // Emitimos evento cuando el mapa está listo

const mapContainer = ref(null);
const mapInstance = ref(null);

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
        // Center on Mexico
        center: [-102.0077097, 23.6585116],
        zoom: 14,
    });

    // Bound con GeoJSON de ejemplo
    mapInstance.value.on("load", () => {
        // Fit bounds on mexico country
        mapInstance.value.fitBounds([
            [-118.599188, 14.3811832], // [west, south]
            [-86.493266, 32.7187133], // [east, north]
        ]);
    });

    provide("mapInstance", mapInstance); // 🔥 Aquí damos el mapa a todos los hijos

    // Geolocalización
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((pos) => {
            const coords = [pos.coords.longitude, pos.coords.latitude];

            new maplibregl.Marker({ draggable: true })
                .setLngLat(coords)
                .addTo(mapInstance.value);
        });
    }
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