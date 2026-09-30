<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import axios from "axios";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import DistribucionPago from "@/Components/Creditos/DistribucionPago.vue";
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
const props = defineProps({ credito: Object, situacion: Object, hoy: String, habilitado: Boolean });
const form = useForm({ fecha_pago: props.hoy, importe: null, referencia: "", idempotency_key: crypto.randomUUID(), confirmacion_qa: false, previa_hash: "" });
const previa = ref(null);
const loading = ref(false);
const error = ref("");
let revision = 0;
watch(() => [form.fecha_pago, form.importe, form.referencia], () => { revision++; previa.value = null; form.previa_hash = ""; form.confirmacion_qa = false; error.value = ""; }, { flush: "sync" });
async function revisar() {
    const version = ++revision;
    previa.value = null;
    error.value = "";
    loading.value = true;
    form.clearErrors();
    try {
        const { data } = await axios.post(route("creditos.pagos.preview", props.credito.id), form.data());
        if (version === revision) { previa.value = data; form.previa_hash = data.previa_hash; }
    } catch (e) {
        if (version === revision) error.value = Object.values(e.response?.data?.errors ?? {}).flat().join(" ") || "No se pudo revisar el pago. Comprueba tu sesión y vuelve a intentarlo; no se ha registrado nada.";
    } finally { loading.value = false; }
}
function guardar() {
    if (!previa.value || !form.confirmacion_qa || loading.value) return;
    form.post(route("creditos.pagos.store", props.credito.id), { onError: () => { previa.value = null; form.previa_hash = ""; form.confirmacion_qa = false; } });
}
</script>
<template>
    <AppLayout title="Registrar pago QA"><template #card-content>
        <div class="space-y-6 p-4 md:p-6">
            <Link :href="route('creditos.show', credito.id)" class="inline-flex items-center gap-2 text-primary hover:underline"><i class="pi pi-arrow-left" aria-hidden="true" />Volver al crédito CR-{{ credito.id }}</Link>
            <header><p class="text-xs uppercase tracking-widest text-primary">Recepción y aplicación · QA</p><h1 class="mt-2 text-2xl font-bold">Registrar pago</h1><p class="mt-2 text-surface-500">{{ credito.cliente.primer_nombre }} {{ credito.cliente.apellido_paterno }} · {{ credito.sucursal.nombre }}</p></header>
            <section class="rounded-2xl bg-gradient-to-br from-slate-950 to-violet-700 p-6 text-white"><Tag value="Solo registro manual QA" severity="warn" /><h2 class="mt-4 text-xl font-semibold text-white">Primero revisa, después confirma</h2><p class="mt-2">Captura la fecha y el importe realmente recibidos en el escenario sintético. La vista previa no registra dinero.</p><p v-if="situacion.resumen" class="mt-4">Exigible hoy: <strong class="text-xl">{{ money(situacion.resumen.exigible) }}</strong> · Capital insoluto: {{ money(situacion.resumen.capital_insoluto) }}</p></section>
            <Message v-if="!habilitado" severity="warn" :closable="false">El administrador técnico debe habilitar ORIGINACION_PAGOS_QA_HABILITADOS y reconstruir la caché exclusivamente en QA.</Message>
            <Message v-if="situacion.error" severity="warn" :closable="false">{{ situacion.error }}</Message>
            <div class="grid gap-5 lg:grid-cols-3">
                <form class="space-y-5 rounded-2xl border border-surface-200 p-5 lg:col-span-2 dark:border-surface-700" @submit.prevent="revisar">
                    <div class="grid gap-4 sm:grid-cols-2"><div><label for="pago-fecha" class="mb-2 block font-semibold">Fecha efectiva del pago *</label><InputText id="pago-fecha" v-model="form.fecha_pago" type="date" :min="credito.fecha_desembolso?.slice(0,10)" :max="hoy" :disabled="form.processing" fluid /><p class="mt-2 text-sm text-surface-500">Puede ser pasada solo si no existen movimientos posteriores del crédito. La fecha de captura se conserva por separado.</p></div><div><label for="pago-importe" class="mb-2 block font-semibold">Importe recibido (MXN) *</label><InputNumber input-id="pago-importe" v-model="form.importe" mode="currency" currency="MXN" locale="es-MX" :min-fraction-digits="2" :max-fraction-digits="2" :disabled="form.processing" fluid /><p class="mt-2 text-sm text-surface-500">Captura el importe completo. No se crea saldo a favor ni se convierte un excedente en anticipo.</p></div></div>
                    <div><label for="pago-referencia" class="mb-2 block font-semibold">Referencia única del pago *</label><InputText id="pago-referencia" v-model="form.referencia" minlength="5" maxlength="120" :disabled="form.processing" fluid /><p class="mt-2 text-sm text-surface-500">Identificador sintético, por ejemplo QA-PAGO-001. No incluyas cuentas bancarias ni reutilices la referencia.</p></div>
                    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message><Message v-for="(message,key) in form.errors" :key="key" severity="error" :closable="false">{{ message }}</Message>
                    <Button type="submit" label="Revisar distribución" icon="pi pi-search" :loading="loading" :disabled="!habilitado || !!situacion.error || form.processing" />
                </form>
                <aside class="space-y-3 rounded-2xl bg-primary-50 p-5 dark:bg-primary-950/30"><h2 class="font-semibold"><i class="pi pi-info-circle mr-2" aria-hidden="true" />Cómo se aplicará</h2><p class="text-sm">Cuota más antigua primero: comisión, moratorio, ordinario y capital. Cada concepto gravado se paga junto con su impuesto proporcional.</p><p class="text-sm">B + sustitución: durante gracia solo se admite cubrir completa la cuota afectada. El sistema explica si tu pago está bloqueado; no alteres su fecha o importe para evitarlo.</p><p class="text-sm">Registrar aquí no cobra a un banco, no factura y no acredita cumplimiento fiscal.</p></aside>
            </div>
            <section v-if="previa" class="space-y-4 rounded-2xl border border-primary-300 p-5" aria-label="Vista previa de pago"><h2 class="text-xl font-semibold">Distribución propuesta</h2><DistribucionPago :filas="previa.asignaciones" /><div class="grid gap-3 sm:grid-cols-2"><Message severity="info" :closable="false">Capital después: {{ money(previa.despues.capital_insoluto) }}</Message><Message severity="info" :closable="false">Exigible después: {{ money(previa.despues.exigible) }}</Message></div><label class="flex gap-3"><Checkbox v-model="form.confirmacion_qa" binary /><span>Verifiqué cliente, fecha, referencia y distribución. Confirmo un pago sintético exclusivamente QA.</span></label><Button label="Confirmar pago QA" icon="pi pi-check" :loading="form.processing" :disabled="!form.confirmacion_qa || loading" @click="guardar" /><p class="text-sm text-surface-500">Cambiar la captura invalida esta revisión. Si otro operador registra un movimiento, deberás revisarla nuevamente.</p></section>
        </div>
    </template></AppLayout>
</template>
