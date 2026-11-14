<script setup>
import { computed, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { importeMXN, fechaCorta } from "@/Utils/formatosFinancieros";
const props = defineProps({ atencion: Object, corte: String });
defineEmits(["detalle"]);
const seleccion = ref("proximos");
const filas = computed(() => props.atencion?.[seleccion.value] ?? []);
</script>

<template>
    <section
        class="k-surface k-panel space-y-4"
        aria-labelledby="atencion-cartera"
    >
        <div class="k-tablero-panel-head">
            <div>
                <p class="k-eyebrow">Seguimiento</p>
                <h2 id="atencion-cartera">Por atender</h2>
            </div>
            <span class="k-tablero-badge">Al {{ fechaCorta(corte) }}</span>
        </div>
        <div
            class="k-tablero-selector"
            role="group"
            aria-label="Créditos por atender"
        >
            <button
                type="button"
                :aria-pressed="seleccion === 'proximos'"
                @click="seleccion = 'proximos'"
            >
                Por vencer
            </button>
            <button
                type="button"
                :aria-pressed="seleccion === 'vencidos'"
                @click="seleccion = 'vencidos'"
            >
                Con atraso
            </button>
        </div>
        <ul v-if="filas.length" class="k-tablero-lista">
            <li v-for="fila in filas" :key="`${fila.credito_id}-${fila.fecha}`">
                <Link
                    :href="route('creditos.show', fila.credito_id)"
                    class="k-tablero-lista-link"
                >
                    <span
                        class="k-tablero-lista-icono"
                        :class="
                            seleccion === 'vencidos'
                                ? 'k-tono-advertencia'
                                : 'k-tono-info'
                        "
                        ><i
                            :class="
                                seleccion === 'vencidos'
                                    ? 'pi pi-clock'
                                    : 'pi pi-calendar'
                            "
                            aria-hidden="true"
                    /></span>
                    <span class="min-w-0"
                        ><strong class="block break-words">{{
                            fila.cliente ||
                            fila.folio ||
                            `Crédito #${fila.credito_id}`
                        }}</strong
                        ><small class="text-muted-color"
                            >{{ fila.cliente ? `${fila.folio} · ` : ""
                            }}{{
                                seleccion === "vencidos"
                                    ? `${fila.dias_atraso} días de atraso`
                                    : `Vence el ${fechaCorta(fila.fecha)}`
                            }}</small
                        ></span
                    >
                    <span
                        class="k-tablero-badge k-financial"
                        :class="
                            seleccion === 'vencidos'
                                ? 'k-tono-advertencia'
                                : 'k-tono-info'
                        "
                        >{{ importeMXN(fila.importe) }}</span
                    >
                </Link>
            </li>
        </ul>
        <p v-else class="k-tablero-vacio">
            <i class="pi pi-check-circle" aria-hidden="true" />{{
                seleccion === "vencidos"
                    ? "Sin créditos con atraso en este corte."
                    : "Sin vencimientos programados en los próximos 28 días."
            }}
        </p>
        <button
            type="button"
            class="k-tablero-text-link"
            @click="
                $emit(
                    'detalle',
                    seleccion === 'vencidos' ? 'vencido' : 'proximos',
                )
            "
        >
            {{
                seleccion === "vencidos"
                    ? "Ver todos los créditos con atraso"
                    : "Ver todos los vencimientos"
            }}
            <i class="pi pi-arrow-right" aria-hidden="true" />
        </button>
        <p class="k-tablero-ayuda">
            Hasta cinco registros: vencimientos por fecha; atrasos por
            antigüedad. Cada enlace abre el crédito exacto.
        </p>
    </section>
</template>
