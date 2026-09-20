<script setup>
import { useForm } from "@inertiajs/vue3";
const props = defineProps({ solicitud: Object, estado: Object, puedeAprobar: Boolean });
const form = useForm({ motivo: "", lock_version: props.solicitud.lock_version });
const etiquetas = { habilitacion: "Habilitación operativa", estado: "Estado de solicitud", permiso: "Permiso y sucursal", politica: "Política configurada", politica_vigente: "Política vigente", producto: "Producto disponible", monto: "Límite de monto", fecha: "Fecha estimada", separacion: "Separación de funciones", sic: "Requisito SIC", cliente: "Datos del cliente", evaluacion: "Dictamen de evaluación", evaluacion_vigente: "Vigencia de evaluación", pld: "Dictamen de cumplimiento", pld_vigente: "Vigencia de cumplimiento" };
function aprobar() {
    form.lock_version = props.solicitud.lock_version;
    form.post(route("solicitudes.aprobar", props.solicitud.id), { preserveScroll: true, onSuccess: () => form.reset("motivo") });
}
</script>
<template>
    <section v-if="estado && solicitud.estado === 'en_revision'" class="space-y-3 rounded border border-surface-200 p-4 dark:border-surface-700">
        <h2 class="text-lg font-semibold">Requisitos para aprobación</h2>
        <p>Se verifican nuevamente al confirmar. Un requisito cumplido no equivale a una aprobación ni a un desembolso.</p>
        <p v-if="estado.politica">Política {{ estado.politica.numero }} · Modalidad {{ estado.politica.condiciones.modalidad }}.</p>
        <p v-if="estado.politica" class="whitespace-pre-wrap">Capacidad de pago: {{ estado.politica.condiciones.criterio_capacidad }}</p>
        <ul class="space-y-2"><li v-for="item in estado.requisitos" :key="item.clave"><span class="font-medium">{{ item.cumplido ? 'Cumplido:' : 'Pendiente:' }} {{ etiquetas[item.clave] ?? item.clave.replace('documento_', '').replaceAll('_', ' ') }}</span><p v-if="!item.cumplido">{{ item.mensaje }}</p></li></ul>
        <form v-if="puedeAprobar" class="space-y-3" @submit.prevent="aprobar">
            <div class="flex flex-col gap-1"><label for="sol-motivo-aprobacion">Motivo de aprobación</label><Textarea id="sol-motivo-aprobacion" v-model="form.motivo" required minlength="10" maxlength="2000" rows="3" /></div>
            <p class="text-sm">Confirma solo tras revisar los dictámenes y las condiciones. El motivo es operativo y visible en la solicitud.</p>
            <Message v-for="(error,key) in form.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
            <Button type="submit" label="Confirmar aprobación" :disabled="!estado.puede_aprobar" :loading="form.processing" />
        </form>
    </section>
</template>
