<script setup>
import { computed } from "vue";
import { emptyTaxConcept, taxConceptLabel } from "./fiscalidad";
const model = defineModel({ type: Object });
const props = defineProps({ title: String, path: String, readonly: Boolean, errors: { type: Object, default: () => ({}) } });
const concept = computed(() => model.value ?? emptyTaxConcept());
const id = computed(() => props.path.replaceAll(".", "-"));
const options = ["no_definido", "gravado", "exento", "no_causa"].map((value) => ({ value, label: taxConceptLabel({ tratamiento: value }) }));
const update = (key, value) => {
    const next = { ...concept.value, [key]: value };
    if (key === "tratamiento") { next.tasa = null; next.base = null; }
    model.value = next;
};
const messages = computed(() => Object.entries(props.errors).filter(([key]) => key === props.path || key.startsWith(`${props.path}.`)));
</script>
<template>
    <section class="rounded-xl border border-surface-200 bg-surface-0 p-4 dark:border-surface-700 dark:bg-surface-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2"><h4 class="font-semibold"><i class="pi pi-percentage mr-2 text-primary" aria-hidden="true" />{{ title }}</h4><Tag :value="taxConceptLabel(concept)" :severity="concept.tratamiento === 'no_definido' ? 'warn' : 'info'" /></div>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2"><label :for="`${id}-tratamiento`" class="mb-1 block text-sm">Tratamiento · {{ title }}</label><Select :input-id="`${id}-tratamiento`" :aria-label="`Tratamiento · ${title}`" :model-value="concept.tratamiento" :options="options" option-label="label" option-value="value" :disabled="readonly" :invalid="!!errors[`${path}.tratamiento`]" :aria-invalid="!!errors[`${path}.tratamiento`]" fluid @update:model-value="update('tratamiento', $event)" /></div>
            <template v-if="concept.tratamiento === 'gravado'">
                <div><label :for="`${id}-tasa`" class="mb-1 block text-sm">Tasa de impuesto (%) · {{ title }}</label><InputText :id="`${id}-tasa`" :model-value="concept.tasa" inputmode="decimal" placeholder="Ejemplo de prueba: 16" :disabled="readonly" :invalid="!!errors[`${path}.tasa`]" :aria-invalid="!!errors[`${path}.tasa`]" fluid @update:model-value="update('tasa', $event)" /><p class="mt-1 text-xs text-surface-500">Porcentaje explícito de 0 a 100, hasta 8 decimales; usa punto decimal. No es la tasa de interés.</p></div>
                <div><label :for="`${id}-base`" class="mb-1 block text-sm">Base de impuesto · {{ title }}</label><Select :input-id="`${id}-base`" :aria-label="`Base de impuesto · ${title}`" :model-value="concept.base" :options="[{ value: 'importe_concepto', label: 'Importe íntegro de este concepto' }]" option-label="label" option-value="value" placeholder="Selecciona la base" :disabled="readonly" :invalid="!!errors[`${path}.base`]" :aria-invalid="!!errors[`${path}.base`]" fluid @update:model-value="update('base', $event)" /><p class="mt-1 text-xs text-surface-500">Interés o comisión, nunca el capital. Si necesitas otra base fiscal, déjalo sin definir: aún no está soportada.</p></div>
            </template>
        </div>
        <p v-if="concept.tratamiento === 'no_definido'" class="mt-3 text-sm text-amber-700 dark:text-amber-300">Pendiente de definición por la institución. No significa exento ni tasa cero.</p>
        <Message v-for="[key, message] in messages" :key="key" severity="error" size="small" class="mt-2">{{ message }}</Message>
    </section>
</template>
