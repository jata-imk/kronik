<script setup>
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
import { taxConceptLabel } from "./fiscalidad";
defineProps({ simulation: { type: Object, required: true } });
</script>

<template>
    <section class="space-y-3" aria-label="Resultado fiscal">
        <Message :severity="simulation.fiscalidad?.estado === 'proyeccion' ? 'info' : 'warn'" :closable="false">{{ simulation.fiscalidad?.leyenda ?? 'Escenario anterior a impuestos.' }}</Message>
        <template v-if="simulation.fiscalidad?.estado === 'proyeccion'">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-gradient-to-br from-primary-700 to-indigo-700 p-4 text-white"><p class="text-xs uppercase tracking-wide"><i class="pi pi-percentage mr-2" aria-hidden="true" />Impuestos proyectados</p><p class="mt-2 text-2xl font-bold">{{ money(simulation.total_impuestos) }}</p><p class="mt-2 text-xs">{{ simulation.fiscalidad.uso === 'prueba' ? 'Configuración de prueba / QA' : 'Configuración declarada por la institución, no certificada por el sistema' }}</p></div>
                <div v-for="item in [{key:'impuestos_pago_separado',label:'Impuesto al inicio, pago separado'},{key:'impuestos_retenidos',label:'Impuesto retenido del desembolso'},{key:'impuestos_financiados',label:'Impuesto financiado en el saldo'}]" :key="item.key" class="rounded-xl border border-surface-200 p-4 dark:border-surface-700"><p class="text-sm text-surface-500">{{ item.label }}</p><p class="mt-2 text-lg font-semibold">{{ money(simulation.totales[item.key]) }}</p></div>
            </div>
            <p class="text-sm text-surface-600 dark:text-surface-300">El pago total de la tabla ya incluye los impuestos del periodo. Los financiados se amortizan dentro del capital, no se vuelven a sumar en cada cuota. La fila de disposición muestra su desglose, no un cobro adicional. El CAT base conserva su escenario anterior a impuestos.</p>
            <details class="rounded-xl border border-surface-200 p-4 dark:border-surface-700">
                <summary class="cursor-pointer font-semibold text-primary">Ver desglose fiscal por periodo y concepto</summary>
                <div class="mt-3 max-h-96 space-y-3 overflow-y-auto">
                    <section v-for="row in simulation.tabla" :key="row.numero" class="rounded-lg bg-surface-50 p-3 dark:bg-surface-800">
                        <h4 class="font-semibold">{{ row.numero === 0 ? 'Disposición' : `Pago ${row.numero}` }} · {{ row.fecha }} · Impuestos {{ money(row.impuestos) }}</h4>
                        <ul class="mt-2 space-y-2 text-sm"><li v-for="(item,index) in row.impuestos_detalle" :key="index"><span class="font-medium">{{ item.concepto }}</span> · {{ taxConceptLabel(item) }}<span v-if="item.tratamiento === 'gravado'"> · {{ money(item.importe_concepto) }} × {{ item.tasa }} %</span> · {{ money(item.impuesto) }}</li></ul>
                        <p v-if="!row.impuestos_detalle.length" class="mt-2 text-sm text-surface-500">Sin cargos fiscales en esta disposición.</p>
                    </section>
                </div>
            </details>
        </template>
    </section>
</template>
