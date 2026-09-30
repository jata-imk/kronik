<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import SimulationTaxSummary from "@/Components/Products/SimulationTaxSummary.vue";
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
defineProps({ credito: Object, desembolso: Object, cronograma: Object, movimientos: Object, puedeVerContrato: Boolean });
const fecha = value => value?.slice(0, 10);
</script>
<template>
    <AppLayout :title="`Crédito CR-${credito.id}`">
        <template #card-content>
            <div class="space-y-6 p-4 md:p-6">
                <nav class="flex flex-wrap gap-4 text-sm" aria-label="Contexto del crédito"><Link :href="route('creditos.index')" class="text-primary hover:underline"><i class="pi pi-arrow-left mr-2" />Créditos</Link><Link :href="route('solicitudes.show', credito.solicitud_id)" class="text-primary hover:underline">Solicitud SOL-{{ credito.solicitud_id }}</Link><Link :href="route('clientes.expediente.show', credito.cliente_id)" class="text-primary hover:underline">Expediente del cliente</Link><Link v-if="puedeVerContrato" :href="route('solicitudes.paquete.show', credito.solicitud_id)" class="text-primary hover:underline">Contrato y firma</Link></nav>
                <header><p class="text-xs font-semibold uppercase tracking-widest text-primary">Crédito simple · MXN</p><h1 class="mt-2 text-2xl font-bold">Crédito CR-{{ credito.id }}</h1><p class="mt-2 text-surface-500">{{ credito.cliente.primer_nombre }} {{ credito.cliente.apellido_paterno }} · {{ credito.sucursal.nombre }}</p></header>
                <section class="rounded-2xl bg-gradient-to-br from-slate-950 to-violet-700 p-6 text-white" aria-label="Resumen del crédito">
                    <Tag value="Activo · QA" severity="success" /><h2 class="mt-4 text-xl font-semibold text-white">Desembolso registrado</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-3"><div class="rounded-xl bg-white/10 p-4"><p>Efectivo entregado</p><p class="mt-2 text-2xl font-bold">{{ money(desembolso.importe) }}</p></div><div class="rounded-xl bg-white/10 p-4"><p>Capital inicial registrado</p><p class="mt-2 text-2xl font-bold">{{ money(credito.capital_inicial) }}</p></div><div class="rounded-xl bg-white/10 p-4"><p>Fecha efectiva</p><p class="mt-2 text-xl font-semibold">{{ fecha(desembolso.fecha_efectiva) }}</p></div></div>
                </section>
                <Message severity="info" :closable="false">Siguiente paso: registro de pagos en el próximo incremento. Esta entrega conserva el capital inicial; no presenta intereses proyectados como deuda devengada ni marca cargos separados como pagados.</Message>
                <div class="grid gap-5 md:grid-cols-2">
                    <section class="space-y-3 rounded-2xl border border-surface-200 p-5 dark:border-surface-700"><h2 class="font-semibold"><i class="pi pi-send mr-2 text-primary" />Transferencia manual QA</h2><p class="break-all">Referencia: {{ desembolso.referencia }}</p><p>Registrado el {{ new Date(desembolso.created_at).toLocaleString('es-MX') }} · usuario #{{ desembolso.registrado_por }}</p><p class="text-sm text-surface-500">No se ejecutó una transferencia bancaria. El registro es inmutable; no lo borres ni repitas para corregirlo.</p></section>
                    <section class="space-y-3 rounded-2xl border border-surface-200 p-5 dark:border-surface-700"><h2 class="font-semibold"><i class="pi pi-lock mr-2 text-primary" />Condiciones conservadas</h2><p>Responsable: {{ credito.responsable.name }}</p><p>Calendario versión {{ cronograma.version }} · tomado de la firma</p><p class="break-all text-xs text-surface-500">Huella: {{ cronograma.snapshot_hash }}</p><p class="text-sm text-surface-500">Cambiar el producto o la plantilla no cambia este crédito. Los ajustes posteriores requerirán operaciones trazables.</p></section>
                </div>
                <section class="space-y-4 rounded-2xl border border-surface-200 p-5 dark:border-surface-700"><h2 class="text-lg font-semibold">Calendario firmado · proyección</h2><SimulationTaxSummary :simulation="cronograma.tabla" />
                    <DataTable :value="cronograma.tabla.tabla" paginator :rows="10" stripedRows scrollable><Column field="numero" header="#" /><Column field="fecha" header="Fecha" /><Column v-for="col in [{key:'capital',label:'Capital'},{key:'interes',label:'Interés'},{key:'comisiones',label:'Comisiones'},{key:'impuestos',label:'Impuestos'},{key:'pago_total',label:'Total proyectado'}]" :key="col.key" :header="col.label"><template #body="{data}">{{ money(data[col.key]) }}</template></Column></DataTable>
                </section>
                <section class="space-y-4 rounded-2xl border border-surface-200 p-5 dark:border-surface-700"><h2 class="text-lg font-semibold">Movimientos registrados</h2><p class="text-sm text-surface-500">El movimiento de capital incluye los cargos financiados. No es el total de intereses futuros ni sustituye el comprobante de transferencia.</p><DataTable :value="movimientos.data" scrollable><Column header="Movimiento"><template #body>Capital inicial</template></Column><Column header="Fecha efectiva"><template #body="{data}">{{ fecha(data.fecha_efectiva) }}</template></Column><Column header="Importe"><template #body="{data}">{{ money(data.importe) }}</template></Column><Column field="actor_id" header="Usuario" /></DataTable></section>
            </div>
        </template>
    </AppLayout>
</template>
