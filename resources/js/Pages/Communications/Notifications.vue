<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { useGuardedForm as useForm } from "@/Composables/useGuardedForm";
import { confirmFormDiscard } from "@/Utils/unsavedChanges";
import { computed, ref, watch } from "vue";
import DocumentEditor from "@/Components/Documents/DocumentEditor.vue";
import TemplateVariablesPanel from "@/Components/Templates/TemplateVariablesPanel.vue";
import TemplatePreview from "@/Components/Templates/TemplatePreview.vue";
import PlainTextTemplateEditor from "@/Components/Templates/PlainTextTemplateEditor.vue";

const props = defineProps({ templates: Object, selected: Object, versions: Object, version: Object, filters: Object, mode: String, permissions: Object });
const page = usePage();
const creating = ref(false);
const textEditor = ref(null);
const htmlEditor = ref(null);
const subjectInput = ref(null);
const activeField = ref("body");
const subjectSelection = ref({ start: 0, end: 0 });
const search = ref({ buscar: props.filters.buscar ?? "", channel: props.filters.channel ?? "", event_type: props.filters.event_type ?? "" });
const form = useForm({ key: "", name: "", description: "", event_type: "payment_received", channel: "sms", format: "text", subject: "", body: "", change_summary: "" });
const editable = computed(() => !!props.permissions.read && (creating.value ? props.permissions.create : props.mode === "edicion" && props.version?.can_edit && props.permissions.update));
const identity = computed(() => [props.selected?.id, props.version?.id, props.mode, creating.value, editable.value, page.props.contextoOperativo?.team_id, page.props.contextoOperativo?.sucursal_id].join(":"));
const variables = [
    { clave: "nombre", nombre: "Primer nombre", origen: "Cliente titular", formato: "Texto", requerida: true },
    { clave: "fecha_vencimiento", nombre: "Fecha del evento", origen: "Evento original", formato: "dd/mm/aaaa", requerida: true },
];
function load() {
    form.cancel();
    creating.value = false;
    form.defaults({
        key: props.selected?.key ?? "", name: props.selected?.name ?? "", description: props.selected?.description ?? "",
        event_type: props.selected?.event_type ?? "payment_received", channel: props.selected?.channel ?? "sms",
        format: props.version?.format ?? "text", subject: props.version?.subject ?? "", body: props.version?.body ?? "", change_summary: props.version?.change_summary ?? "",
    });
    form.reset(); form.clearErrors();
    activeField.value = "body";
}
let loadedIdentity = null;
function syncFromServer() {
    const current = JSON.stringify([
        props.selected?.id, props.version?.id, props.mode, props.version?.state, props.version?.can_edit,
        ...["read", "create", "update", "version", "activate", "retire"].map(key => !!props.permissions[key]),
        page.props.contextoOperativo?.team_id, page.props.contextoOperativo?.sucursal_id,
    ]);
    if (current !== loadedIdentity) {
        loadedIdentity = current;
        load();
    } else if (!creating.value && !editable.value) {
        load();
    }
}
watch(() => [props.selected, props.version, props.mode, props.permissions, page.props.contextoOperativo], syncFromServer, { immediate: true, deep: true });
function create() {
    if (!props.permissions.read || !props.permissions.create) return;
    if (!confirmFormDiscard(form)) return;
    load();
    form.defaults({ key: "", name: "", description: "", event_type: "payment_received", channel: "sms", format: "text", subject: "", body: "", change_summary: "" });
    form.reset();
    creating.value = true;
}
function visit(templateId, versionId, mode = "consulta") {
    if (!confirmFormDiscard(form)) return;
    form.cancel();
    creating.value = false;
    router.get(route("notificaciones.index"), { ...search.value, plantilla_id: templateId, version_id: versionId, modo: mode }, { preserveScroll: true });
}
function save() {
    if (!editable.value || form.processing) return;
    const options = { preserveScroll: true, onSuccess: () => form.defaults() };
    if (creating.value) form.post(route("notificaciones.store"), options);
    else form.put(route("notificaciones.update", [props.selected.id, props.version.id]), options);
}
function changeChannel() {
    if (!editable.value || !creating.value) return;
    form.format = "text"; form.subject = ""; form.body = ""; activeField.value = "body";
}
function changeFormat() {
    if (!editable.value) return;
    // Formats are separate authoring modes; never silently reinterpret markup as plain text.
    form.body = "";
}
function rememberSubject() {
    subjectSelection.value = { start: subjectInput.value?.selectionStart ?? 0, end: subjectInput.value?.selectionEnd ?? 0 };
}
function insertVariable(variable) {
    if (!editable.value) return;
    if (activeField.value === "subject") {
        const token = "{" + variable.clave + "}";
        const { start, end } = subjectSelection.value;
        form.subject = form.subject.slice(0, start) + token + form.subject.slice(end);
        subjectSelection.value = { start: start + token.length, end: start + token.length };
    } else if (form.format === "html") htmlEditor.value?.insertVariable({ ...variable, token: "{" + variable.clave + "}" });
    else textEditor.value?.insertVariable(variable);
}
function action(kind) {
    if (!props.permissions[kind === "duplicate" ? "version" : kind] || !props.version) return;
    const messages = { duplicate: "¿Crear un borrador desde esta versión exacta?", activate: "¿Activar esta versión? Quedará inmutable y cancelará los pendientes de la versión sustituida.", retire: "¿Retirar esta versión y cancelar sus pendientes sin recrearlos?" };
    if (kind === "duplicate" && !props.permissions.version) return;
    if (!confirmFormDiscard(form)) return;
    if (!window.confirm(messages[kind])) return;
    router.post(route("notificaciones." + kind, kind === "duplicate" ? [props.selected.id, props.version.id] : props.version.id), {}, { preserveScroll: true });
}
const source = computed(() => {
    if (!props.permissions.read || (!creating.value && !props.version)) return null;
    return editable.value
        ? { method: "post", url: route("notificaciones.preview-draft"), data: { ...form.data(), version_id: creating.value ? undefined : props.version.id } }
        : { method: "get", url: route("notificaciones.preview", props.version.id) };
});
const stateLabel = (state) => ({ draft: "Borrador", active: "Activa", retired: "Retirada" })[state] ?? state;
</script>

