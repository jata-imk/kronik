<script setup>
import { Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PrivateDocumentViewer from "@/Components/Documents/PrivateDocumentViewer.vue";
import SimulationTaxSummary from "@/Components/Products/SimulationTaxSummary.vue";
import { useAsyncPolling } from "@/Composables/useAsyncPolling";
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
import axios from "axios";
import { computed, ref, watch } from "vue";

const props = defineProps({ solicitud: Object, preparacion: Object, actual: Object, paquetes: Object, plantillas: Array, can: Object });
const conImpuestos = computed(() => props.actual?.tabla?.fiscalidad?.estado === "proyeccion");
const columnas = computed(() => [{field:"capital",label:"Capital"},{field:"interes",label:"Interés"},{field:"comisiones",label:"Comisiones"}, ...(conImpuestos.value ? [{field:"impuestos",label:"Impuestos"}] : []), {field:"pago_total",label: conImpuestos.value ? "Total con impuestos" : "Total sin impuestos"},{field:"saldo_final",label:"Saldo"}]);
const drawer = ref(false);
const viewer = ref(false);
const documento = ref(null);
const aviso = ref("");
const form = useForm({ version_id: null, lock_version: props.solicitud.lock_version, idempotency_key: crypto.randomUUID(), confirmacion_qa: false });
const retry = useForm({});
const pendientes = computed(() => props.preparacion.requisitos.filter((r) => !r.cumplido));
const enProceso = computed(() => props.paquetes.data.filter((p) => ["pendiente", "procesando"].includes(p.documento?.estado)));
const estados = { pendiente: ["En cola", "info"], procesando: ["Generando PDF", "info"], generado: ["PDF disponible", "success"], fallido: ["Generación fallida", "danger"] };
const titulos = { habilitacion: "Habilitación operativa", estado: "Solicitud aprobada", permiso: "Permiso y sucursal", vigencia_aprobacion: "Vigencia de aprobación", politica: "Política definida", politica_vigente: "Política vigente", producto: "Producto vigente", monto: "Límite de monto", fecha: "Fecha estimada", separacion: "Separación de funciones", sic: "Modalidad SIC", persona_fisica: "Identidad fiscal", cliente: "Datos del cliente", evaluacion: "Evaluación", evaluacion_vigente: "Evidencia de evaluación", pld: "Revisión PLD", pld_vigente: "Evidencia PLD", evidencia_aprobada: "Evidencia aprobada", qa: "Habilitación de paquetes QA", sucursal: "Sucursal responsable", fiscalidad: "Configuración fiscal del producto" };
const fecha = (value) => new Date(value).toLocaleString("es-MX");
const { start, stop } = useAsyncPolling(async () => {
    const states = await Promise.all(enProceso.value.map((p) => axios.get(p.documento.status_url)));
    if (states.some((r) => ["generado", "fallido"].includes(r.data.estado))) router.reload({ only: ["actual", "paquetes", "preparacion"] });
    return enProceso.value.length > 0;
}, { onTimeout: () => { aviso.value = "La generación sigue pendiente. Puedes volver más tarde o actualizar; no prepares otro paquete."; }, onError: () => { aviso.value = "No pudimos consultar el estado. Revisa tu conexión y actualiza la página."; } });
watch(enProceso, (items) => { if (items.length) start(); else stop(); }, { immediate: true });
function preparar() {
    form.lock_version = props.solicitud.lock_version;
    form.post(route("solicitudes.paquete.store", props.solicitud.id), { preserveScroll: true, onSuccess: () => { drawer.value = false; } });
}
function abrir(doc) { documento.value = doc; viewer.value = true; }
function reintentar(paquete) { retry.post(route("solicitudes.paquete.retry", [props.solicitud.id, paquete.id]), { preserveScroll: true }); }
</script>

<template>
    <AppLayout :title="`Paquete QA · SOL-${solicitud.id}`">
        <template #card-content>
            <div class="space-y-6 p-4 md:p-6">
                <nav class="flex flex-wrap gap-4 text-sm" aria-label="Contexto del paquete">
                    <Link :href="route('solicitudes.show', solicitud.id)" class="text-primary hover:underline"><i class="pi pi-arrow-left mr-2" aria-hidden="true" />Solicitud SOL-{{ solicitud.id }}</Link>
                    <Link :href="route('clientes.expediente.show', solicitud.cliente_id)" class="text-primary hover:underline"><i class="pi pi-folder-open mr-2" aria-hidden="true" />Expediente del cliente</Link>
                </nav>
                <header class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-primary"><i class="pi pi-file-pdf mr-2" aria-hidden="true" />Preparación contractual</p>
                        <h1 class="text-2xl font-bold">Paquete contractual QA</h1>
                        <p class="mt-2 text-surface-500">Contrato y anexo en un solo PDF privado, con versiones y condiciones conservadas.</p>
                    </div>
                    <Button v-if="can.preparar && !actual" label="Preparar paquete QA" icon="pi pi-plus" :disabled="!preparacion.puede_preparar" @click="drawer = true" />
                </header>

                <section class="paquete-hero rounded-2xl p-5 text-white md:p-6" aria-label="Resumen del paquete">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div><Tag value="Solo pruebas QA" severity="warn" /><h2 class="mt-4 text-xl font-semibold text-white">{{ solicitud.cliente }}</h2><p class="mt-1 text-white/80">SOL-{{ solicitud.id }} · {{ solicitud.sucursal }}</p></div>
                        <i class="pi pi-file-check text-4xl text-white/70" aria-hidden="true" />
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-white/10 p-4"><p class="text-sm text-white/80">Solicitud</p><p class="mt-1 font-semibold">{{ solicitud.estado === 'aprobada' ? 'Aprobada · sin formalizar' : 'Pendiente de aprobación vigente' }}</p></div>
                        <div class="rounded-xl bg-white/10 p-4"><p class="text-sm text-white/80">Paquete de esta aprobación</p><p class="mt-1 font-semibold">{{ actual ? estados[actual.documento?.estado]?.[0] : 'Todavía no preparado' }}</p></div>
                        <div class="rounded-xl bg-white/10 p-4"><p class="text-sm text-white/80">Tratamiento fiscal</p><p class="mt-1 font-semibold">{{ actual ? (conImpuestos ? "Proyección fiscal congelada" : "Histórico anterior a impuestos") : "Se validará al preparar" }}</p></div>
                    </div>
                </section>
                <Message severity="warn" :closable="false"><strong>Documento de prueba sin validez contractual.</strong> Los nuevos paquetes incluyen la proyección fiscal del producto aprobado; los anteriores no se recalculan. Preparar este paquete no formaliza el crédito ni habilita firma, desembolso o pagos.</Message>
                <Message v-if="aviso" severity="info" :closable="false">{{ aviso }} <Button label="Actualizar estado" text size="small" @click="router.reload()" /></Message>
                <Message v-for="(error, key) in retry.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>

                <div class="grid gap-5 lg:grid-cols-3">
                    <section class="rounded-2xl border border-surface-200 p-5 dark:border-surface-700 lg:col-span-2">
                        <div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary dark:bg-primary-900"><i class="pi pi-shield" aria-hidden="true" /></span><div><h2 class="font-semibold">Antes de preparar</h2><p class="text-sm text-surface-500">Se comprueba de nuevo la aprobación y su evidencia.</p></div></div>
                        <Message v-if="!pendientes.length" class="mt-4" severity="success" :closable="false">Los requisitos están completos. {{ actual ? 'El paquete ya conserva los datos de esta aprobación.' : 'Selecciona una plantilla activa para preparar el paquete QA.' }}</Message>
                        <ul v-if="!pendientes.length" class="mt-5 grid gap-4 sm:grid-cols-2" aria-label="Comprobaciones completas">
                            <li v-for="key in ['estado', 'vigencia_aprobacion', 'producto', 'evaluacion', 'pld', 'evidencia_aprobada']" :key="key" class="flex items-center gap-2 text-sm"><i class="pi pi-check-circle text-emerald-600" aria-hidden="true" />{{ titulos[key] }}</li>
                        </ul>
                        <p v-if="!pendientes.length" class="mt-5 rounded-xl bg-surface-50 p-4 text-sm text-surface-600 dark:bg-surface-900 dark:text-surface-300"><i class="pi pi-lock mr-2 text-primary" aria-hidden="true" />Se conservan la revisión aprobada, las condiciones, la tabla y la versión del contrato. Los cambios posteriores no actualizan el PDF original.</p>
                        <ul v-else class="mt-4 divide-y divide-surface-200 dark:divide-surface-700">
                            <li v-for="item in pendientes" :key="item.clave" class="flex gap-3 py-3"><i class="pi pi-exclamation-circle mt-1 text-amber-600" aria-hidden="true" /><div><h3 class="font-semibold">{{ titulos[item.clave] ?? item.clave.replace('documento_', 'Documento: ').replaceAll('_', ' ') }}</h3><p class="mt-1 text-sm text-surface-600 dark:text-surface-300">{{ item.mensaje }}</p></div></li>
                        </ul>
                    </section>
                    <aside class="rounded-2xl border border-surface-200 bg-surface-50 p-5 dark:border-surface-700 dark:bg-surface-900">
                        <h2 class="font-semibold"><i class="pi pi-compass mr-2 text-primary" aria-hidden="true" />Tu siguiente paso</h2>
                        <ol class="mt-4 space-y-4 text-sm">
                            <li><p class="font-semibold text-primary">1. Preparar y revisar el PDF QA</p><p class="mt-1">Comprueba cliente, versión y tabla. Un paquete por aprobación evita copias accidentales.</p></li>
                            <li><p class="font-semibold">2. Firma · próxima entrega</p><p class="mt-1 text-surface-500">La recepción y validación del contrato firmado todavía no están disponibles.</p></li>
                            <li><p class="font-semibold">3. Desembolso y pagos · posteriores</p><p class="mt-1 text-surface-500">No registres transferencias ni cobros usando esta tabla de prueba.</p></li>
                        </ol>
                        <p class="mt-5 text-sm">¿Cambió algo? Devuelve y reenvía desde la solicitud. Una nueva aprobación permite otro paquete sin borrar el anterior.</p>
                    </aside>
                </div>

                <section class="rounded-2xl border border-surface-200 p-5 dark:border-surface-700">
                    <h2 class="text-lg font-semibold">Paquetes conservados</h2><p class="mb-4 text-sm text-surface-500">Los anteriores son evidencia histórica, no autorizaciones vigentes. Sus archivos no se sobrescriben.</p>
                    <div v-if="!paquetes.data.length" class="rounded-xl bg-surface-50 p-8 text-center dark:bg-surface-900"><i class="pi pi-inbox mb-3 text-3xl text-primary" aria-hidden="true" /><h3 class="font-semibold">Aún no hay paquetes</h3><p class="mt-2 text-sm">Completa los requisitos y usa «Preparar paquete QA». Si no ves la acción, solicita al administrador los permisos de preparación y documentos.</p></div>
                    <div v-for="item in paquetes.data" :key="item.id" class="mb-3 rounded-xl border border-surface-200 p-4 dark:border-surface-700">
                        <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold">{{ item.plantilla }} · v{{ item.version }}</h3><p class="mt-1 text-sm text-surface-500">Paquete #{{ item.id }} · {{ fecha(item.created_at) }} · {{ item.actual ? 'Última resolución' : 'Histórico' }}</p></div><div class="flex flex-wrap gap-2"><Tag :value="estados[item.documento?.estado]?.[0] ?? 'Sin archivo'" :severity="estados[item.documento?.estado]?.[1] ?? 'secondary'" /><Button v-if="item.documento?.view_url" label="Ver PDF QA" icon="pi pi-eye" severity="secondary" @click="abrir(item.documento)" /><Button v-if="item.actual && item.documento?.estado === 'fallido' && can.preparar" label="Reintentar PDF" icon="pi pi-refresh" :loading="retry.processing" :disabled="!preparacion.puede_preparar" @click="reintentar(item)" /></div></div>
                        <p v-if="item.documento?.error_mensaje" role="alert" class="mt-3 text-sm text-red-600">{{ item.documento.error_mensaje }}</p>
                        <p class="mt-3 break-all text-xs text-surface-500"><i class="pi pi-lock mr-1" aria-hidden="true" />Huella del paquete: {{ item.snapshot_hash }}</p>
                    </div>
                    <nav v-if="paquetes.last_page > 1" class="flex justify-between gap-4" aria-label="Páginas de paquetes"><Link v-if="paquetes.prev_page_url" :href="paquetes.prev_page_url" class="text-primary">Anteriores</Link><span>Página {{ paquetes.current_page }} de {{ paquetes.last_page }}</span><Link v-if="paquetes.next_page_url" :href="paquetes.next_page_url" class="text-primary">Siguientes</Link></nav>
                </section>
                <section v-if="actual" class="min-w-0 rounded-2xl border border-surface-200 p-5 dark:border-surface-700">
                    <h2 class="text-lg font-semibold">Tabla congelada · {{ conImpuestos ? "con impuestos proyectados" : "antes de impuestos" }}</h2><p class="mb-4 text-sm text-surface-500">Monto {{ money(actual.tabla.monto) }} MXN · {{ actual.tabla.plazo }} pagos. No es un saldo exigible.</p>
                    <SimulationTaxSummary :simulation="actual.tabla" class="mb-5" />
                    <DataTable :value="actual.tabla.tabla" paginator :rows="10" scrollable>
                        <Column field="numero" header="Pago"><template #body="{ data }">{{ data.numero === 0 ? 'Disposición estimada' : data.numero }}</template></Column><Column field="fecha" header="Fecha estimada" />
                        <Column v-for="column in columnas" :key="column.field" :header="column.label"><template #body="{ data }">{{ money(data[column.field]) }}</template></Column>
                    </DataTable>
                </section>
            </div>
            <Drawer v-model:visible="drawer" position="right" header="Preparar paquete QA" class="!w-full md:!w-[36rem]">
                <form class="space-y-5" @submit.prevent="preparar">
                    <Message severity="info" :closable="false">Se conservarán esta versión del contrato, las variables del cliente y una tabla con impuestos calculada con las condiciones de la revisión aprobada. Después de preparar, estos datos no se recalculan al cambiar el producto.</Message>
                    <div>
                        <label for="paquete-plantilla" class="mb-2 block font-semibold">Plantilla de contrato *</label>
                        <Select inputId="paquete-plantilla" aria-label="Plantilla de contrato" v-model="form.version_id" :options="plantillas" optionLabel="label" optionValue="id" placeholder="Selecciona una versión activa" class="w-full" :invalid="!!form.errors.version_id" />
                        <p class="mt-2 text-sm text-surface-500">Estar activa no significa aprobación jurídica. El PDF llevará la marca de prueba QA.</p>
                        <p v-if="!plantillas.length" class="mt-2 text-sm text-amber-700">No hay contratos activos. Pide al administrador de Documentos y plantillas activar una plantilla de prueba de tipo Contrato.</p>
                    </div>
                    <label class="flex items-start gap-3"><Checkbox v-model="form.confirmacion_qa" binary inputId="confirmar-paquete-qa" /><span>Entiendo que este documento es de prueba, sin validez contractual, e incluirá impuestos proyectados según la configuración del producto.</span></label>
                    <Message v-for="(error, key) in form.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
                    <Button type="submit" label="Confirmar preparación QA" icon="pi pi-file-pdf" :loading="form.processing" :disabled="!plantillas.length" class="w-full" />
                </form>
            </Drawer>
            <PrivateDocumentViewer v-model:visible="viewer" :url="documento?.view_url ?? ''" :downloadUrl="documento?.download_url ?? ''" :name="documento?.nombre_archivo ?? 'Paquete QA'" />
        </template>
    </AppLayout>
</template>

<style scoped>
.paquete-hero { background: linear-gradient(115deg, #171c38 0%, #362469 55%, #702be0 100%); }
</style>
