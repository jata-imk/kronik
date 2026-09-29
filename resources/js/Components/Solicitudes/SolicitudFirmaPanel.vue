<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import PrivateDocumentViewer from "@/Components/Documents/PrivateDocumentViewer.vue";

const props = defineProps({ solicitud: Object, actual: Object, firmas: Object, requisitos: Object, formalizacion: Object, pendiente: Boolean, can: Object, maxArchivoKb: Number });
const drawer = ref(false);
const seleccion = ref(null);
const viewer = ref(false);
const archivo = ref(null);
const recibir = useForm({ archivo: null, fecha_firma: "", confirmacion_qa: false, idempotency_key: crypto.randomUUID(), lock_version: props.solicitud.lock_version });
const revision = useForm({ estado_firma: "aceptada", motivo: "", confirmacion_revision: false, confirmacion_qa: false, lock_version: props.solicitud.lock_version });
const bloqueos = computed(() => props.requisitos?.requisitos.filter(r => !r.cumplido) ?? []);
const fechaHora = value => value ? new Date(value).toLocaleString("es-MX") : "";
const estados = { recibida: ["Pendiente de revisión", "warn"], aceptada: ["Firma aceptada", "success"], rechazada: ["Copia rechazada", "danger"] };
function enviar() {
    recibir.lock_version = props.solicitud.lock_version;
    recibir.post(route("solicitudes.firmas.store", [props.solicitud.id, props.actual.id]), { preserveScroll: true, onSuccess: () => { drawer.value = false; recibir.reset(); recibir.idempotency_key = crypto.randomUUID(); } });
}
function revisar(item) {
    seleccion.value = item;
    // Inertia promotes successful submissions to defaults; never reuse a previous verdict or confirmation.
    revision.defaults({ estado_firma: "aceptada", motivo: "", confirmacion_revision: false, confirmacion_qa: false, lock_version: props.solicitud.lock_version });
    revision.reset();
    revision.clearErrors();
}
function confirmar() {
    revision.lock_version = props.solicitud.lock_version;
    revision.post(route("solicitudes.firmas.review", [props.solicitud.id, seleccion.value.id]), { preserveScroll: true, onSuccess: () => { seleccion.value = null; } });
}
function ver(item) { archivo.value = item; viewer.value = true; }
</script>

