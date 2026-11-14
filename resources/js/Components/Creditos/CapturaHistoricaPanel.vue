<script setup>
import { useForm } from "@inertiajs/vue3";
import { watch } from "vue";

const props = defineProps({ creditoId: Number, captura: Object, puedeCompletar: Boolean });
const form = useForm({ confirmacion_qa: false, confirmacion_historial_completo: false });
watch(() => [props.creditoId, props.captura?.estado, props.puedeCompletar], () => {
    form.cancel();
    form.reset();
    form.clearErrors();
});
function completar() {
    if (!props.puedeCompletar || props.captura?.estado !== "pendiente" || form.processing || !form.confirmacion_qa || !form.confirmacion_historial_completo) return;
    form.post(route("creditos.captura-historica.completar", props.creditoId), { preserveScroll: true });
}
</script>

<template>
    <section v-if="captura?.estado === 'pendiente'" class="space-y-4 rounded-2xl border border-amber-300 p-5 dark:border-amber-700" aria-label="Captura histórica">
        <h2 class="text-lg font-semibold">Captura histórica pendiente</h2>
        <p>El saldo a hoy refleja solo los movimientos incorporados y es provisional mientras falten pagos. Registra desde el pago más antiguo con su fecha efectiva real.</p>
        <p class="text-sm">Esta captura queda excluida de avisos financieros basados en un historial completo. Completarla no liquida el crédito, no mueve dinero ni envía avisos acumulados.</p>
        <form v-if="puedeCompletar" class="space-y-3" @submit.prevent="completar">
            <label class="flex items-start gap-2"><Checkbox v-model="form.confirmacion_historial_completo" binary /><span>Incorporé todos los pagos históricos conocidos.</span></label>
            <label class="flex items-start gap-2"><Checkbox v-model="form.confirmacion_qa" binary /><span>Confirmo que esta captura es exclusivamente sintética QA.</span></label>
            <Message v-for="(error, key) in form.errors" :key="key" severity="error">{{ error }}</Message>
            <Button type="submit" label="Completar captura histórica" icon="pi pi-check" :loading="form.processing" :disabled="form.processing || !form.confirmacion_qa || !form.confirmacion_historial_completo" />
        </form>
        <p v-else class="text-sm">Una persona con permiso de pago en la sucursal responsable debe completar la captura cuando termine de registrar la historia.</p>
    </section>
    <Message v-else-if="captura?.estado === 'completa'" severity="info" :closable="false">Captura histórica completada el {{ new Date(captura.completada_en).toLocaleString("es-MX") }}. Los movimientos y documentos originales se conservan.</Message>
</template>
