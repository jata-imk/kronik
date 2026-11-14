<script setup>
import { computed } from "vue";
import GraficaFinanciera from "@/Components/Creditos/GraficaFinanciera.vue";
import { usePaletaFinanciera } from "@/Composables/usePaletaFinanciera";
import { importeMXN } from "@/Utils/formatosFinancieros";
const props = defineProps({
    cobros: { type: Object, required: true },
    periodo: String,
});
defineEmits(["detalle"]);
const paleta = usePaletaFinanciera();
const conceptos = [
    ["capital", "Capital recuperado", "principal"],
    ["ordinario", "Interés ordinario", "secundario"],
    ["moratorio", "Interés moratorio", "advertencia"],
    ["comisiones", "Comisiones", "info"],
    ["impuestos", "Impuestos", "peligro"],
];
const data = computed(() => ({
    labels: conceptos.map(([, nombre]) => nombre),
    datasets: [
        {
            data: conceptos.map(([clave]) => Number(props.cobros[clave])),
            backgroundColor: conceptos.map(([, , tono]) => paleta.value[tono]),
            borderWidth: 0,
            hoverOffset: 5,
        },
    ],
}));
const options = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    cutout: "78%",
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) =>
                    `${ctx.label}: ${importeMXN(props.cobros[conceptos[ctx.dataIndex][0]])}`,
            },
        },
    },
}));
</script>

<template>
    <section
        class="k-surface k-panel space-y-4"
        aria-labelledby="composicion-cobros"
    >
        <div class="k-tablero-panel-head">
            <div>
                <p class="k-eyebrow">Composición del periodo</p>
                <h2 id="composicion-cobros">¿De qué se componen los cobros?</h2>
            </div>
            <span class="k-tablero-badge">MXN</span>
        </div>
        <p class="text-sm text-muted-color">{{ periodo }}</p>
        <div class="k-tablero-composicion">
            <div class="k-tablero-donut">
                <GraficaFinanciera
                    v-if="Number(cobros.total) > 0"
                    type="doughnut"
                    :data="data"
                    :options="options"
                    class="h-full"
                    role="img"
                    aria-label="Composición de cobros por concepto"
                />
                <div v-else class="k-tablero-donut-vacio" aria-hidden="true" />
                <div class="k-tablero-donut-centro">
                    <span>Total cobrado</span
                    ><strong class="k-financial">{{
                        importeMXN(cobros.total)
                    }}</strong>
                </div>
            </div>
            <dl class="k-tablero-leyenda">
                <div v-for="[clave, nombre, tono] in conceptos" :key="clave">
                    <dt>
                        <i
                            class="k-tablero-punto"
                            :class="`k-tono-${tono}`"
                            aria-hidden="true"
                        />{{ nombre }}
                    </dt>
                    <dd class="k-financial">{{ importeMXN(cobros[clave]) }}</dd>
                </div>
            </dl>
        </div>
        <p v-if="Number(cobros.total) === 0" class="text-sm text-muted-color">
            No hay cobros registrados en este periodo.
        </p>
        <button
            type="button"
            class="k-tablero-text-link"
            @click="$emit('detalle', 'cobros')"
        >
            Ver composición y pagos
            <i class="pi pi-arrow-right" aria-hidden="true" />
        </button>
        <p class="k-tablero-ayuda">
            Incluye pagos, anticipos y liquidaciones netos de reversos.
            Recuperar capital es recuperar lo prestado; los intereses cobrados
            no representan utilidad neta.
        </p>
    </section>
</template>
