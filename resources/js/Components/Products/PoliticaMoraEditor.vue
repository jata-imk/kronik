<script setup>
const model = defineModel();
defineProps({ errors: { type: Object, default: () => ({}) }, readonly: Boolean });
const graces = [{ label: "A · Gracia efectiva (recomendada)", value: "efectiva" }, { label: "B · Retroactiva al superar la gracia", value: "retroactiva" }];
const interests = [{ label: "Ambos intereses", value: "ambos" }, { label: "Moratorio sustituye al ordinario", value: "sustituye" }];
</script>
<template>
    <section class="space-y-3 rounded-xl border border-primary-200 bg-primary-50/50 p-4 dark:border-primary-800 dark:bg-primary-950/20" aria-label="Política de atraso">
        <h3 class="font-semibold"><i class="pi pi-clock mr-2 text-primary" aria-hidden="true" />¿Qué ocurre si el cliente se atrasa?</h3>
        <template v-if="!model">
            <p class="text-sm">Modalidades de mora sin definir. No se deducen de las tasas ni se agregan a contratos anteriores. Para operar pagos se necesitarán condiciones explícitas.</p>
            <Button v-if="!readonly" label="Definir política de atraso" icon="pi pi-pencil" severity="secondary" @click="model = { gracia: 'efectiva', intereses: null }" />
        </template>
        <template v-else>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="mora-gracia" class="mb-2 block text-sm font-medium">Aplicación de los días de gracia</label><Select v-if="!readonly" input-id="mora-gracia" aria-label="Aplicación de los días de gracia" v-model="model.gracia" :options="graces" option-label="label" option-value="value" fluid :invalid="!!errors['version.politica_mora.gracia']" /><p v-else>{{ graces.find(item => item.value === model.gracia)?.label }}</p><p class="mt-2 text-sm">A: si vence el 10 y tiene 3 días de gracia, la mora comienza el 14. B: si supera la gracia con capital pendiente, se calcula desde el 11.</p></div>
                <div><label for="mora-intereses" class="mb-2 block text-sm font-medium">Intereses durante la mora</label><Select v-if="!readonly" input-id="mora-intereses" aria-label="Intereses durante la mora" v-model="model.intereses" :options="interests" option-label="label" option-value="value" placeholder="Selecciona la regla del contrato" fluid :invalid="!!errors['version.politica_mora.intereses']" /><p v-else>{{ interests.find(item => item.value === model.intereses)?.label }}</p><p class="mt-2 text-sm">Ambos conserva ordinario y moratorio sobre sus respectivas bases. Sustitución retira el ordinario solo del capital vencido sujeto a mora; el capital aún no vencido conserva su ordinario.</p></div>
            </div>
            <p v-if="model.intereses === 'sustituye'" class="text-sm">Con A, el ordinario continúa durante la gracia. Con B, al superarla, la sustitución alcanza esos días retroactivamente: no se cobran ambos intereses por los mismos días sobre ese capital.</p>
            <Message v-if="model.gracia === 'retroactiva' && model.intereses === 'sustituye'" severity="warn" :closable="false">Límite inicial de pagos: durante la gracia no se admitirán abonos parciales a una cuota afectada. Debe cubrirse completa, incluidos intereses, comisiones e impuestos. La compensación trazable queda para una entrega posterior. No cambies la fecha real de un pago para evitar este límite.</Message>
            <Message v-for="key in ['version.politica_mora','version.politica_mora.gracia','version.politica_mora.intereses']" :key="key" v-show="errors[key]" severity="error" size="small">{{ errors[key] }}</Message>
            <p class="text-xs text-surface-500">Se conserva por versión y en las condiciones del crédito. Configurar estas reglas no ejecuta cobros ni habilita dinero real.</p>
        </template>
    </section>
</template>
