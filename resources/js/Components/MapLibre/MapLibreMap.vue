<script setup>
import { onMounted } from "vue";
import maplibregl from "maplibre-gl";
import "maplibre-gl/dist/maplibre-gl.css";

onMounted(() => {
    const map = new maplibregl.Map({
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

    // Geolocalización
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((pos) => {
            const coords = [pos.coords.longitude, pos.coords.latitude];

            new maplibregl.Marker({ draggable: true })
                .setLngLat(coords)
                .addTo(map);
        });
    }

    // Bound con GeoJSON de ejemplo
    map.on("load", () => {
        // Fit bounds on mexico country
        map.fitBounds([
            [-118.599188, 14.3811832], // [west, south]
            [-86.493266, 32.7187133], // [east, north]
        ]);
    });
});
</script>
  
<template>
    <div id="map" class="map-container"></div>
</template>
  
<style>
  .map-container {
    width: 100%;
    height: 100%;
  }
</style>