<script setup>
import { importeMXN } from "@/Utils/formatosFinancieros";
defineProps({
    titulo: String,
    valor: [String, Number],
    ayuda: String,
    icono: String,
    badge: String,
    tono: { type: String, default: "principal" },
    monetario: { type: Boolean, default: true },
});
defineEmits(["abrir"]);
</script>

<template>
    <button
        type="button"
        class="k-surface k-tablero-indicador"
        :disabled="valor == null"
        @click="$emit('abrir')"
    >
        <span class="flex flex-wrap items-center justify-between gap-2"
            ><span class="k-tablero-lista-icono" :class="`k-tono-${tono}`"
                ><i :class="icono" aria-hidden="true" /></span
            ><span
                v-if="badge"
                class="k-tablero-badge"
                :class="`k-tono-${tono}`"
                >{{ badge }}</span
            ></span
        >
        <strong class="k-financial k-tablero-cifra">{{
            valor == null
                ? "Sin dato"
                : monetario
                  ? importeMXN(valor)
                  : valor.toLocaleString("es-MX")
        }}</strong>
        <span class="font-semibold">{{ titulo }}</span>
        <span class="text-sm text-muted-color">{{ ayuda }}</span>
        <span v-if="valor != null" class="text-sm font-semibold text-primary"
            >Ver detalle <i class="pi pi-arrow-right" aria-hidden="true"
        /></span>
    </button>
</template>
