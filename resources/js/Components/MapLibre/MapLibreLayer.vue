<script setup>
import { inject, onMounted, onUnmounted, watch } from "vue";

const props = defineProps({
    id: { type: String, required: true },
    type: { type: String, required: true }, // 'line', 'fill', etc.
    source: { type: String, required: true }, // id del source
    paint: { type: Object, default: () => ({}) },
    layout: { type: Object, default: () => ({}) },
});

const mapInstance = inject("mapInstance");

onMounted(() => {
    if (!mapInstance?.value) {
        console.error("No se encontró el mapa.");
        return;
    }

    mapInstance.value.addLayer({
        id: props.id,
        type: props.type,
        source: props.source,
        paint: props.paint,
        layout: props.layout,
    });
});

onUnmounted(() => {
    if (mapInstance?.value?.getLayer(props.id)) {
        mapInstance.value.removeLayer(props.id);
    }
});
</script>

<template>
  <!-- Igual, no renderiza nada -->
</template>
