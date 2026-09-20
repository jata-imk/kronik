<script setup>
import { Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { computed } from "vue";
const props = defineProps({ solicitud: Object, revision: { type: Object, default: null }, can: Object, responsables: Array });
const envio = useForm({ lock_version: props.solicitud.lock_version });
const assignment = useForm({ lock_version: props.solicitud.lock_version, responsable_id: props.solicitud.responsable_id });
const campos = { producto_version_id: "Producto y versión", monto: "Monto", plazo: "Número de pagos", periodicidad: "Periodicidad", metodo: "Amortización", destino: "Destino", fecha_estimada: "Fecha estimada" };
const completos = computed(() => Object.keys(campos).filter((k) => props.solicitud[k] !== null && props.solicitud[k] !== "").length);
const eventos = { creada: "Solicitud creada", borrador_actualizado: "Borrador actualizado", enviada: "Enviada a revisión", asignada: "Responsable asignado" };
function enviar() {
    envio.lock_version = props.solicitud.lock_version;
    envio.post(route("solicitudes.enviar", props.solicitud.id), { preserveScroll: true });
}
function asignar() {
    assignment.lock_version = props.solicitud.lock_version;
    assignment.patch(route("solicitudes.asignar", props.solicitud.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`Solicitud SOL-${solicitud.id}`">
        <template #card-header>
            <div class="flex flex-wrap items-center justify-between gap-3 p-6">
                <div><h1 class="text-2xl font-semibold">Solicitud SOL-{{ solicitud.id }}</h1><p>{{ solicitud.cliente.primer_nombre }} {{ solicitud.cliente.apellido_paterno }} · {{ solicitud.sucursal.nombre }}</p></div>
                <Link :href="route('solicitudes.index')" class="text-primary underline">Volver a solicitudes</Link>
            </div>
        </template>
        <template #card-content>
            <div class="space-y-6 p-6">
                <Message severity="info" :closable="false">Este incremento permite captura y revisión inicial. Aprobación, validación PLD, formalización y desembolso aún no están habilitados.</Message>
                <section class="rounded border border-surface-200 p-4 dark:border-surface-700">
                    <h2 class="text-lg font-semibold">{{ solicitud.estado === "borrador" ? "Borrador" : "En revisión" }}</h2>
                    <p>Responsable: {{ solicitud.responsable.name }}</p>
                    <p>Siguiente paso: {{ solicitud.estado === "borrador" ? "Completar los datos y enviar a revisión." : "Evaluación y cumplimiento pendientes; no hay aprobación ni autorización de desembolso." }}</p>
                    <p class="mt-3">Captura: {{ completos }}/7 datos. No representa avance de aprobación.</p>
                    <ul class="mt-2 grid gap-1 md:grid-cols-2"><li v-for="(label, field) in campos" :key="field">{{ solicitud[field] !== null && solicitud[field] !== "" ? "✓" : "Pendiente:" }} {{ label }}</li></ul>
                </section>
                <div class="flex flex-wrap gap-4">
                    <Link :href="route('clientes.expediente.show', solicitud.cliente_id)" class="text-primary underline">Expediente y documentos del cliente</Link>
                    <Link v-if="can.sic" :href="route('clientes.historial-crediticio.show', solicitud.cliente_id)" class="text-primary underline">Historial SIC</Link>
                    <Link v-if="can.update && solicitud.estado === 'borrador'" :href="route('solicitudes.edit', solicitud.id)" class="text-primary underline">Completar borrador</Link>
                </div>
                <section>
                    <h2 class="text-lg font-semibold">Condiciones solicitadas</h2>
                    <p>Producto: {{ solicitud.producto_version?.producto?.nombre ?? "Pendiente" }} · Monto: {{ solicitud.monto ?? "Pendiente" }} MXN</p>
                    <p>{{ solicitud.plazo ?? "—" }} pagos · {{ solicitud.periodicidad ?? "Periodicidad pendiente" }} · Fecha estimada: {{ solicitud.fecha_estimada ?? "Pendiente" }}</p>
                    <p class="whitespace-pre-wrap">{{ solicitud.destino }}</p>
                </section>
                <section v-if="can.update && solicitud.estado === 'borrador'">
                    <Message v-for="(error, key) in envio.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
                    <Button label="Enviar a revisión" :loading="envio.processing" @click="enviar" />
                    <p class="mt-2 text-sm">Al enviar se congela la revisión y se validan las condiciones contra el producto vigente. No genera consultas SIC ni movimientos monetarios.</p>
                </section>
                <form v-if="can.assign" class="flex flex-wrap items-end gap-3" @submit.prevent="asignar">
                    <div class="flex flex-col gap-1"><label for="sol-responsable">Responsable</label><Select input-id="sol-responsable" aria-label="Responsable" v-model="assignment.responsable_id" :options="responsables" option-label="name" option-value="id" /></div>
                    <Button type="submit" label="Asignar responsable" :loading="assignment.processing" />
                    <Message v-for="(error, key) in assignment.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
                </form>
                <section v-if="revision">
                    <h2 class="text-lg font-semibold">Revisión {{ revision.numero }} conservada</h2>
                    <p class="break-all text-sm">Huella: {{ revision.snapshot_hash }}</p>
                    <p>La simulación guardada es informativa y no incluye IVA. No es contrato ni saldo real.</p>
                    <DataTable :value="revision.snapshot.simulacion_informativa.tabla" scrollable paginator :rows="10">
                        <Column field="numero" header="Pago" /><Column field="fecha" header="Fecha" /><Column field="capital" header="Capital" /><Column field="interes" header="Interés" /><Column field="comisiones" header="Comisiones" /><Column field="pago_total" header="Total proyectado" />
                    </DataTable>
                </section>
                <section>
                    <h2 class="text-lg font-semibold">Actividad reciente</h2>
                    <p class="text-sm">Últimos 30 eventos, del más reciente al más antiguo.</p>
                    <ol class="mt-3 space-y-2"><li v-for="evento in solicitud.eventos" :key="evento.id">{{ eventos[evento.tipo] ?? evento.tipo }} · {{ evento.actor.name }} · {{ new Date(evento.created_at).toLocaleString("es-MX") }}</li></ol>
                </section>
                <Button text label="Actualizar datos de la solicitud" @click="router.reload()" />
            </div>
        </template>
    </AppLayout>
</template>
