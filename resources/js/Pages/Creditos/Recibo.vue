<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import DistribucionPago from "@/Components/Creditos/DistribucionPago.vue";
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
const props = defineProps({ credito: Object, pago: Object, puedeReversar: Boolean });
const drawer = ref(false);
const form = useForm({ motivo: "", confirmacion_qa: false, idempotency_key: crypto.randomUUID() });
function reversar() { form.post(route("creditos.pagos.reverse", [props.credito.id, props.pago.id])); }
</script>
<template>
    <AppLayout :title="`Registro de pago #${pago.id}`"><template #card-header><PageHeader eyebrow="Operación · Cartera" :title="`${pago.tipo === 'reverso' ? 'Reverso' : 'Recibo interno'} #${pago.id}`" description="Detalle del movimiento y su distribución." /></template><template #card-content><div class="space-y-6 p-4 md:p-6">
        <Link :href="route('creditos.show', credito.id)" class="text-primary hover:underline">Volver al crédito CR-{{ credito.id }}</Link>
        <section class="k-feature p-6"><Tag :value="pago.tipo === 'reverso' ? 'Compensación · QA' : pago.reverso_id ? 'Pago revertido · QA' : 'Pago registrado · QA'" severity="info" /><p class="mt-4 text-3xl font-bold">{{ money(pago.importe) }}</p><p class="mt-2">Fecha efectiva: {{ pago.fecha_efectiva?.slice(0,10) }}</p><p class="mt-2 text-sm">Capturado: {{ new Date(pago.created_at).toLocaleString('es-MX') }} · usuario #{{ pago.actor_id }}</p></section>
        <Message severity="warn" :closable="false">Registro sintético QA. No es CFDI, comprobante bancario ni autorización para operar dinero real.</Message>
        <p v-if="pago.referencia" class="break-all">Referencia: {{ pago.referencia }}</p><p v-if="pago.motivo" class="break-words">Motivo: {{ pago.motivo }}</p>
        <Link v-if="pago.reversa_de" :href="route('creditos.pagos.show', [credito.id, pago.reversa_de])" class="inline-block text-primary underline">Consultar pago original #{{ pago.reversa_de }}</Link><Link v-if="pago.reverso_id" :href="route('creditos.pagos.show', [credito.id, pago.reverso_id])" class="inline-block text-primary underline">Consultar reverso #{{ pago.reverso_id }}</Link>
        <section class="space-y-4 rounded-2xl border border-surface-200 p-5"><h2 class="text-lg font-semibold">{{ pago.tipo === 'reverso' ? 'Distribución compensada (importes del pago original)' : 'Distribución registrada' }}</h2><DistribucionPago :filas="pago.asignaciones" /></section>
        <Button v-if="puedeReversar" label="Revertir último pago" icon="pi pi-undo" severity="danger" outlined @click="drawer = true" />
        <Drawer v-model:visible="drawer" header="Reverso compensatorio QA" position="right" class="!w-full md:!w-[36rem]"><form class="space-y-5" @submit.prevent="reversar"><Message severity="warn" :closable="false">No se elimina el recibo ni se devuelve dinero. Se compensan sus movimientos con la fecha efectiva original y se conserva la fecha real de esta captura. Solo se admite el último pago sin movimientos posteriores.</Message><label for="reverso-motivo" class="block font-semibold">Motivo del reverso *</label><Textarea id="reverso-motivo" v-model="form.motivo" rows="4" minlength="10" maxlength="1000" fluid /><label class="flex gap-3"><Checkbox v-model="form.confirmacion_qa" binary /><span>Confirmo la corrección sintética de QA, sin devolución bancaria.</span></label><Message v-for="(message,key) in form.errors" :key="key" severity="error">{{ message }}</Message><Button label="Confirmar reverso QA" type="submit" severity="danger" :loading="form.processing" :disabled="!form.confirmacion_qa" /></form></Drawer>
    </div></template></AppLayout>
</template>
