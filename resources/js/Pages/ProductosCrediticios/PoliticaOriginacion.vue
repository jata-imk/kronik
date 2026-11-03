<script setup>
import PageHeader from "@/Components/PageHeader.vue";
import FieldHelp from "@/Components/FieldHelp.vue";
import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { computed, ref, watch } from "vue";
import { formatMoneyWithCents as money } from "./productValidation";
import "../../../css/originacion.css";
const props = defineProps({ version: Object, politica: Object, politicaSeleccionada: Object, historial: Object, tiposDocumento: Array, puedeCrear: Boolean, modoPolitica: String });
const page = usePage();
const puedeCrearAhora = computed(() => props.puedeCrear && (page.props.auth.is_super_admin || (page.props.auth.permissions?.["manage-origination-productos-crediticios"] === true && page.props.auth.permissions?.["read-productos-crediticios"] === true)));
const editando = ref(props.modoPolitica === "nueva" && puedeCrearAhora.value);
const guiaVisible = ref(false);
const readonly = computed(() => !editando.value || !puedeCrearAhora.value);
const datos = (politica, nueva = false) => {
    const c = politica?.condiciones ?? {};
    return { version_anterior: props.politica?.numero ?? 0, modalidad: c.modalidad ?? null, sic: c.sic ?? null,
        sic_apis: [...(c.sic_apis ?? [])], sic_sin_score: c.sic_sin_score ?? null,
        monto_maximo: c.monto_maximo == null ? null : Number(c.monto_maximo), vigencia_dias: c.vigencia_dias ?? "",
        documentos: [...(c.documentos ?? [])], referencia_validacion: nueva ? "" : (c.referencia_validacion ?? ""),
        criterio_capacidad: c.criterio_capacidad ?? "", confirmacion: false };
};
const form = useForm(datos(editando.value ? props.politica : props.politicaSeleccionada, editando.value));
function nueva() {
    if (!puedeCrearAhora.value) return;
    form.cancel(); form.defaults(datos(props.politica, true)); form.reset(); form.clearErrors(); editando.value = true;
}
function consultar() {
    form.cancel(); editando.value = false; form.defaults(datos(props.politicaSeleccionada)); form.reset(); form.clearErrors();
}
watch([() => props.version.id, () => props.politicaSeleccionada?.id, () => puedeCrearAhora.value, () => props.modoPolitica], () => {
    consultar();
    if (props.modoPolitica === "nueva" && puedeCrearAhora.value) nueva();
});
function guardar() {
    if (readonly.value) return;
    form.transform(data => ({ ...data, monto_maximo: data.monto_maximo == null ? null : Number(data.monto_maximo).toFixed(2) }))
        .post(route("originacion-politicas.store", props.version.id), { preserveScroll: true, onSuccess: consultar });
}
function seleccionar(id) {
    form.cancel(); editando.value = false;
    router.get(route("originacion-politicas.show", props.version.id), { politica_id: id, modo: "consulta", page: props.historial.current_page }, { preserveScroll: true });
}
</script>
<template>
    <AppLayout title="Política de originación">
        <template #card-header><PageHeader eyebrow="Configuración · Reglas de decisión" title="Política de originación" :description="version.producto.nombre + ' · versión de producto ' + version.numero"><template #actions><Link :href="route('productos-crediticios.index', {producto_id:version.producto.id, version_id:version.id})" class="text-primary underline">Volver a productos</Link></template></PageHeader></template>
        <template #card-content>
            <div class="originacion-content space-y-5 p-4 md:p-6">
                <p>Las condiciones contractuales del producto se congelan al activar o programar. Esta política define criterios internos de aprobación y tiene versionado independiente: cambiarla exige devolver y reenviar las solicitudes anteriores.</p>
                <Message severity="warn" :closable="false">Guardar configura criterios internos, no acredita validación jurídica ni habilita operaciones reales. El producto debe estar activo y vigente para seleccionarlo; la política permite comprobar los requisitos de aprobación.</Message>
                <div class="flex flex-wrap items-center gap-3"><p>Política actual: {{ politica ? 'versión ' + politica.numero : 'Sin configurar; aprobación bloqueada' }}.</p><Button type="button" label="Guía de originación" icon="pi pi-book" outlined @click="guiaVisible = true" /><Button v-if="puedeCrearAhora && !editando" type="button" :label="politica ? 'Nueva versión de política' : 'Crear política'" @click="nueva" /></div>
                <Message v-if="version.estado === 'retirada'" severity="info" :closable="false">Versión de producto retirada. Su política e historia se consultan; no se crean políticas nuevas.</Message>
                <Message severity="info" :closable="false">{{ readonly ? ('Consulta · política ' + (politicaSeleccionada?.numero ?? 'sin configurar') + ' · versión de producto ' + version.numero) : ('Nueva política a partir de la actual ' + (politica?.numero ?? 0)) }}.</Message>
                <form class="space-y-4" @submit.prevent="guardar">
                    <div class="flex flex-col gap-1"><label for="pol-modalidad">Separación de funciones</label><Select input-id="pol-modalidad" aria-label="Separación de funciones" v-model="form.modalidad" :disabled="readonly" :options="[{label:'Dual: quien capturó no aprueba',value:'dual'},{label:'Individual: misma persona, decisión auditada',value:'individual'}]" option-label="label" option-value="value" /><small>Individual admite la misma persona con permiso. Dual exige un aprobador distinto de quien capturó, modificó o envió, incluso con Super Admin.</small></div>
                    <div class="flex flex-col gap-1"><label for="pol-sic">Requisito SIC</label><Select input-id="pol-sic" aria-label="Requisito SIC" v-model="form.sic" :disabled="readonly" :options="[{label:'SIC integrado requerido (sandbox para créditos QA)',value:'requerido'},{label:'Evaluación manual documentada permitida',value:'manual_permitido'}]" option-label="label" option-value="value" /><small>Selecciona la evidencia admitida por la institución. La evaluación manual debe quedar documentada; no sustituye un SIC obligatorio.</small></div>
                    <fieldset v-if="form.sic === 'requerido'" class="k-surface space-y-3 p-4"><legend>Servicios SIC aceptados</legend><label v-for="s in [{clave:'fico_score_v2',nombre:'FICO Score v2'},{clave:'fintech',nombre:'Fintech Score'},{clave:'rc_fico_score',nombre:'Reporte de Crédito + FICO'}]" :key="s.clave" class="flex items-center gap-3"><Checkbox v-model="form.sic_apis" :disabled="readonly" :value="s.clave" /><span>{{ s.nombre }}</span></label><small>La consulta de la revisión debe corresponder a uno de estos servicios permitidos.</small><label for="pol-sin-score" class="block">Resultado sin score utilizable</label><Select input-id="pol-sin-score" v-model="form.sic_sin_score" :disabled="readonly" :options="[{label:'Bloquear aprobación',value:'bloquear'},{label:'Permitir evaluación manual documentada',value:'evaluacion_manual'}]" option-label="label" option-value="value" fluid /><p class="text-sm text-muted-color">Define qué hacer cuando no existe un score utilizable, sin inventar un umbral. Evaluación y PLD siguen siendo obligatorias.</p></fieldset>
                    <div class="flex flex-col gap-1"><label for="pol-maximo">Límite de aprobación (MXN)</label><InputNumber input-id="pol-maximo" v-model="form.monto_maximo" :disabled="readonly" mode="currency" currency="MXN" locale="es-MX" :min-fraction-digits="2" :max-fraction-digits="2" fluid required /><small>Importe máximo aprobable con esta política, dentro del rango del producto: {{ money(version.monto_minimo) }} a {{ money(version.monto_maximo) }} MXN.</small></div>
                    <div class="flex flex-col gap-1"><label for="pol-vigencia">Vigencia de aprobación en días</label><InputText id="pol-vigencia" v-model="form.vigencia_dias" :readonly="readonly" inputmode="numeric" required /><small>Entre 1 y 365; cuenta la fecha de aprobación como primer día. No es un plazo legal predeterminado ni la fecha de activación del producto.</small></div>
                    <fieldset><legend class="mb-2">Documentos requeridos por esta política</legend><div v-for="tipo in tiposDocumento" :key="tipo.value" class="mb-2 flex items-center gap-2"><Checkbox :input-id="'pol-doc-' + tipo.value" v-model="form.documentos" :disabled="readonly" :value="tipo.value" /><label :for="'pol-doc-' + tipo.value">{{ tipo.label }}</label></div><small>Selecciona de uno a cinco tipos del expediente que la institución exige para aprobar. No constituye un checklist legal universal.</small></fieldset>
                    <div class="flex flex-col gap-1"><label for="pol-capacidad">Criterio de capacidad de pago para evaluación manual</label><Textarea id="pol-capacidad" v-model="form.criterio_capacidad" :readonly="readonly" required minlength="20" maxlength="1000" rows="3" /><small>Describe qué evidencias revisar y cómo fundamentar la decisión, mínimo 20 caracteres. El sistema no calcula un umbral.</small><FieldHelp title="Criterio de capacidad" description="Referencia el procedimiento interno y los documentos que revisará el analista." example="Revisar ingresos y egresos documentados y fundamentar la decisión conforme al procedimiento interno vigente." /></div>
                    <div class="flex flex-col gap-1"><label for="pol-validacion">Referencia de validación del operador</label><Textarea id="pol-validacion" v-model="form.referencia_validacion" :readonly="readonly" required minlength="20" maxlength="1000" rows="3" /><small>Identifica decisión interna, responsable y respaldo, mínimo 20 caracteres. No incluyas secretos ni afirmes validación jurídica sin evidencia.</small><FieldHelp title="Referencia de validación" description="Identifica el documento interno que autoriza estos criterios." example="Acta interna identificada por folio y fecha, con responsable y versión del procedimiento." /></div>
                    <div v-if="!readonly" class="flex items-center gap-2"><Checkbox input-id="pol-confirmacion" v-model="form.confirmacion" binary /><label for="pol-confirmacion">Confirmo esta nueva versión; las solicitudes anteriores deben devolverse y reenviarse antes de aprobar.</label></div>
                    <Message v-for="(error,key) in form.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
                    <div v-if="!readonly" class="flex flex-wrap gap-3"><Button type="button" label="Cancelar nueva política" severity="secondary" @click="consultar" /><Button type="submit" label="Guardar nueva versión de política" :loading="form.processing" /></div>
                </form>
                <section><h2 class="text-lg font-semibold">Historia de políticas</h2><div v-for="item in historial.data" :key="item.id" class="mt-3 flex flex-wrap gap-3"><Button type="button" :label="'Consultar política ' + item.numero" outlined @click="seleccionar(item.id)" /><span>{{ new Date(item.created_at).toLocaleString('es-MX') }}</span></div><Paginator :first="(historial.current_page-1)*historial.per_page" :rows="historial.per_page" :total-records="historial.total" @page="event => router.get(route('originacion-politicas.show',version.id),{page:event.page+1,politica_id:politicaSeleccionada?.id,modo:'consulta'},{preserveScroll:true})" /></section>
            </div>
            <Drawer v-model:visible="guiaVisible" header="Guía de la política de originación" position="right" :style="{width:'min(38rem,96vw)'}"><div class="space-y-4"><p>La versión del producto conserva condiciones contractuales. La política define cómo se evalúa y aprueba una solicitud y conserva su propio número.</p><p>Borrador permite configurar. Activar o programar congela condiciones; la vigencia empresarial determina cuándo se ofrece el producto. Una política ausente no impide capturar ni enviar, pero no permite aprobar.</p><p>Una política nueva conserva la anterior. Las solicitudes que usaron otra política deben devolverse y reenviarse; luego se renuevan los dictámenes correspondientes.</p><p>Los valores los decide la institución conforme a su procedimiento. Esta ayuda explica los campos y no recomienda límites financieros ni acredita cumplimiento legal.</p></div></Drawer>
        </template>
    </AppLayout>
</template>
