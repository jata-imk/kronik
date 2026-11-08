<script setup>
import { computed } from "vue";
import Select from "primevue/select";

const props = defineProps({
    reglas: { type: Object, required: true },
    catalogo: { type: Object, required: true },
    combinaciones: { type: Object, default: () => ({}) },
    readonly: Boolean,
    errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(["update:reglas"]);
const bullet = computed(() =>
    props.reglas.metodos_amortizacion?.includes("bullet"),
);
const combinacion = computed(
    () => props.combinaciones[bullet.value ? "bullet" : "actuales"],
);
const bases = computed(() =>
    (props.catalogo.bases_moratorias ?? []).filter((o) =>
        combinacion.value?.bases_moratorias.includes(o.value),
    ),
);
const label = (dimension, valor) =>
    props.catalogo[dimension]?.find((o) => o.value === valor)?.label ??
    valor ??
    "Sin registrar";
const update = (key, value) => {
    if (!props.readonly)
        emit("update:reglas", { ...props.reglas, [key]: value });
};
const campos = computed(() => [
    ...(bullet.value
        ? [
              {
                  key: "politica_interes_bullet",
                  title: "Pago de intereses Bullet",
                  dimension: "politicas_interes_bullet",
                  opciones: props.catalogo.politicas_interes_bullet ?? [],
              },
          ]
        : []),
    {
        key: "base_ordinaria",
        title: "Base ordinaria",
        dimension: "bases_ordinarias",
        opciones: props.catalogo.bases_ordinarias ?? [],
    },
    {
        key: "base_moratoria",
        title: "Base moratoria",
        dimension: "bases_moratorias",
        opciones: bases.value,
    },
]);
</script>

<template>
    <section class="space-y-4 rounded-xl border border-surface-200 p-4" aria-label="Condiciones de intereses">
        <Message v-if="bullet && combinacion?.aviso" severity="info">{{ combinacion.aviso }}</Message>
        <div v-for="campo in campos" :key="campo.key">
            <label :for="`interes-${campo.key}`" class="mb-1 block text-sm font-medium">{{ campo.title }}</label>
            <p v-if="readonly" :id="`interes-${campo.key}`">{{ label(campo.dimension, reglas[campo.key]) }}</p>
            <Select v-else :aria-label="campo.title" :input-id="`interes-${campo.key}`" :model-value="reglas[campo.key]" @update:model-value="update(campo.key, $event)" :options="campo.opciones" option-label="label" option-value="value" :invalid="!!errors[`version.reglas.${campo.key}`]" :aria-invalid="!!errors[`version.reglas.${campo.key}`]" fluid />
            <Message v-if="errors[`version.reglas.${campo.key}`]" severity="error" size="small">{{ errors[`version.reglas.${campo.key}`] }}</Message>
        </div>
        <Button v-if="!readonly && bases.some(o => o.value === reglas.base_ordinaria)" type="button" label="Usar base ordinaria para mora" text @click="update('base_moratoria', reglas.base_ordinaria)" />
        <p v-if="bullet" class="text-sm text-surface-500">La obligación vencida impagada activa mora. Cada base global se aplica una sola vez, aunque existan varias obligaciones vencidas. El interés acumulado no se capitaliza.</p>
    </section>
</template>
