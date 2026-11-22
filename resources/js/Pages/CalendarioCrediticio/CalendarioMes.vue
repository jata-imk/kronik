<script setup>
import { toRef } from "vue";
import { useCalendarioCrediticio } from "@/Composables/useCalendarioCrediticio";

const props = defineProps({ version: Object, hoy: String });
const { seleccionado, etiquetaMes, celdas, detalle, moverMes, leyenda } = useCalendarioCrediticio(toRef(props, "version"), props.hoy);
const dias = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];
</script>

<template>
    <div class="grid gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(14rem,1fr)]">
        <section class="rounded-xl border border-surface-200 p-3 dark:border-surface-700 sm:p-5">
            <div class="mb-4 flex items-center justify-between gap-2">
                <h2 class="m-0 text-xl font-semibold capitalize" aria-live="polite">{{ etiquetaMes }}</h2>
                <div class="flex gap-1">
                    <Button icon="pi pi-chevron-left" text rounded aria-label="Mes anterior" @click="moverMes(-1)" />
                    <Button icon="pi pi-chevron-right" text rounded aria-label="Mes siguiente" @click="moverMes(1)" />
                </div>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center">
                <span v-for="dia in dias" :key="dia" class="text-xs font-semibold text-surface-500 dark:text-surface-400">{{ dia }}</span>
                <template v-for="celda in celdas" :key="celda.clave">
                    <Button v-if="celda.fecha" type="button" size="small" :severity="celda.severidad" :outlined="seleccionado === celda.fecha"
                        :text="seleccionado !== celda.fecha" class="min-h-11 !min-w-0 !px-0"
                        :aria-label="`${celda.fecha}, ${celda.texto}`" :aria-pressed="seleccionado === celda.fecha" @click="seleccionado = celda.fecha">
                        <span class="flex flex-col items-center gap-0.5">
                            <span>{{ celda.dia }}</span>
                            <i v-if="celda.tipo !== 'habil'" class="pi !text-xs" :class="celda.icono" aria-hidden="true" />
                        </span>
                    </Button>
                    <span v-else aria-hidden="true" />
                </template>
            </div>
        </section>
        <aside class="space-y-4">
            <div class="rounded-xl bg-surface-100 p-4 dark:bg-surface-800" aria-live="polite">
                <h2 class="mb-2 capitalize">{{ detalle.fecha }}</h2>
                <Tag :value="detalle.texto" :severity="detalle.severidad" :icon="`pi ${detalle.icono}`" />
                <p class="mb-0 mt-2">{{ detalle.motivo }}</p>
            </div>
            <ul class="flex flex-wrap gap-2" aria-label="Leyenda del calendario">
                <li v-for="estado in leyenda" :key="estado.texto"><Tag :value="estado.texto" :severity="estado.severidad" :icon="`pi ${estado.icono}`" /></li>
            </ul>
        </aside>
    </div>
</template>
