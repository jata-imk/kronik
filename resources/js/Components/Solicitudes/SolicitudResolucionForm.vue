<script setup>
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({ solicitud: Object, can: Object, responsables: Array });
const form = useForm({ accion: null, motivo: "", responsable_id: props.solicitud.responsable_id, lock_version: props.solicitud.lock_version });
const opciones = computed(() => [
    ...(props.can.review && props.solicitud.estado === "en_revision" ? [{ label: "Devolver para corrección", value: "devolver" }, { label: "Rechazar solicitud", value: "rechazar" }] : []),
    ...(props.can.review && props.solicitud.estado === "aprobada" ? [{ label: "Devolver para nueva revisión (invalida aprobación)", value: "devolver" }] : []),
    ...(props.can.cancel && ["borrador", "devuelta", "en_revision", "aprobada"].includes(props.solicitud.estado) ? [{ label: "Cancelar solicitud", value: "cancelar" }] : []),
]);
function guardar() {
    form.lock_version = props.solicitud.lock_version;
    form.post(route("solicitudes.resolver", props.solicitud.id), { preserveScroll: true, onSuccess: () => form.reset("accion", "motivo") });
}
</script>

<template>
    <form v-if="opciones.length" class="space-y-3 rounded border border-surface-200 p-4 dark:border-surface-700" @submit.prevent="guardar">
        <h2 class="text-lg font-semibold">Devolución o cierre</h2>
        <div class="flex flex-col gap-1"><label for="sol-accion">Acción</label><Select input-id="sol-accion" aria-label="Acción" v-model="form.accion" :options="opciones" option-label="label" option-value="value" placeholder="Selecciona una acción" /></div>
        <template v-if="form.accion">
            <Message severity="warn" :closable="false">{{ form.accion === "devolver" ? "Se conservará la revisión actual. La corrección deberá enviarse nuevamente a revisión." : "Esta acción cerrará la solicitud y no puede deshacerse desde esta pantalla. No elimina el expediente." }}</Message>
            <div class="flex flex-col gap-1"><label for="sol-motivo">Motivo operativo</label><Textarea id="sol-motivo" v-model="form.motivo" rows="3" minlength="10" maxlength="2000" required aria-describedby="sol-motivo-ayuda" /><p id="sol-motivo-ayuda" class="text-sm">Explica la acción o corrección requerida. Visible para quienes consultan esta solicitud; no incluyas reportes SIC ni detalles reservados de PLD.</p></div>
            <div v-if="form.accion === 'devolver'" class="flex flex-col gap-1"><label for="sol-corrector">Responsable de corregir</label><Select input-id="sol-corrector" aria-label="Responsable de corregir" v-model="form.responsable_id" :options="responsables" option-label="name" option-value="id" /></div>
            <Button type="submit" :label="form.accion === 'devolver' ? 'Confirmar devolución' : form.accion === 'rechazar' ? 'Confirmar rechazo' : 'Confirmar cancelación'" severity="warn" :loading="form.processing" />
        </template>
        <Message v-for="(error, key) in form.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
    </form>
</template>
