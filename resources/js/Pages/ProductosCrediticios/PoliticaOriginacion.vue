<script setup>
import { Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
const props = defineProps({ version: Object, politica: Object, historial: Object, tiposDocumento: Array });
const anteriores = props.politica?.condiciones ?? {};
const form = useForm({ version_anterior: props.politica?.numero ?? 0, modalidad: anteriores.modalidad ?? null, sic: anteriores.sic ?? null, monto_maximo: anteriores.monto_maximo ?? "", vigencia_dias: anteriores.vigencia_dias ?? "", documentos: anteriores.documentos ?? [], referencia_validacion: "", criterio_capacidad: anteriores.criterio_capacidad ?? "", confirmacion: false });
function guardar() {
    form.post(route("originacion-politicas.store", props.version.id), { preserveScroll: true, onSuccess: () => { form.version_anterior = props.politica.numero; form.confirmacion = false; form.referencia_validacion = ""; } });
}
</script>
<template>
    <AppLayout title="Política de originación">
        <template #card-header><div class="space-y-2 p-6"><h1 class="text-2xl font-semibold">Política de originación</h1><p>{{ version.producto.nombre }} · versión de producto {{ version.numero }}</p><Link :href="route('productos-crediticios.index')" class="text-primary underline">Volver a productos</Link></div></template>
        <template #card-content>
            <div class="space-y-5 p-6">
                <Message severity="warn" :closable="false">Guardar configura criterios internos, no acredita validación jurídica ni habilita operaciones reales. Cada cambio conserva historia y exige reenviar las solicitudes anteriores antes de aprobar.</Message>
                <p>Política actual: {{ politica ? `versión ${politica.numero}` : 'Sin configurar; aprobación bloqueada' }}.</p>
                <form class="space-y-4" @submit.prevent="guardar">
                    <div class="flex flex-col gap-1"><label for="pol-modalidad">Separación de funciones</label><Select input-id="pol-modalidad" aria-label="Separación de funciones" v-model="form.modalidad" :options="[{label:'Dual: quien capturó no aprueba',value:'dual'},{label:'Individual: misma persona, decisión auditada',value:'individual'}]" option-label="label" option-value="value" /></div>
                    <div class="flex flex-col gap-1"><label for="pol-sic">Requisito SIC</label><Select input-id="pol-sic" aria-label="Requisito SIC" v-model="form.sic" :options="[{label:'SIC integrado requerido (bloqueado hasta integración productiva)',value:'requerido'},{label:'Evaluación manual documentada permitida',value:'manual_permitido'}]" option-label="label" option-value="value" /></div>
                    <div class="flex flex-col gap-1"><label for="pol-maximo">Límite de aprobación (MXN)</label><InputText id="pol-maximo" v-model="form.monto_maximo" inputmode="decimal" required /><small>Dentro del rango del producto: {{ version.monto_minimo }} a {{ version.monto_maximo }}.</small></div>
                    <div class="flex flex-col gap-1"><label for="pol-vigencia">Vigencia de aprobación en días</label><InputText id="pol-vigencia" v-model="form.vigencia_dias" inputmode="numeric" required /><small>Entre 1 y 365; cuenta la fecha de aprobación como primer día. No es un plazo legal predeterminado.</small></div>
                    <fieldset><legend class="mb-2">Documentos requeridos por esta política</legend><div v-for="tipo in tiposDocumento" :key="tipo.value" class="mb-2 flex items-center gap-2"><Checkbox :input-id="`pol-doc-${tipo.value}`" v-model="form.documentos" :value="tipo.value" /><label :for="`pol-doc-${tipo.value}`">{{ tipo.label }}</label></div></fieldset>
                    <div class="flex flex-col gap-1"><label for="pol-capacidad">Criterio de capacidad de pago para evaluación manual</label><Textarea id="pol-capacidad" v-model="form.criterio_capacidad" required minlength="20" maxlength="1000" rows="3" /><small>Debe indicar qué verificar y cómo fundamentarlo. El sistema no calcula ni inventa un umbral de capacidad.</small></div>
                    <div class="flex flex-col gap-1"><label for="pol-validacion">Referencia de validación del operador</label><Textarea id="pol-validacion" v-model="form.referencia_validacion" required minlength="20" maxlength="1000" rows="3" /><small>Identifica la decisión interna, responsable y validación aplicable. Visible en solicitudes; no incluyas secretos.</small></div>
                    <div class="flex items-center gap-2"><Checkbox input-id="pol-confirmacion" v-model="form.confirmacion" binary /><label for="pol-confirmacion">Confirmo esta nueva versión y su modalidad de aprobación.</label></div>
                    <Message v-for="(error,key) in form.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
                    <Button type="submit" label="Guardar nueva versión de política" :loading="form.processing" />
                </form>
                <section><h2 class="text-lg font-semibold">Historia de políticas</h2><details v-for="item in historial.data" :key="item.id" class="mt-3"><summary>Versión {{ item.numero }} · {{ new Date(item.created_at).toLocaleString('es-MX') }}</summary><p>Modalidad: {{ item.condiciones.modalidad }} · SIC: {{ item.condiciones.sic }}</p><p>Límite: {{ item.condiciones.monto_maximo }} MXN · Vigencia: {{ item.condiciones.vigencia_dias }} días.</p><p class="whitespace-pre-wrap">{{ item.condiciones.criterio_capacidad }}</p><p class="whitespace-pre-wrap">{{ item.condiciones.referencia_validacion }}</p><p>Documentos: {{ item.condiciones.documentos.join(', ') }}</p></details><Paginator :first="(historial.current_page-1)*historial.per_page" :rows="historial.per_page" :total-records="historial.total" @page="event => router.get(route('originacion-politicas.show',version.id),{page:event.page+1},{preserveScroll:true})" /></section>
            </div>
        </template>
    </AppLayout>
</template>
