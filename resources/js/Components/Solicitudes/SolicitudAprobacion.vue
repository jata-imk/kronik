<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";
const props = defineProps({ solicitud: Object, estado: Object, puedeAprobar: Boolean });
const form = useForm({ motivo: "", lock_version: props.solicitud.lock_version });
const pendientes = computed(() => props.estado?.requisitos.filter(item => !item.cumplido).length ?? 0);
const cumplidos = computed(() => props.estado?.requisitos.filter(item => item.cumplido) ?? []);
function etiqueta(clave) {
    if (clave === 'persona_fisica') return 'Persona física e identidad fiscal';
    if (clave.endsWith('_vigente') && ['evaluacion_vigente', 'pld_vigente'].includes(clave)) return clave === 'pld_vigente' ? 'PLD sobre el expediente actual' : 'Evaluación sobre el expediente actual';
    if (clave.startsWith('documento_')) return `Documento: ${clave.replace('documento_', '').replaceAll('_', ' ').toUpperCase()}`;
    return etiquetas[clave] ?? clave;
}
function responsable(clave) {
    if (clave === 'habilitacion') return 'Administrador técnico';
    if (clave.startsWith('evaluacion')) return 'Analista de evaluación';
    if (clave.startsWith('pld')) return 'Responsable de cumplimiento';
    if (clave.startsWith('documento_')) return 'Responsable del expediente';
    if (['politica', 'politica_vigente', 'producto', 'sic'].includes(clave)) return 'Administrador de productos / revisor';
    if (['permiso', 'separacion'].includes(clave)) return 'Aprobador / administrador de accesos';
    return 'Responsable de la solicitud';
}
const etiquetas = { habilitacion: "Habilitación operativa", estado: "Estado de solicitud", permiso: "Permiso y sucursal", politica: "Política configurada", politica_vigente: "Política vigente", producto: "Producto disponible", monto: "Límite de monto", fecha: "Fecha estimada", separacion: "Separación de funciones", sic: "Requisito SIC", cliente: "Datos del cliente", evaluacion: "Dictamen de evaluación", evaluacion_vigente: "Vigencia de evaluación", pld: "Dictamen de cumplimiento", pld_vigente: "Vigencia de cumplimiento" };
function aprobar() {
    form.lock_version = props.solicitud.lock_version;
    form.post(route("solicitudes.aprobar", props.solicitud.id), { preserveScroll: true, onSuccess: () => form.reset("motivo") });
}
</script>
<template>
    <section v-if="estado && solicitud.estado === 'en_revision'" class="space-y-3 rounded border border-surface-200 p-4 dark:border-surface-700">
        <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-semibold"><i class="pi pi-list-check mr-2 text-primary" aria-hidden="true" />Requisitos para aprobación</h2><span class="rounded-full bg-surface-100 px-3 py-1 text-sm dark:bg-surface-800">{{ pendientes ? `${pendientes} pendientes` : 'Requisitos completos' }}</span></div>
        <p>Se verifican nuevamente al confirmar. Un requisito cumplido no equivale a una aprobación ni a un desembolso.</p>
        <p v-if="estado.politica">Política {{ estado.politica.numero }} · Modalidad {{ estado.politica.condiciones.modalidad }}.</p>
        <p v-if="estado.politica" class="whitespace-pre-wrap">Capacidad de pago: {{ estado.politica.condiciones.criterio_capacidad }}</p>
        <details class="rounded-xl bg-primary-50 p-4 dark:bg-primary-950/30"><summary class="cursor-pointer font-medium">¿Por qué un requisito cumplido vuelve a pendiente?</summary><p class="mt-2">Los dictámenes evalúan una versión concreta del expediente. Cargar, validar o sustituir documentos cambia esa versión y exige renovar Evaluación y PLD. Por eso conviene completar y validar documentos antes de dictaminar.</p><p class="mt-2">Cambiar identidad, domicilio o política exige devolver y reenviar la solicitud; después se registran nuevos dictámenes. Estas comprobaciones no son un score ni una decisión automática.</p></details>
        <ul class="divide-y divide-surface-200 dark:divide-surface-700"><li v-for="item in estado.requisitos.filter(item => !item.cumplido)" :key="item.clave" class="py-3"><div class="flex items-start justify-between gap-3"><span class="font-medium">{{ etiqueta(item.clave) }}</span><span class="flex shrink-0 items-center gap-2 text-sm text-amber-700 dark:text-amber-400"><i class="pi pi-clock" aria-hidden="true" />Pendiente</span></div><p class="mt-2 text-sm">{{ item.mensaje }}</p><p class="mt-1 text-sm font-medium text-surface-500">Quién lo resuelve: {{ responsable(item.clave) }}</p></li></ul>
        <details v-if="cumplidos.length" class="rounded-xl border border-surface-200 p-3 dark:border-surface-700"><summary class="cursor-pointer font-medium"><i class="pi pi-check-circle mr-2 text-green-600" aria-hidden="true" />{{ cumplidos.length }} requisitos cumplidos · Ver detalle</summary><ul class="mt-3 grid gap-3 md:grid-cols-2"><li v-for="item in cumplidos" :key="item.clave" class="flex items-center gap-2 text-sm"><i class="pi pi-check text-green-600" aria-hidden="true" />{{ etiqueta(item.clave) }}<span class="sr-only">Cumplido</span></li></ul></details>
        <form v-if="puedeAprobar" class="space-y-3" @submit.prevent="aprobar">
            <div class="flex flex-col gap-1"><label for="sol-motivo-aprobacion">Motivo de aprobación</label><Textarea id="sol-motivo-aprobacion" v-model="form.motivo" required minlength="10" maxlength="2000" rows="3" /></div>
            <p class="text-sm">Confirma solo tras revisar los dictámenes y las condiciones. El motivo es operativo y visible en la solicitud.</p>
            <Message v-for="(error,key) in form.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
            <Button type="submit" label="Confirmar aprobación" :disabled="!estado.puede_aprobar" :loading="form.processing" />
        </form>
    </section>
</template>
