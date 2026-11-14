<script setup>
import { computed } from "vue";
import { importeMXN } from "@/Utils/formatosFinancieros";
const props = defineProps({ filas: Array, total: String, corte: String });
defineEmits(["detalle"]);
const maximo = computed(() =>
    Math.max(0, ...(props.filas ?? []).map((fila) => Number(fila.importe))),
);
const tonos = ["info", "principal", "secundario", "advertencia", "peligro"];
</script>
<template>
    <section
        class="k-surface k-panel space-y-4"
        aria-labelledby="antiguedad-title"
    >
        <div class="k-tablero-panel-head">
            <div>
                <p class="k-eyebrow">Saldos al corte</p>
                <h2 id="antiguedad-title">Antigüedad de atrasos</h2>
            </div>
            <strong class="k-financial">{{ importeMXN(total) }}</strong>
        </div>
        <p class="text-sm text-muted-color">
            {{ corte }} · obligación pendiente más antigua de cada crédito.
        </p>
        <dl class="k-tablero-antiguedad">
            <div v-for="(fila, i) in filas" :key="fila.etiqueta">
                <dt>{{ fila.etiqueta }}</dt>
                <dd>
                    <div class="k-tablero-barra" aria-hidden="true">
                        <span
                            :class="`k-tono-${tonos[i]}`"
                            :style="{
                                width: `${maximo > 0 ? (Number(fila.importe) / maximo) * 100 : 0}%`,
                            }"
                        />
                    </div>
                    <strong class="k-financial">{{
                        importeMXN(fila.importe)
                    }}</strong>
                </dd>
            </div>
        </dl>
        <button
            type="button"
            class="k-tablero-text-link"
            @click="$emit('detalle', 'vencido')"
        >
            Ver obligaciones vencidas
            <i class="pi pi-arrow-right" aria-hidden="true" />
        </button>
        <p class="k-tablero-ayuda">
            Los rangos suman el vencido total. La gracia regula mora; no
            desplaza el vencimiento. Las barras comparten la escala del rango
            mayor.
        </p>
    </section>
</template>
