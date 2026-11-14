<script setup>
import Chart from "./GraficaFinanciera.vue";
import { computed } from "vue";
import { usePaletaFinanciera } from "@/Composables/usePaletaFinanciera";
import { importeMXN } from "@/Utils/formatosFinancieros";
import { color } from "chart.js/helpers";
const props = defineProps({
    titulo: String,
    descripcion: String,
    filas: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    moneda: { type: Boolean, default: true },
    unidad: { type: String, default: "" },
    tipo: { type: String, default: "line" },
    eyebrow: String,
    badge: String,
    apilada: Boolean,
    area: Boolean,
});
const paleta = usePaletaFinanciera();
const format = (value) =>
    props.moneda
        ? importeMXN(value)
        : props.unidad
          ? `${new Intl.NumberFormat("es-MX", { maximumFractionDigits: 2 }).format(Number(value))} ${props.unidad}`
          : String(value);
const axisFormat = (value) =>
    new Intl.NumberFormat("es-MX", {
        notation: "compact",
        maximumFractionDigits: 1,
    }).format(Number(value));
const data = computed(() => {
    const colors = [
        paleta.value.principal,
        paleta.value.secundario,
        paleta.value.advertencia,
    ];
    return {
        labels: props.filas.map((f) => f.etiqueta),
        datasets: props.series.map((s, i) => ({
            label: s.nombre,
            data: props.filas.map((f) =>
                f[s.clave] == null ? null : Number(f[s.clave]),
            ),
            borderColor: paleta.value[s.tono] ?? colors[i % colors.length],
            backgroundColor: props.area
                ? color(
                      paleta.value[s.tono] ??
                          colors[i % colors.length] ??
                          "#10b981",
                  )
                      .alpha(0.12)
                      .rgbString()
                : (paleta.value[s.tono] ?? colors[i % colors.length]),
            borderRadius: props.tipo === "bar" ? 5 : 0,
            maxBarThickness: 44,
            borderSkipped: false,
            borderDash: props.tipo === "line" && i % 2 === 1 ? [6, 4] : [],
            pointRadius: 3,
            tension: 0,
            fill: props.area,
        })),
    };
});
const options = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                color: paleta.value.texto,
                usePointStyle: true,
                boxWidth: 8,
                boxHeight: 8,
                padding: 18,
            },
        },
        tooltip: {
            callbacks: {
                label: (ctx) => `${ctx.dataset.label}: ${format(ctx.parsed.y)}`,
            },
        },
    },
    scales: {
        x: {
            stacked: props.apilada,
            ticks: {
                color: paleta.value.tenue,
                maxTicksLimit: 7,
                callback: function (v) {
                    const label = this.getLabelForValue(v);
                    return label.length > 25 ? `${label.slice(0, 22)}…` : label;
                },
            },
            grid: { display: false },
        },
        y: {
            stacked: props.apilada,
            beginAtZero: props.moneda || !!props.unidad || props.tipo === "bar",
            ticks: {
                color: paleta.value.tenue,
                precision: props.moneda || props.unidad ? undefined : 0,
                callback: (value) => axisFormat(value),
            },
            title: {
                display: props.moneda || !!props.unidad,
                text: props.moneda ? "MXN" : props.unidad,
                color: paleta.value.tenue,
            },
            grid: { color: paleta.value.borde },
        },
    },
}));
</script>
<template>
    <section class="k-surface k-panel min-w-0 space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <p v-if="eyebrow" class="k-eyebrow">{{ eyebrow }}</p>
                <h2 class="text-lg font-semibold">{{ titulo }}</h2>
            </div>
            <span v-if="badge" class="k-tablero-badge">{{ badge }}</span>
        </div>
        <p v-if="descripcion" class="text-sm text-muted-color">
            {{ descripcion }}
        </p>
        <div v-if="filas.length" class="relative h-72">
            <Chart
                :type="tipo"
                :data="data"
                :options="options"
                class="h-full"
                :aria-label="titulo"
                role="img"
            />
        </div>
        <p v-else class="text-muted-color">
            No hay datos comparables disponibles.
        </p>
        <details v-if="filas.length">
            <summary class="cursor-pointer py-2 font-semibold">
                Ver datos de la gráfica
            </summary>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="p-2 text-left">Fecha / concepto</th>
                            <th
                                v-for="s in series"
                                :key="s.clave"
                                class="p-2 text-right"
                            >
                                {{ s.nombre }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(f, i) in filas" :key="i">
                            <th class="p-2 text-left font-normal">
                                {{ f.etiqueta }}
                            </th>
                            <td
                                v-for="s in series"
                                :key="s.clave"
                                class="k-financial p-2 text-right"
                            >
                                {{
                                    f[s.clave] == null
                                        ? "Sin dato"
                                        : format(f[s.clave])
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    </section>
</template>
