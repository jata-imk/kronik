<script setup>
import Chart from "primevue/chart";
import { ref, watch, nextTick, onUnmounted } from "vue";
import { useLayout } from "@sakai-vue/layout/composables/layout";
const props = defineProps({
    titulo: String,
    descripcion: String,
    filas: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    moneda: { type: Boolean, default: true },
    unidad: { type: String, default: "" },
    tipo: { type: String, default: "line" },
});
const { layoutConfig } = useLayout();
const data = ref({ labels: [], datasets: [] });
const options = ref({});
let activo = true;
onUnmounted(() => {
    activo = false;
});
const format = (value) =>
    props.moneda
        ? new Intl.NumberFormat("es-MX", { style: "currency", currency: "MXN" }).format(
              Number(value),
          )
        : props.unidad
          ? `${new Intl.NumberFormat("es-MX", { maximumFractionDigits: 2 }).format(Number(value))} ${props.unidad}`
          : String(value);
const axisFormat = (value) =>
    new Intl.NumberFormat("es-MX", { notation: "compact", maximumFractionDigits: 1 }).format(
        Number(value),
    );
async function actualizar() {
    await nextTick();
    await new Promise((resolve) => requestAnimationFrame(resolve));
    if (!activo) return;
    const css = getComputedStyle(document.documentElement);
    const token = (name) => css.getPropertyValue(name).trim();
    const colors = [
        token("--p-primary-color"),
        token("--p-text-muted-color"),
        token("--p-text-color"),
    ];
    data.value = {
        labels: props.filas.map((f) => f.etiqueta),
        datasets: props.series.map((s, i) => ({
            label: s.nombre,
            data: props.filas.map((f) => (f[s.clave] == null ? null : Number(f[s.clave]))),
            borderColor: colors[i % colors.length],
            backgroundColor: colors[i % colors.length],
            borderDash: i === 1 ? [6, 4] : [],
            pointRadius: 3,
            tension: 0,
            fill: false,
        })),
    };
    options.value = {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
            legend: {
                position: "bottom",
                labels: { color: token("--p-text-color"), usePointStyle: true },
            },
            tooltip: {
                callbacks: { label: (ctx) => `${ctx.dataset.label}: ${format(ctx.parsed.y)}` },
            },
        },
        scales: {
            x: {
                ticks: {
                    color: token("--p-text-muted-color"),
                    maxTicksLimit: 7,
                    callback: function (v) {
                        const label = this.getLabelForValue(v);
                        return label.length > 25 ? `${label.slice(0, 22)}…` : label;
                    },
                },
                grid: { display: false },
            },
            y: {
                beginAtZero: props.moneda || !!props.unidad || props.tipo === "bar",
                ticks: {
                    color: token("--p-text-muted-color"),
                    precision: props.moneda || props.unidad ? undefined : 0,
                    callback: (value) => axisFormat(value),
                },
                title: {
                    display: props.moneda || !!props.unidad,
                    text: props.moneda ? "MXN" : props.unidad,
                    color: token("--p-text-muted-color"),
                },
                grid: { color: token("--p-content-border-color") },
            },
        },
    };
}
watch([() => props.filas, () => props.series, () => layoutConfig], actualizar, {
    deep: true,
    immediate: true,
});
</script>
<template>
    <section class="k-surface min-w-0 space-y-4 p-5">
        <h2 class="text-lg font-semibold">{{ titulo }}</h2>
        <p v-if="descripcion" class="text-sm text-muted-color">{{ descripcion }}</p>
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
        <p v-else class="text-muted-color">No hay datos comparables disponibles.</p>
        <details v-if="filas.length">
            <summary class="cursor-pointer py-2 font-semibold">Ver datos de la gráfica</summary>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="p-2 text-left">Fecha / concepto</th>
                            <th v-for="s in series" :key="s.clave" class="p-2 text-right">
                                {{ s.nombre }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(f, i) in filas" :key="i">
                            <th class="p-2 text-left font-normal">{{ f.etiqueta }}</th>
                            <td
                                v-for="s in series"
                                :key="s.clave"
                                class="k-financial p-2 text-right"
                            >
                                {{ f[s.clave] == null ? "Sin dato" : format(f[s.clave]) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    </section>
</template>
