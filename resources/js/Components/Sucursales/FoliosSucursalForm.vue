<script setup>
import { useForm, router } from "@inertiajs/vue3";
import FieldHelp from "@/Components/FieldHelp.vue";

const props = defineProps({ sucursal: { type: Object, required: true }, editable: Boolean });
const copiar = (value) => ({ ...value, siguientes: { ...value.siguientes } });
const form = useForm(copiar(props.sucursal.folios));
const tipos = [
    { tipo: "solicitud", codigo: "SOL", nombre: "solicitud", uso: "Aparece en la captura, la evaluación y el seguimiento de la solicitud." },
    { tipo: "contrato", codigo: "CTO", nombre: "contrato", uso: "Aparece en el contrato y la tabla preparados antes de recibir la firma." },
    { tipo: "credito", codigo: "CRE", nombre: "crédito", uso: "Identifica el crédito en cartera y los futuros reportes SIC, retirando solo los guiones al exportar." },
    { tipo: "recibo", codigo: "REC", nombre: "recibo", uso: "Identifica cada pago, anticipo, liquidación y reverso. El recibo original siempre conserva su número." },
];
const preview = (item) => {
    const numero = form.siguientes[item.tipo];
    return numero && form.prefijo_folio?.trim()
        ? `${form.prefijo_folio.trim().toUpperCase()}-${item.codigo}-${String(numero).padStart(6, "0")}`
        : "Completa prefijo y siguiente número";
};
function cargar(page) {
    const actual = page.props.sucursales.find((sucursal) => sucursal.id === props.sucursal.id)?.folios;
    if (actual) {
        form.defaults(copiar(actual));
        form.reset();
        form.clearErrors();
    }
}
function guardar() {
    form.put(route("admin.sucursales.folios.update", props.sucursal.id), { preserveScroll: true, onSuccess: cargar });
}
function actualizar() {
    router.reload({ only: ["sucursales"], onSuccess: cargar });
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="guardar">
        <p class="text-sm text-surface-600 dark:text-surface-300">Numeración continua por sucursal. La vista previa no reserva números; se asignan al confirmar cada operación. Los registros anteriores conservan su folio.</p>
        <Message v-if="form.errors.version" severity="warn" :closable="false">{{ form.errors.version }} <Button label="Cargar numeración actual" type="button" text @click="actualizar" /></Message>
        <div>
            <div class="flex items-center gap-1"><label for="folio-prefijo" class="font-medium">Prefijo de folios</label><FieldHelp title="Prefijo de folios" description="Distingue los registros de esta sucursal. Los cambios afectan solo las emisiones futuras; los prefijos anteriores quedan reservados." example="MTZ-SOL-000001" note="Se permiten letras A–Z, números y guiones entre grupos. No se reutilizan prefijos de otras sucursales." /></div>
            <InputText id="folio-prefijo" v-model="form.prefijo_folio" :disabled="!editable" maxlength="20" fluid />
            <Message v-if="form.errors.prefijo_folio" severity="error" size="small">{{ form.errors.prefijo_folio }}</Message>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div v-for="item in tipos" :key="item.tipo" class="rounded-xl border border-surface-200 p-4 dark:border-surface-700">
                <div class="flex items-center gap-1"><label :for="`folio-${item.tipo}`" class="font-medium">Siguiente {{ item.nombre }}</label><FieldHelp :title="`Folio de ${item.nombre}`" :description="item.uso" :example="preview(item)" note="No se reinicia al cambiar el año. Adelantar el número deja números sin utilizar; no puede disminuirse." /></div>
                <InputNumber :input-id="`folio-${item.tipo}`" v-model="form.siguientes[item.tipo]" @input="form.siguientes[item.tipo] = $event.value" :disabled="!editable" :min="1" :max="999999999999999" :use-grouping="false" fluid />
                <output class="mt-2 block break-all text-sm font-medium text-primary" aria-live="polite">Próximo folio: {{ preview(item) }}</output>
                <Message v-if="form.errors[`siguientes.${item.tipo}`]" severity="error" size="small">{{ form.errors[`siguientes.${item.tipo}`] }}</Message>
            </div>
        </div>
        <Message v-if="form.errors.siguientes" severity="error">{{ form.errors.siguientes }}</Message>
        <Button v-if="editable" label="Guardar numeración" type="submit" icon="pi pi-check" :loading="form.processing" />
    </form>
</template>