<template>
    <section class="space-y-4 rounded-2xl border border-surface-200 p-5 dark:border-surface-700" aria-label="Firma del contrato">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-widest text-primary"><i class="pi pi-pencil mr-2" aria-hidden="true" />Firma autógrafa digitalizada</p><h2 class="mt-2 text-xl font-semibold">Recibir y revisar la copia firmada</h2></div>
            <Button v-if="can.recibirFirma && actual && !pendiente && !formalizacion" label="Recibir copia firmada" icon="pi pi-upload" :disabled="!requisitos?.permitido" @click="drawer = true" />
        </div>
        <p class="text-sm text-surface-600 dark:text-surface-300">Descarga el contrato y su tabla, reúne las firmas y digitaliza todas las páginas en un PDF. Cargarlo no lo aprueba: una persona autorizada debe compararlo con el original. El sistema conserva archivos y responsables; no autentica automáticamente una firma ni utiliza firma electrónica.</p>
        <Message v-if="formalizacion" severity="success" :closable="false"><strong>Formalizada en QA.</strong> La copia fue aceptada y quedó ligada al contrato y tabla originales. No hay desembolso ni autorización para dinero real.</Message>
        <Message v-else-if="!actual" severity="info" :closable="false">Primero prepara el contrato y la tabla de pagos de la aprobación actual.</Message>
        <Message v-else-if="pendiente" severity="info" :closable="false">Hay una copia pendiente. Un responsable con permiso de revisión debe aceptarla o rechazarla. No necesitas volver a subirla.</Message>
        <ul v-if="!formalizacion && actual && bloqueos.length" class="space-y-2" aria-label="Pendientes para aceptar la firma">
            <li v-for="item in bloqueos" :key="item.clave" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100"><i class="pi pi-exclamation-circle mr-2" aria-hidden="true" />{{ item.mensaje }}</li>
        </ul>
        <div v-if="!firmas?.data?.length" class="rounded-xl bg-surface-50 p-5 text-sm dark:bg-surface-900">Todavía no se han recibido copias firmadas. Si no ves la acción, solicita el permiso de recepción al administrador.</div>
        <article v-for="item in firmas?.data ?? []" :key="item.id" class="space-y-3 rounded-xl border border-surface-200 p-4 dark:border-surface-700">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold">Copia #{{ item.id }} · {{ item.actual ? 'Contrato actual' : 'Contrato histórico' }}</h3><p class="mt-1 text-sm text-surface-500">Firmada el {{ item.fecha_firma }} · Recibida por {{ item.receptor ?? `usuario #${item.recibida_por}` }} el {{ fechaHora(item.recibida_en) }}<span v-if="item.revisada_por"> · Revisada por {{ item.revisor ?? `usuario #${item.revisada_por}` }} el {{ fechaHora(item.revisada_en) }}</span></p></div><Tag :value="estados[item.estado]?.[0]" :severity="estados[item.estado]?.[1]" /></div>
            <p v-if="item.motivo" class="rounded-lg bg-surface-50 p-3 text-sm dark:bg-surface-900">Observaciones: {{ item.motivo }}</p>
            <div class="flex flex-wrap gap-2"><Button label="Ver copia firmada" icon="pi pi-eye" severity="secondary" @click="ver(item)" /><Button v-if="can.revisarFirma && item.actual && item.estado === 'recibida' && solicitud.estado === 'aprobada'" label="Revisar firma" icon="pi pi-check-square" @click="revisar(item)" /></div>
            <p class="break-all text-xs text-surface-500">Huella del archivo: {{ item.archivo_hash }}</p>
        </article>
        <nav v-if="firmas?.last_page > 1" class="flex justify-between gap-3" aria-label="Páginas de firmas"><Link v-if="firmas.prev_page_url" :href="firmas.prev_page_url" class="text-primary">Anteriores</Link><span>Página {{ firmas.current_page }} de {{ firmas.last_page }}</span><Link v-if="firmas.next_page_url" :href="firmas.next_page_url" class="text-primary">Siguientes</Link></nav>
        <Drawer v-model:visible="drawer" position="right" header="Recibir copia firmada" class="!w-full md:!w-[36rem]">
            <form class="space-y-5" @submit.prevent="enviar">
                <Message severity="warn" :closable="false">Prueba QA sin validez contractual. Adjunta el contrato completo, incluidas la tabla y las firmas. Una nueva copia no reemplaza la evidencia rechazada.</Message>
                <div><label for="firma-archivo" class="mb-2 block font-semibold">Contrato firmado en PDF *</label><input id="firma-archivo" type="file" accept=".pdf,application/pdf" required class="w-full rounded-lg border border-surface-300 p-3" @change="recibir.archivo = $event.target.files?.[0] ?? null" /><p class="mt-2 text-sm text-surface-500">PDF legible de hasta {{ maxArchivoKb }} KB. El archivo será privado y se conservará su huella.</p></div>
                <div><label for="firma-fecha" class="mb-2 block font-semibold">Fecha de firma *</label><InputText id="firma-fecha" v-model="recibir.fecha_firma" type="date" required class="w-full" /><p class="mt-2 text-sm text-surface-500">Fecha indicada en el documento, entre su generación y hoy. La fecha de recepción se registra por separado.</p></div>
                <label class="flex items-start gap-3"><Checkbox v-model="recibir.confirmacion_qa" binary /><span>Confirmo que es una firma de prueba QA, sin validez contractual.</span></label>
                <Message v-for="(error,key) in recibir.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
                <p v-if="recibir.progress" role="status">Enviando {{ recibir.progress.percentage }} %</p>
                <Button type="submit" label="Enviar copia para revisión" icon="pi pi-upload" :loading="recibir.processing" class="w-full" />
            </form>
        </Drawer>
        <Drawer :visible="!!seleccion" @update:visible="value => { if (!value) seleccion = null; }" position="right" header="Revisar firma" class="!w-full md:!w-[38rem]">
            <form v-if="seleccion" class="space-y-5" @submit.prevent="confirmar">
                <Message severity="info" :closable="false">Compara ambas versiones: mismo cliente y condiciones, todas las páginas y anexos, firmas legibles y fecha correcta. No aceptes una copia incompleta o alterada.</Message>
                <div class="flex flex-wrap gap-2"><Button label="Ver original" severity="secondary" @click="ver(actual.documento)" /><Button label="Ver copia firmada" severity="secondary" @click="ver(seleccion)" /></div>
                <div><label for="firma-resultado" class="mb-2 block font-semibold">Resultado *</label><Select inputId="firma-resultado" aria-label="Resultado de firma" v-model="revision.estado_firma" :options="[{label:'Aceptar firma y formalizar QA',value:'aceptada'},{label:'Rechazar copia y pedir corrección',value:'rechazada'}]" optionLabel="label" optionValue="value" class="w-full" /></div>
                <div><label for="firma-motivo" class="mb-2 block font-semibold">Observaciones {{ revision.estado_firma === 'rechazada' ? '*' : '(opcional)' }}</label><Textarea id="firma-motivo" v-model="revision.motivo" :required="revision.estado_firma === 'rechazada'" minlength="10" maxlength="2000" rows="3" class="w-full" /><p class="text-sm text-surface-500">Explica qué debe corregirse. Visible para quienes consultan el contrato; no incluyas información reservada de PLD o SIC.</p></div>
                <label v-if="revision.estado_firma === 'aceptada'" class="flex items-start gap-3"><Checkbox v-model="revision.confirmacion_revision" binary /><span>Comparé todas las páginas, condiciones, identidad y firmas con el original.</span></label>
                <label class="flex items-start gap-3"><Checkbox v-model="revision.confirmacion_qa" binary /><span>Confirmo la revisión exclusivamente QA, sin autorización de dinero real.</span></label>
                <Message v-for="(error,key) in revision.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
                <Button type="submit" :label="revision.estado_firma === 'aceptada' ? 'Aceptar firma y formalizar QA' : 'Confirmar rechazo de copia'" :severity="revision.estado_firma === 'aceptada' ? 'success' : 'danger'" :disabled="revision.estado_firma === 'aceptada' && !requisitos?.permitido" :loading="revision.processing" class="w-full" />
            </form>
        </Drawer>
        <PrivateDocumentViewer v-model:visible="viewer" :url="archivo?.view_url ?? ''" :downloadUrl="archivo?.download_url ?? ''" name="Contrato QA" />
    </section>
</template>
