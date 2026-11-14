<script setup>
import { importeMXN } from "@/Utils/formatosFinancieros";
defineProps({
    titulo: String,
    valor: [String, Number],
    ayuda: String,
    icono: String,
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
        <span class="flex items-center justify-between gap-3"
            ><span class="font-semibold text-muted-color">{{ titulo }}</span
            ><i :class="icono" aria-hidden="true"
        /></span>
        <strong class="k-financial k-tablero-cifra">{{
            valor == null
                ? "Sin dato"
                : monetario
                  ? importeMXN(valor)
                  : valor.toLocaleString("es-MX")
        }}</strong>
        <span class="text-sm text-muted-color">{{ ayuda }}</span>
        <span v-if="valor != null" class="text-sm font-semibold text-primary"
            >Ver detalle <i class="pi pi-arrow-right" aria-hidden="true"
        /></span>
    </button>
</template>
