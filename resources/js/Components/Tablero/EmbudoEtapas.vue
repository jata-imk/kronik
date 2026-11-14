<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
const props = defineProps({
    etapas: { type: Array, default: () => [] },
    filtros: Object,
});
const orden = [
    "borrador",
    "en_revision",
    "aprobada",
    "formalizada",
    "desembolsada",
];
const tonos = ["info", "secundario", "advertencia", "principal", "principal"];
const proceso = computed(() =>
    orden
        .map((estado) => props.etapas.find((fila) => fila.estado === estado))
        .filter(Boolean),
);
const otras = computed(() =>
    props.etapas.filter(
        (fila) => !orden.includes(fila.estado) && fila.total > 0,
    ),
);
const maximo = computed(() =>
    Math.max(1, ...proceso.value.map((fila) => fila.total)),
);
function poligono(fila, i) {
    const ancho = (fila.total / maximo.value) * 280;
    const y = i * 42;
    return `${160 - ancho / 2},${y} ${160 + ancho / 2},${y} ${160 + ancho * 0.43},${y + 32} ${160 - ancho * 0.43},${y + 32}`;
}
const enlace = (estado) =>
    route("solicitudes.index", {
        scope: props.filtros?.scope,
        sucursal_id:
            props.filtros?.scope === "all"
                ? props.filtros.sucursal_id
                : undefined,
        estado,
    });
</script>
<template>
    <section
        v-if="etapas.length"
        class="k-surface k-panel mb-6 space-y-4"
        aria-labelledby="etapas-title"
    >
        <div class="k-tablero-panel-head">
            <div>
                <p class="k-eyebrow">Originación · situación actual</p>
                <h2 id="etapas-title">Embudo de etapas</h2>
            </div>
            <Link class="k-tablero-text-link" :href="enlace(undefined)"
                >Ver solicitudes
                <i class="pi pi-arrow-right" aria-hidden="true"
            /></Link>
        </div>
        <div class="k-tablero-embudo">
            <svg
                viewBox="0 0 320 210"
                role="img"
                aria-label="Volumen actual de solicitudes en cada etapa"
            >
                <g v-for="(fila, i) in proceso" :key="fila.estado">
                    <path
                        :d="`M 12 ${i * 42 + 16} H 308`"
                        class="k-tablero-embudo-guia"
                    />
                    <polygon
                        :points="poligono(fila, i)"
                        :class="`k-tono-${tonos[i]}`"
                    >
                        <title>{{ fila.etiqueta }}: {{ fila.total }}</title>
                    </polygon>
                </g>
            </svg>
            <ol class="k-tablero-etapas">
                <li v-for="(fila, i) in proceso" :key="fila.estado">
                    <Link :href="enlace(fila.estado)"
                        ><span
                            ><i
                                class="k-tablero-punto"
                                :class="`k-tono-${tonos[i]}`"
                                aria-hidden="true"
                            />{{ fila.etiqueta }}</span
                        ><strong class="k-financial">{{
                            fila.total.toLocaleString("es-MX")
                        }}</strong
                        ><i class="pi pi-arrow-up-right" aria-hidden="true"
                    /></Link>
                </li>
            </ol>
        </div>
        <div v-if="otras.length" class="flex flex-wrap gap-2">
            <Link
                v-for="fila in otras"
                :key="fila.estado"
                :href="enlace(fila.estado)"
                class="k-tablero-badge"
                :class="
                    fila.estado === 'rechazada'
                        ? 'k-tono-peligro'
                        : 'k-tono-advertencia'
                "
                >{{ fila.etiqueta }} · {{ fila.total }}</Link
            >
        </div>
        <p class="k-tablero-ayuda">
            Cada banda representa solicitudes en su estado actual; puede
            ensancharse en etapas posteriores. No es una tasa de conversión
            histórica ni una cohorte de solicitudes.
        </p>
    </section>
</template>
