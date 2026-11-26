<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    channels: { type: Array, required: true },
    capabilities: { type: Object, required: true },
    rules: { type: Array, required: true },
    ruleDefaults: { type: Object, required: true },
    consents: { type: Array, required: true },
    deliveries: { type: Object, required: true },
    canManage: Boolean,
    contextoOperativo: { type: Object, default: () => ({}) },
    canReadNotifications: Boolean,
    notificationTemplates: { type: Array, default: () => [] },
    canTest: Boolean,
    sendingEnabled: Boolean,
    smsConfiguration: { type: Object, required: true },
    testRecipient: String,
    filters: { type: Object, required: true },
    activeTab: String,
    notice: String,
});

const tab = ref(props.activeTab || "channels");
const expanded = ref(null);
const historyFilters = ref({ channel: props.filters.channel || "", event_type: props.filters.event_type || "", status: props.filters.status || "", cliente_id: props.filters.cliente_id || "", credito_id: props.filters.credito_id || "", from: props.filters.from || "", to: props.filters.to || "" });
const selectedChannel = ref("email");
const selectedEvent = ref("payment_received");
const selectedRuleChannel = ref("email");
const selectedChannelData = computed(() => props.channels.find((item) => item.channel === selectedChannel.value));
const selectedRuleData = computed(() => props.rules.find((item) => item.event_type === selectedEvent.value && item.channel === selectedRuleChannel.value));
const channelForm = useForm({ channel: "email", sender: "", secret: "", enabled: false, eligibility: "unverified", eligibility_reference: "" });
const ruleForm = useForm({ notification_template_id: null, event_type: "payment_received", channel: "email", enabled: false, lead_days: props.ruleDefaults.leadDays, timezone: props.ruleDefaults.timezone, window_start: props.ruleDefaults.windowStart, window_end: props.ruleDefaults.windowEnd, daily_limit: props.ruleDefaults.dailyLimit, subject: "Confirmación de pago", body: "Hola {nombre}, registramos tu pago. Gracias.", external_template_name: "", external_template_locale: "es_MX", external_template_status: "draft" });
const consentForm = useForm({ cliente_id: props.filters.cliente_id || "", channel: "email", purpose: "operational", source: "documento firmado", evidence_reference: "" });
const testForm = useForm({ channel: "email", recipient: props.testRecipient || "" });
const checkForm = useForm({ channel: "sms" });

function loadChannel() {
    channelForm.cancel();
    checkForm.cancel();
    const item = selectedChannelData.value;
    channelForm.defaults({ channel: selectedChannel.value, sender: item?.sender || "", secret: "", enabled: item?.enabled || false, eligibility: item?.eligibility || "unverified", eligibility_reference: item?.eligibility_reference || "" });
    channelForm.reset();
    channelForm.clearErrors();
    checkForm.clearErrors();
}

function loadRule() {
    ruleForm.cancel();
    ruleForm.clearErrors();
    const item = selectedRuleData.value;
    const defaults = { notification_template_id: item?.notification_template_id ?? null, event_type: selectedEvent.value, channel: selectedRuleChannel.value, enabled: item?.enabled || false, lead_days: item?.lead_days ?? props.ruleDefaults.leadDays, timezone: item?.timezone || props.ruleDefaults.timezone, window_start: item?.window_start?.slice(0, 5) || props.ruleDefaults.windowStart, window_end: item?.window_end?.slice(0, 5) || props.ruleDefaults.windowEnd, daily_limit: item?.daily_limit ?? props.ruleDefaults.dailyLimit, subject: item?.subject || (selectedEvent.value === "payment_received" ? "Pago recibido" : "Recordatorio de vencimiento"), body: item?.body || (selectedEvent.value === "payment_received" ? "Hola {nombre}, registramos tu pago." : "Hola {nombre}, tu próximo vencimiento es el {fecha_vencimiento}."), external_template_name: item?.external_template_name || "", external_template_locale: item?.external_template_locale || "es_MX", external_template_status: item?.external_template_status || "draft" };
    ruleForm.defaults(defaults);
    ruleForm.reset();
}

loadChannel();
loadRule();
watch(() => selectedChannelData.value?.id, loadChannel);
watch(() => props.canManage, (allowed) => { if (!allowed) loadChannel(); });
watch([() => props.canManage, () => props.contextoOperativo.team_id, () => props.contextoOperativo.sucursal_id], loadRule);

