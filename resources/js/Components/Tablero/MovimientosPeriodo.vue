<script setup>
import { computed } from "vue";
import { importeMXN } from "@/Utils/formatosFinancieros";
const props = defineProps({
    cobros: String,
    desembolsos: String,
    recuperado: String,
    periodo: String,
    corte: String,
});
defineEmits(["detalle"]);
const maximo = computed(() =>
    Math.max(Number(props.cobros), Number(props.desembolsos), 0),
);
</script>
<template>
    <section
        class="k-surface k-panel space-y-4"
        aria-labelledby="movimientos-periodo"
    >
        <div class="k-tablero-panel-head">
            <div>
                <p class="k-eyebrow">Movimientos del periodo</p>
                <h2 id="movimientos-periodo">Cobros y desembolsos</h2>
            </div>
            <span class="k-tablero-badge">Registrados</span>
        </div>
        <p class="text-sm text-muted-color">{{ periodo }}</p>
        <dl class="space-y-5">
            <div
                v-for="fila in [
                    {
                        clave: 'cobros',
                        etiqueta: 'Cobros registrados',
                        importe: cobros,
                        tono: 'principal',
                    },
                    {
                        clave: 'desembolsos',
                        etiqueta: 'Desembolsos efectivos',
                        importe: desembolsos,
                        tono: 'secundario',
                    },
                ]"
                :key="fila.clave"
            >
                <div class="flex flex-wrap justify-between gap-2">
                    <dt>{{ fila.etiqueta }}</dt>
                    <dd class="k-financial font-semibold">
                        {{ importeMXN(fila.importe) }}
                    </dd>
                </div>
                <div
                    class="k-tablero-barra k-tablero-barra-grande"
                    aria-hidden="true"
                >
                    <span
                        :class="`k-tono-${fila.tono}`"
                        :style="{
                            width: `${maximo > 0 ? (Number(fila.importe) / maximo) * 100 : 0}%`,
                        }"
                    />
                </div>
                <button
                    type="button"
                    class="k-tablero-text-link mt-2"
                    @click="$emit('detalle', fila.clave)"
                >
                    {{
                        fila.clave === "cobros"
                            ? "Ver pagos registrados"
                            : "Ver desembolsos"
                    }}
                    <i class="pi pi-arrow-up-right" aria-hidden="true" />
                </button>
            </div>
        </dl>
        <div class="k-tablero-recuperado">
            <span>Capital recuperado acumulado {{ corte.toLowerCase() }}</span
            ><strong class="k-financial">{{ importeMXN(recuperado) }}</strong>
        </div>
        <p class="k-tablero-ayuda">
            Movimientos de créditos; no representan utilidad ni saldo de caja.
            La recuperación acumula el capital pagado hasta el corte.
        </p>
    </section>
</template>
