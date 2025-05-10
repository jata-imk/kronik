<script setup>
import { inject, onMounted, onUnmounted } from "vue";
import maplibre from "maplibre-gl";

const props = defineProps({
    lngLat: { type: Array, required: true }, // [lng, lat]
    popupContent: { type: String, default: null },
    draggable: { type: Boolean, default: false },
});

const mapInstance = inject("mapInstance"); // 🔥 Traemos el mapa desde el padre
let marker;

onMounted(() => {
    if (!mapInstance?.value) {
        console.error(
            "No se encontró instancia del mapa. Asegúrate que MapLibreMap esté cargado.",
        );
        return;
    }

    marker = new maplibre.Marker({ draggable: props.draggable })
        .setLngLat(props.lngLat)
        .addTo(mapInstance.value);

    if (props.popupContent) {
        const popup = new maplibre.Popup().setHTML(props.popupContent);
        marker.setPopup(popup);
    }
});

onUnmounted(() => {
    if (marker) {
        marker.remove();
    }
});
</script>

<template>
  <!-- Este componente no renderiza nada visualmente, solo agrega un marker en el mapa -->
</template>
