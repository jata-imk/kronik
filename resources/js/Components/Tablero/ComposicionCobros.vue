<script setup>
import { computed } from "vue";
import { importeMXN } from "@/Utils/formatosFinancieros";
const props = defineProps({ cobros: { type: Object, required: true } });
const nombres = {
    capital: "Capital recuperado",
    ordinario: "Interés ordinario cobrado",
    moratorio: "Interés moratorio cobrado",
    comisiones: "Comisiones cobradas",
    impuestos: "Impuestos cobrados",
};
const filas = computed(() =>
    Object.entries(nombres).map(([clave, etiqueta]) => ({
        clave,
        etiqueta,
        importe: props.cobros[clave],
        proporcion:
            Number(props.cobros.total) > 0
                ? (Number(props.cobros[clave]) / Number(props.cobros.total)) *
                  100
                : 0,
    })),
);
</script>

<template>
    <section
        class="k-surface k-panel space-y-4"
        aria-labelledby="composicion-cobros"
    >
        <div>
            <p class="k-eyebrow">Movimientos del periodo</p>
            <h2 id="composicion-cobros" class="text-lg font-semibold">
                Composición de cobros
            </h2>
        </div>
        <p class="k-financial text-3xl font-semibold">
            {{ importeMXN(cobros.total) }}
        </p>
        <dl class="space-y-4">
            <div v-for="fila in filas" :key="fila.clave">
                <div class="flex flex-wrap justify-between gap-2">
                    <dt>{{ fila.etiqueta }}</dt>
                    <dd class="k-financial font-semibold">
                        {{ importeMXN(fila.importe) }}
                    </dd>
                </div>
                <div class="k-tablero-barra" aria-hidden="true">
                    <span :style="{ width: `${fila.proporcion}%` }" />
                </div>
            </div>
        </dl>
        <p class="text-sm text-muted-color">
            Los cobros incluyen pagos, anticipos y liquidaciones, descontando
            reversos. Los intereses cobrados no representan utilidad neta.
        </p>
    </section>
</template>