const compatibleTemplates = computed(() => props.notificationTemplates.filter(item => item.channel === selectedRuleChannel.value && item.event_type === selectedEvent.value));
const preview = computed(() => {
    const due = new Date();
    due.setDate(due.getDate() + Number(ruleForm.lead_days || 0));
    return ruleForm.body.replaceAll("{nombre}", "María").replaceAll("{fecha_vencimiento}", due.toLocaleDateString("es-MX"));
});
const reasons = { notification_template_changed: "Pendiente cancelado por sustitución, retiro o cambio de plantilla.", notification_content_invalid: "Falta un dato requerido por la plantilla.", sms_content_invalid: "El texto o el teléfono SMS no cumple los límites permitidos.", sms_rate_limited: "Esperando disponibilidad del proveedor SMS.", sms_provider_replaced: "Salida de prueba invalidada al sustituir el proveedor SMS." };
const statuses = { prepared: "Preparado", queued: "En cola", sending: "En proceso", accepted: "Aceptado por proveedor", delivered: "Entregado", read: "Leído", failed: "Fallido", cancelled: "Cancelado", uncertain: "Resultado incierto" };

function saveChannel() {
    if (!props.canManage || channelForm.processing) return;
    if (selectedChannelData.value?.enabled && !channelForm.enabled && !window.confirm("¿Desactivar este canal? Los envíos pendientes se detendrán antes de salir.")) return;
    channelForm.transform((data) => selectedChannel.value === "sms" ? { channel: data.channel, enabled: data.enabled, eligibility: data.eligibility, eligibility_reference: data.eligibility_reference } : data).put(route("comunicaciones.channels.update"), { preserveScroll: true, onSuccess: loadChannel });
}
function checkConnection() {
    if (!props.canTest || checkForm.processing) return;
    checkForm.channel = selectedChannel.value;
    checkForm.post(route("comunicaciones.connection.check"), { preserveScroll: true });
}
function saveRule() {
    if (!props.canManage || ruleForm.processing) return;
    ruleForm.put(route("comunicaciones.rules.update"), { preserveScroll: true, onSuccess: loadRule });
}
function saveConsent() { consentForm.post(route("comunicaciones.consents.store", consentForm.cliente_id), { preserveScroll: true, onSuccess: () => consentForm.reset("evidence_reference") }); }
function revokeConsent(item) {
    if (window.confirm("¿Revocar este consentimiento? Los envíos pendientes se cancelarán.")) {
        useForm({}).patch(route("comunicaciones.consents.revoke", [item.cliente_id, item.id]), { preserveScroll: true });
    }
}
function filterHistory() {
    router.get(route("comunicaciones.index"), { ...historyFilters.value, tab: "history" }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Comunicaciones">
        <main class="mx-auto w-full max-w-6xl space-y-5 p-3 sm:p-6">
            <header>
                <h1 class="text-2xl font-semibold">Comunicaciones</h1>
                <p class="text-surface-600 dark:text-surface-300">Configuración y seguimiento del equipo activo. El destinatario no necesita acceder a Kronik.</p>
            </header>
            <div v-if="!sendingEnabled" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:bg-amber-950 dark:text-amber-100" role="status">Los envíos externos están desactivados en este entorno. Configura y prueba con datos autorizados antes de habilitarlos.</div>
            <div v-if="notice" class="rounded-lg border border-green-300 p-3 text-green-800 dark:text-green-200" role="status">{{ notice }}</div>
            <nav class="flex flex-wrap gap-2 border-b border-surface-200 pb-3 dark:border-surface-700" aria-label="Secciones de comunicaciones">
                <button v-for="item in [{ id: 'channels', label: 'Canales' }, { id: 'rules', label: 'Reglas y plantillas' }, { id: 'consents', label: 'Consentimientos' }, { id: 'history', label: 'Historial' }]" :key="item.id" type="button" class="rounded px-3 py-2 focus-visible:outline-2 focus-visible:outline-primary-500" :class="tab === item.id ? 'bg-primary-600 text-white' : 'bg-surface-100 dark:bg-surface-800'" :aria-current="tab === item.id ? 'page' : undefined" @click="tab = item.id">{{ item.label }}</button>
            </nav>

            <section v-if="tab === 'channels'" class="space-y-5">
                <div class="grid gap-3 md:grid-cols-3">
                    <div v-for="item in [{ key: 'email', title: 'Correo' }, { key: 'sms', title: 'SMS' }, { key: 'whatsapp', title: 'WhatsApp' }]" :key="item.key" class="rounded-lg border border-surface-200 bg-surface-0 p-4 dark:border-surface-700 dark:bg-surface-900">
                        <h2 class="font-semibold">{{ item.title }}</h2>
                        <p class="text-sm">{{ channels.find((channel) => channel.channel === item.key)?.enabled ? 'Habilitado por equipo' : 'Deshabilitado' }}</p>
                        <p class="text-sm text-surface-500">{{ channels.find((channel) => channel.channel === item.key)?.eligibility || 'Sin configurar' }}</p>
                        <p v-if="channels.find((channel) => channel.channel === item.key)?.checked_at" class="text-xs text-surface-500">Última consulta técnica: {{ new Date(channels.find((channel) => channel.channel === item.key).checked_at).toLocaleString('es-MX') }}</p>
                        <div v-if="item.key === 'sms'" class="mt-2 space-y-1 text-sm">
                            <p>LabsMobile · {{ smsConfiguration.configured ? 'Configuración técnica completa' : 'Configuración técnica incompleta' }}</p>
                            <p>{{ smsConfiguration.sending_enabled ? 'Barrera SMS habilitada' : 'Los envíos SMS están desactivados en este entorno.' }}</p>
                            <p>Seguimiento de entrega pendiente. La conexión técnica no autoriza envíos.</p>
                        </div>
                        <p class="mt-2 text-xs text-surface-500">Plantillas: sí · Adjuntos: no · Respuestas: no · Entrega: {{ capabilities[item.key]?.delivery_receipts ? 'con webhook' : 'sin confirmación' }} · Lectura: {{ capabilities[item.key]?.read_receipts ? 'si el proveedor confirma' : 'sin confirmación' }}</p>
                    </div>
                </div>
                <form v-if="canManage" class="grid gap-4 rounded-lg border border-surface-200 p-4 dark:border-surface-700 md:grid-cols-2" @submit.prevent="saveChannel">
                    <h2 class="md:col-span-2 text-lg font-semibold">Configurar canal</h2>
                    <label class="grid gap-1">Canal<select v-model="selectedChannel" class="rounded border p-2 dark:bg-surface-900" @change="loadChannel"><option value="email">Correo SMTP</option><option value="sms">SMS LabsMobile</option><option value="whatsapp">WhatsApp Cloud API</option></select></label>
                    <label v-if="selectedChannel !== 'sms'" class="grid gap-1">{{ selectedChannel === 'email' ? 'Correo remitente' : 'ID del número de Meta' }}<input v-model="channelForm.sender" class="rounded border p-2 dark:bg-surface-900" required /><small>{{ channelForm.errors.sender }}</small></label>
                    <label class="grid gap-1">Elegibilidad<select v-model="channelForm.eligibility" class="rounded border p-2 dark:bg-surface-900"><option value="unverified">Sin verificar</option><option value="testing">Pruebas</option><option value="approved">Aprobada para este uso</option><option value="rejected">Rechazada</option></select></label>
                    <label class="grid gap-1">Referencia de aprobación<input v-model="channelForm.eligibility_reference" class="rounded border p-2 dark:bg-surface-900" placeholder="Folio o referencia interna" /><small>{{ channelForm.errors.eligibility_reference }}</small></label>
                    <label v-if="selectedChannel === 'whatsapp'" class="grid gap-1 md:col-span-2">Credenciales Meta en JSON (token, api_version, waba_id, phone_number_id, app_secret, verify_token)<textarea v-model="channelForm.secret" class="rounded border p-2 dark:bg-surface-900" rows="2" autocomplete="off" :placeholder="selectedChannelData?.has_secret ? 'Credencial guardada; deja vacío para conservarla' : 'Credencial nueva'" /><small>{{ channelForm.errors.secret }}</small></label>
                    <div v-else-if="selectedChannel === 'sms'" class="text-sm md:col-span-2" data-testid="sms-environment">
                        <p>Las credenciales y el remitente SMS se configuran en el entorno.</p>
                        <p>Conexión: {{ smsConfiguration.credentials ? 'configurada' : 'incompleta' }} · Remitente: {{ smsConfiguration.sender ? 'configurado' : 'pendiente' }}</p>
                        <p>Máximo dos segmentos por aviso después de sustituir las variables. No se recorta el texto.</p>
                    </div>
                    <p v-else class="text-sm md:col-span-2">El servidor SMTP se configura en el entorno con MAIL_*. El correo remitente debe tener dominio autenticado y aprobación para este uso.</p>
                    <label class="flex items-center gap-2"><input v-model="channelForm.enabled" type="checkbox" /> Activar canal para este equipo</label>
                    <button type="submit" class="rounded bg-primary-600 px-4 py-2 text-white disabled:opacity-50" :disabled="channelForm.processing">Guardar canal</button>
                    <p v-if="Object.keys(channelForm.errors).length" class="text-red-600 md:col-span-2" role="alert">{{ Object.values(channelForm.errors).join(" ") }}</p>
                </form>
                <form v-if="canTest && selectedChannel === 'email'" class="flex flex-wrap items-end gap-3 rounded-lg border border-surface-200 p-4 dark:border-surface-700" @submit.prevent="testForm.post(route('comunicaciones.test'))">
                    <label class="grid gap-1">Destinatario de prueba autorizado<input v-model="testForm.recipient" type="email" class="rounded border p-2 dark:bg-surface-900" required /></label>
                    <button type="submit" class="rounded border border-primary-600 px-4 py-2" :disabled="testForm.processing">Enviar prueba técnica</button>
                    <p class="w-full text-sm">La aceptación por SMTP no demuestra entrega al buzón. La prueba requiere transporte real y el destinatario configurado en el entorno.</p>
                    <p v-if="testForm.errors.recipient" class="text-red-600" role="alert">{{ testForm.errors.recipient }}</p>
                </form>
                <div v-if="canTest && selectedChannel !== 'email'" class="rounded-lg border border-surface-200 p-4 dark:border-surface-700">
                    <button type="button" class="rounded border border-primary-600 px-4 py-2 disabled:opacity-50" :disabled="checkForm.processing || !selectedChannelData?.has_secret || (selectedChannel === 'sms' && selectedChannelData?.provider !== 'labsmobile')" @click="checkConnection">Comprobar conexión técnica</button>
                    <p class="mt-2 text-sm">Consulta la API sin enviar mensajes. Una respuesta válida no confirma que el proveedor permita avisos financieros ni que un destinatario los reciba.</p>
                    <p v-if="checkForm.errors.channel" class="text-red-600" role="alert">{{ checkForm.errors.channel }}</p>
                </div>
            </section>

            <Link v-if="canReadNotifications" :href="route('notificaciones.index')" class="inline-block rounded border p-3 text-primary">Notificaciones · catálogo global de SMS y correo</Link>
            <section v-if="tab === 'rules'" class="space-y-4">
                <p>Eventos iniciales: pago recibido y vencimiento próximo. Configura la anticipación por canal. El límite diario se comparte entre canales.</p>
                <div v-if="rules.length" class="overflow-x-auto rounded-lg border border-surface-200 dark:border-surface-700">
                    <table class="w-full min-w-[620px] text-left text-sm">
                        <thead class="bg-surface-100 dark:bg-surface-800"><tr><th class="p-3">Evento</th><th class="p-3">Canal</th><th class="p-3">Plantilla</th><th class="p-3">Anticipación</th><th class="p-3">Horario</th><th class="p-3">Límite diario</th><th class="p-3">Estado</th></tr></thead>
                        <tbody><tr v-for="item in rules" :key="`${item.event_type}-${item.channel}`" class="border-t border-surface-200 dark:border-surface-700"><td class="p-3">{{ item.event_type === 'upcoming_due' ? 'Próximo vencimiento' : 'Pago recibido' }}</td><td class="p-3">{{ item.channel }}</td><td class="p-3"><template v-if="item.channel !== 'whatsapp'">{{ item.notification_template?.name || "Falta seleccionar plantilla global" }}<span class="block text-xs">{{ item.notification_template?.active_version ? "Activa · V" + item.notification_template.active_version.number : "Sin versión activa" }}</span></template><template v-else>Plantilla Meta</template></td><td class="p-3">{{ item.event_type === 'upcoming_due' ? `${item.lead_days} días antes` : 'Al registrar el pago' }}</td><td class="p-3">{{ item.window_start?.slice(0, 5) }}–{{ item.window_end?.slice(0, 5) }} · {{ item.timezone }}</td><td class="p-3">{{ item.daily_limit }}</td><td class="p-3">{{ item.enabled ? 'Activa' : 'Desactivada' }}</td></tr></tbody>
                    </table>
                </div>
                <p v-else>No hay reglas configuradas para este equipo.</p>
                <form v-if="canManage" class="grid gap-4 rounded-lg border border-surface-200 p-4 dark:border-surface-700 md:grid-cols-2" @submit.prevent="saveRule">
                    <label class="grid gap-1">Evento<select v-model="selectedEvent" class="rounded border p-2 dark:bg-surface-900" @change="loadRule"><option value="payment_received">Pago recibido</option><option value="upcoming_due">Próximo vencimiento</option></select></label>
                    <label class="grid gap-1">Canal<select v-model="selectedRuleChannel" class="rounded border p-2 dark:bg-surface-900" @change="loadRule"><option value="email">Correo</option><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select></label>
                    <label v-if="selectedEvent === 'upcoming_due'" class="grid gap-1">Días antes del vencimiento<input v-model.number="ruleForm.lead_days" type="number" min="1" :max="ruleDefaults.maxLeadDays" class="rounded border p-2 dark:bg-surface-900" required /><small>{{ ruleForm.errors.lead_days }}</small></label>
                    <label class="grid gap-1">Zona horaria<input v-model="ruleForm.timezone" class="rounded border p-2 dark:bg-surface-900" required /></label>
                    <label class="grid gap-1">Límite diario conjunto<input v-model.number="ruleForm.daily_limit" type="number" min="1" :max="ruleDefaults.maxDailyLimit" class="rounded border p-2 dark:bg-surface-900" /></label>
                    <label class="grid gap-1">Desde<input v-model="ruleForm.window_start" type="time" class="rounded border p-2 dark:bg-surface-900" /></label>
                    <label class="grid gap-1">Hasta<input v-model="ruleForm.window_end" type="time" class="rounded border p-2 dark:bg-surface-900" /></label>
                    <label v-if="selectedRuleChannel !== 'whatsapp'" class="grid gap-1 md:col-span-2">Plantilla global<select v-model="ruleForm.notification_template_id" class="rounded border p-2 dark:bg-surface-900" :required="ruleForm.enabled"><option :value="null">Seleccione una plantilla activa</option><option v-if="selectedRuleData?.notification_template_id && !compatibleTemplates.some(item => item.id === selectedRuleData.notification_template_id)" :value="selectedRuleData.notification_template_id">{{ selectedRuleData.notification_template?.name || "Plantilla seleccionada" }} · Sin versión activa</option><option v-for="item in compatibleTemplates" :key="item.id" :value="item.id">{{ item.name }} · V{{ item.active_version.number }}</option></select><small v-if="!compatibleTemplates.length">Para activar la regla se necesita una plantilla global activa para este evento y canal. Puedes desactivarla sin elegir otra.</small><Link v-if="canReadNotifications" :href="route('notificaciones.index')" class="text-primary underline">Consultar Notificaciones</Link></label>
                    <label v-if="selectedRuleChannel === 'whatsapp'" class="grid gap-1 md:col-span-2">Texto local<textarea v-model="ruleForm.body" rows="4" class="rounded border p-2 dark:bg-surface-900" required /></label>
                    <div v-if="selectedRuleChannel === 'whatsapp'" class="grid gap-3 md:col-span-2 md:grid-cols-3"><label class="grid gap-1">Nombre en Meta<input v-model="ruleForm.external_template_name" class="rounded border p-2 dark:bg-surface-900" /></label><label class="grid gap-1">Idioma<input v-model="ruleForm.external_template_locale" class="rounded border p-2 dark:bg-surface-900" /></label><label class="grid gap-1">Estado externo<select v-model="ruleForm.external_template_status" class="rounded border p-2 dark:bg-surface-900"><option value="draft">Borrador</option><option value="submitted">En revisión</option><option value="approved">Aprobada</option><option value="rejected">Rechazada</option></select></label><p class="text-sm md:col-span-3">Editar el texto local no cambia la plantilla aprobada en Meta; verifica la versión antes de activarla.</p></div>
                    <div v-if="selectedRuleChannel === 'whatsapp'" class="md:col-span-2 rounded bg-surface-100 p-3 dark:bg-surface-800"><strong>Vista previa</strong><p class="whitespace-pre-wrap">{{ preview }}</p><p class="text-xs">Variables permitidas: {nombre}, {fecha_vencimiento}.</p></div>
                    <label class="flex items-center gap-2"><input v-model="ruleForm.enabled" type="checkbox" /> Activar regla</label>
                    <button type="submit" class="rounded bg-primary-600 px-4 py-2 text-white disabled:opacity-50" :disabled="ruleForm.processing">Guardar regla</button>
                    <p v-if="Object.keys(ruleForm.errors).length" class="text-red-600 md:col-span-2" role="alert">{{ Object.values(ruleForm.errors).join(" ") }}</p>
                </form>
            </section>

            <section v-if="tab === 'consents'" class="space-y-4">
                <p>Registra evidencia de autorización por cliente, prestamista, canal y finalidad. Tener un dato de contacto no equivale a consentimiento.</p>
                <form v-if="canManage" class="grid gap-3 rounded-lg border border-surface-200 p-4 dark:border-surface-700 md:grid-cols-2" @submit.prevent="saveConsent">
                    <label class="grid gap-1">ID del cliente en la sucursal actual<input v-model="consentForm.cliente_id" type="number" min="1" class="rounded border p-2 dark:bg-surface-900" required /></label>
                    <label class="grid gap-1">Canal<select v-model="consentForm.channel" class="rounded border p-2 dark:bg-surface-900"><option value="email">Correo</option><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select></label>
                    <label class="grid gap-1">Finalidad<select v-model="consentForm.purpose" class="rounded border p-2 dark:bg-surface-900"><option value="operational">Operación / pago recibido</option><option value="preventive">Vencimiento preventivo</option></select></label>
                    <label class="grid gap-1">Origen<input v-model="consentForm.source" class="rounded border p-2 dark:bg-surface-900" required /></label>
                    <label class="grid gap-1 md:col-span-2">Referencia a evidencia protegida<input v-model="consentForm.evidence_reference" class="rounded border p-2 dark:bg-surface-900" required /></label>
                    <button type="submit" class="rounded bg-primary-600 px-4 py-2 text-white disabled:opacity-50" :disabled="consentForm.processing">Registrar consentimiento</button>
                    <p v-if="Object.keys(consentForm.errors).length" class="text-red-600 md:col-span-2" role="alert">{{ Object.values(consentForm.errors).join(" ") }}</p>
                </form>
                <div class="overflow-x-auto rounded-lg border border-surface-200 dark:border-surface-700"><table class="w-full min-w-[600px] text-left text-sm"><thead><tr class="bg-surface-100 dark:bg-surface-800"><th class="p-3">Cliente</th><th class="p-3">Canal</th><th class="p-3">Finalidad</th><th class="p-3">Fecha</th><th class="p-3">Estado</th><th class="p-3">Acción</th></tr></thead><tbody><tr v-for="item in consents" :key="item.id" class="border-t border-surface-200 dark:border-surface-700"><td class="p-3">{{ item.cliente_id }}</td><td class="p-3">{{ item.channel }}</td><td class="p-3">{{ item.purpose }}</td><td class="p-3">{{ new Date(item.granted_at).toLocaleString('es-MX') }}</td><td class="p-3">{{ item.revoked_at ? 'Revocado' : 'Vigente' }}</td><td class="p-3"><button v-if="!item.revoked_at && canManage" type="button" class="text-red-600 underline" @click="revokeConsent(item)">Revocar</button></td></tr><tr v-if="!consents.length"><td colspan="6" class="p-5 text-center">Aún no hay consentimientos en esta sucursal.</td></tr></tbody></table></div>
            </section>

            <section v-if="tab === 'history'" class="space-y-3">
                <p>El estado “Aceptado” significa que el proveedor recibió la solicitud. La entrega y lectura requieren confirmación adicional.</p>
                <form class="grid gap-2 rounded-lg border border-surface-200 p-3 dark:border-surface-700 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="filterHistory">
                    <label class="grid gap-1">Canal<select v-model="historyFilters.channel" class="rounded border p-2 dark:bg-surface-900"><option value="">Todos</option><option value="email">Correo</option><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select></label>
                    <label class="grid gap-1">Evento<select v-model="historyFilters.event_type" class="rounded border p-2 dark:bg-surface-900"><option value="">Todos</option><option value="payment_received">Pago recibido</option><option value="upcoming_due">Próximo vencimiento</option></select></label>
                    <label class="grid gap-1">Estado<select v-model="historyFilters.status" class="rounded border p-2 dark:bg-surface-900"><option value="">Todos</option><option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option></select></label>
                    <label class="grid gap-1">Cliente<input v-model="historyFilters.cliente_id" type="number" min="1" class="rounded border p-2 dark:bg-surface-900" /></label>
                    <label class="grid gap-1">Crédito<input v-model="historyFilters.credito_id" type="number" min="1" class="rounded border p-2 dark:bg-surface-900" /></label>
                    <label class="grid gap-1">Desde<input v-model="historyFilters.from" type="date" class="rounded border p-2 dark:bg-surface-900" /></label>
                    <label class="grid gap-1">Hasta<input v-model="historyFilters.to" type="date" class="rounded border p-2 dark:bg-surface-900" /></label>
                    <button type="submit" class="self-end rounded bg-primary-600 px-4 py-2 text-white">Filtrar</button>
                </form>
                <div class="overflow-x-auto rounded-lg border border-surface-200 dark:border-surface-700"><table class="w-full min-w-[700px] text-left text-sm"><thead><tr class="bg-surface-100 dark:bg-surface-800"><th class="p-3">Fecha</th><th class="p-3">Cliente / crédito</th><th class="p-3">Evento / canal</th><th class="p-3">Estado</th><th class="p-3">Detalle</th></tr></thead><tbody><template v-for="item in deliveries.data" :key="item.id"><tr class="border-t border-surface-200 dark:border-surface-700"><td class="p-3">{{ new Date(item.created_at).toLocaleString('es-MX') }}</td><td class="p-3">{{ item.cliente_id }} / {{ item.credito_id }}</td><td class="p-3">{{ item.event_type === 'payment_received' ? 'Pago recibido' : 'Próximo vencimiento' }} · {{ item.channel }}</td><td class="p-3">{{ statuses[item.status] || item.status }}</td><td class="p-3"><button type="button" class="text-primary-600 underline" :aria-expanded="expanded === item.id" @click="expanded = expanded === item.id ? null : item.id">{{ expanded === item.id ? 'Cerrar' : 'Ver intento' }}</button></td></tr><tr v-if="expanded === item.id"><td colspan="5" class="bg-surface-100 p-4 dark:bg-surface-800"><dl class="grid gap-2 sm:grid-cols-2"><div><dt class="font-semibold">Motivo</dt><dd>{{ reasons[item.reason] || item.reason || '—' }}</dd></div><div><dt class="font-semibold">Versión de plantilla</dt><dd v-if="item.notification_version">V{{ item.notification_version.number }}<Link v-if="canReadNotifications" :href="route('notificaciones.index', { plantilla_id: item.notification_version.notification_template_id, version_id: item.notification_version.id })" class="ml-2 text-primary underline">Consultar versión exacta</Link></dd><dd v-else>{{ item.channel === 'whatsapp' ? item.template_version : 'Sin referencia global anterior a I3' }}</dd></div><div><dt class="font-semibold">Intentado</dt><dd>{{ item.attempted_at ? new Date(item.attempted_at).toLocaleString('es-MX') : '—' }}</dd></div><div><dt class="font-semibold">Aceptado</dt><dd>{{ item.accepted_at ? new Date(item.accepted_at).toLocaleString('es-MX') : '—' }}</dd></div><div><dt class="font-semibold">Entregado</dt><dd>{{ item.delivered_at ? new Date(item.delivered_at).toLocaleString('es-MX') : '—' }}</dd></div><div><dt class="font-semibold">Leído</dt><dd>{{ item.read_at ? new Date(item.read_at).toLocaleString('es-MX') : '—' }}</dd></div><div v-if="item.provider"><dt class="font-semibold">Proveedor</dt><dd>{{ item.provider }}</dd></div><div v-if="item.provider_correlation_id"><dt class="font-semibold">Correlación</dt><dd class="break-all">{{ item.provider_correlation_id }}</dd></div><div class="sm:col-span-2"><dt class="font-semibold">ID del proveedor</dt><dd class="break-all">{{ item.provider_message_id || '—' }}</dd></div></dl></td></tr></template><tr v-if="!deliveries.data.length"><td colspan="5" class="p-5 text-center">Aún no hay envíos en este contexto.</td></tr></tbody></table></div>
                <nav class="flex justify-between" aria-label="Páginas del historial"><Link v-if="deliveries.prev_page_url" :href="deliveries.prev_page_url">Anterior</Link><span v-else /><Link v-if="deliveries.next_page_url" :href="deliveries.next_page_url">Siguiente</Link></nav>
            </section>
        </main>
    </AppLayout>
</template>