<template>
    <AppLayout title="Notificaciones">
        <template #card-content>
        <div class="mx-auto max-w-[1600px] space-y-5 p-4 md:p-6">
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div><h1 class="text-2xl font-semibold">Notificaciones</h1><p class="text-sm">Catálogo global de SMS y correo · versiones y contenido institucional.</p></div>
                <div class="flex flex-wrap gap-2">
                    <Link v-if="page.props.auth?.permissions?.['read-comunicaciones']" :href="route('comunicaciones.index')" class="rounded border p-2">Comunicaciones</Link>
                    <button v-if="permissions.create" type="button" class="rounded bg-primary px-3 py-2 text-primary-contrast" @click="create">Nueva plantilla</button>
                </div>
            </header>
            <p v-if="page.props.flash?.success" role="status">{{ page.props.flash.success }}</p>
            <form class="flex flex-wrap gap-3" @submit.prevent="router.get(route('notificaciones.index'), search)">
                <label class="grid gap-1 text-sm">Buscar plantillas<input v-model="search.buscar" type="search" class="rounded border p-2 dark:bg-surface-900" /></label>
                <label class="grid gap-1 text-sm">Canal<select v-model="search.channel" class="rounded border p-2 dark:bg-surface-900"><option value="">Todos</option><option value="sms">SMS</option><option value="email">Correo</option></select></label>
                <label class="grid gap-1 text-sm">Evento<select v-model="search.event_type" class="rounded border p-2 dark:bg-surface-900"><option value="">Todos</option><option value="payment_received">Pago recibido</option><option value="upcoming_due">Próximo vencimiento</option></select></label>
                <button class="self-end rounded border p-2" type="submit">Filtrar</button>
            </form>
            <div class="grid min-w-0 gap-5 lg:grid-cols-[280px_minmax(0,1fr)]">
                <aside class="min-w-0 space-y-4">
                    <ul aria-label="Plantillas globales" class="space-y-2">
                        <li v-for="item in templates.data" :key="item.id"><button type="button" class="w-full rounded-xl border p-3 text-left" :class="selected?.id === item.id && 'border-primary'" @click="visit(item.id)"><strong class="block break-words">{{ item.name }}</strong><span class="text-sm">{{ item.channel === "sms" ? "SMS" : "Correo" }} · {{ item.event_type === "payment_received" ? "Pago recibido" : "Próximo vencimiento" }}</span><span class="block text-xs">{{ item.active_version ? "Activa · V" + item.active_version.number : "Sin versión activa" }}</span></button></li>
                    </ul>
                    <p v-if="!templates.data.length">No hay plantillas para estos filtros.</p>
                    <nav class="flex gap-3" aria-label="Páginas de plantillas"><Link v-if="templates.prev_page_url" :href="templates.prev_page_url">Anterior</Link><span>{{ templates.current_page }} / {{ templates.last_page }}</span><Link v-if="templates.next_page_url" :href="templates.next_page_url">Siguiente</Link></nav>
                    <template v-if="selected">
                        <h2 class="font-semibold">Versiones de {{ selected.name }}</h2>
                        <ul aria-label="Versiones de notificación" class="space-y-2"><li v-for="item in versions.data" :key="item.id"><button type="button" class="w-full rounded border p-2 text-left" @click="visit(selected.id, item.id)">V{{ item.number }} · {{ stateLabel(item.state) }}<span class="block truncate text-xs">{{ item.change_summary }}</span></button></li></ul>
                        <nav class="flex gap-3" aria-label="Páginas de versiones"><Link v-if="versions.prev_page_url" :href="versions.prev_page_url">Anteriores</Link><Link v-if="versions.next_page_url" :href="versions.next_page_url">Siguientes</Link></nav>
                    </template>
                </aside>
                <main v-if="version || creating" class="min-w-0 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-xl font-semibold">{{ creating ? "Nueva plantilla" : (editable ? "Editar" : "Consultar") + " versión " + version.number + " · " + stateLabel(version.state) }}</h2>
                        <div v-if="!creating" class="flex flex-wrap gap-2">
                            <button v-if="version.can_edit && permissions.update" type="button" class="rounded border p-2" @click="visit(selected.id, version.id, editable ? 'consulta' : 'edicion')">{{ editable ? "Consultar versión" : "Editar borrador" }}</button>
                            <button v-if="permissions.version" type="button" class="rounded border p-2" @click="action('duplicate')">Nueva versión</button>
                            <button v-if="version.state === 'draft' && permissions.activate" type="button" class="rounded border p-2" @click="action('activate')">Activar versión</button>
                            <button v-if="version.state === 'active' && permissions.retire" type="button" class="rounded border p-2" @click="action('retire')">Retirar versión</button>
                        </div>
                    </div>
                    <form class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1fr)_260px]" @submit.prevent="save">
                        <div class="min-w-0 space-y-4">
                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="grid gap-1 text-sm">Nombre<input v-model="form.name" :readonly="!editable" class="rounded border p-2 dark:bg-surface-900" required maxlength="160" /></label>
                                <label class="grid gap-1 text-sm">Clave<input v-model="form.key" :readonly="!editable || !creating" class="rounded border p-2 dark:bg-surface-900" required maxlength="80" /></label>
                                <label class="grid gap-1 text-sm">Canal de la plantilla<select v-model="form.channel" :disabled="!editable || !creating" class="rounded border p-2 dark:bg-surface-900" @change="changeChannel"><option value="sms">SMS</option><option value="email">Correo</option></select></label>
                                <label class="grid gap-1 text-sm">Evento de la plantilla<select v-model="form.event_type" :disabled="!editable || !creating" class="rounded border p-2 dark:bg-surface-900"><option value="payment_received">Pago recibido</option><option value="upcoming_due">Próximo vencimiento</option></select></label>
                            </div>
                            <label class="grid gap-1 text-sm">Descripción<textarea v-model="form.description" :readonly="!editable" class="rounded border p-2 dark:bg-surface-900" rows="2" maxlength="500" /></label>
                            <label v-if="form.channel === 'email'" class="grid gap-1 text-sm">Formato<select v-model="form.format" :disabled="!editable" class="rounded border p-2 dark:bg-surface-900" @change="changeFormat"><option value="text">Texto plano</option><option value="html">HTML</option></select><small>Cambiar el formato limpia el cuerpo para redactarlo explícitamente.</small></label>
                            <label v-if="form.channel === 'email'" class="grid gap-1 text-sm">Asunto<input ref="subjectInput" v-model="form.subject" :readonly="!editable" class="rounded border p-2 dark:bg-surface-900" maxlength="160" @focus="activeField = 'subject'" @select="rememberSubject" @keyup="rememberSubject" @blur="rememberSubject" /></label>
                            <div @focusin="activeField = 'body'">
                                <DocumentEditor v-if="form.format === 'html'" :key="identity + ':html'" ref="htmlEditor" v-model="form.body" section="contenido_html" profile="notification" :readonly="!editable" />
                                <PlainTextTemplateEditor v-else :key="identity + ':text'" ref="textEditor" v-model="form.body" :readonly="!editable" />
                            </div>
                            <label class="grid gap-1 text-sm">Resumen de cambios<input v-model="form.change_summary" :readonly="!editable" class="rounded border p-2 dark:bg-surface-900" maxlength="500" /></label>
                            <p v-if="Object.keys(form.errors).length" role="alert" class="text-red-600">{{ Object.values(form.errors).join(" ") }}</p>
                            <button v-if="editable" type="submit" :disabled="form.processing" class="rounded bg-primary px-4 py-2 text-primary-contrast">Guardar borrador</button>
                            <TemplatePreview :key="identity" :source="source" :identity="identity" />
                        </div>
                        <TemplateVariablesPanel :key="identity" :variables="variables" :readonly="!editable" @insert="insertVariable" />
                    </form>
                </main>
                <p v-else class="p-4">Selecciona una plantilla o crea un borrador con el permiso correspondiente.</p>
            </div>
        </div>
        </template>
    </AppLayout>
</template>
