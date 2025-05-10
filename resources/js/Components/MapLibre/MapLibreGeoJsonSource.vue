<script setup>
import { inject, onMounted, onUnmounted, watch, toValue } from "vue";

const props = defineProps({
    id: { type: String, required: true },
    data: { type: [Object, Function], required: true }, // Aceptamos objeto o ref
});

const mapInstance = inject("mapInstance");

onMounted(() => {
    if (!mapInstance?.value) {
        console.error("No se encontró el mapa. ¿MapLibreMap montado?");
        return;
    }

    mapInstance.value.addSource(props.id, {
        type: "geojson",
        data: toValue(props.data),
    });

    // Si data es reactivo, actualiza el source dinámicamente
    watch(
        () => toValue(props.data),
        (newData) => {
            const source = mapInstance.value.getSource(props.id);
            if (source) {
                source.setData(newData);
            }
        },
    );
});

onUnmounted(() => {
    if (mapInstance?.value?.getSource(props.id)) {
        mapInstance.value.removeSource(props.id);
    }
});
</script>

<template>
  <!-- No renderiza nada, sólo conecta el source -->
</template>
