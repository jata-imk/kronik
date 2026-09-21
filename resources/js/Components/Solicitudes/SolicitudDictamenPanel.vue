<script setup>
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";
const props = defineProps({ tipo: String, solicitud: Object, registros: Array, puedeRegistrar: Boolean });
const pld = computed(() => props.tipo === "pld");
const form = useForm({ tipo_dictamen: props.tipo, lock_version: props.solicitud.lock_version, resultado: null, fundamento: "", fuentes: "", metodologia: "", nivel_riesgo: null });
const labels = { favorable: "Favorable", desfavorable: "Desfavorable", pendiente: "Pendiente de información", sin_observaciones: "Sin observaciones en la revisión manual", bloqueada: "Bloqueada para seguimiento" };
const opciones = computed(() => (pld.value ? ["sin_observaciones", "pendiente", "bloqueada"] : ["favorable", "pendiente", "desfavorable"]).map(value => ({ value, label: labels[value] })));
function guardar() {
    form.lock_version = props.solicitud.lock_version;
    form.transform(data => {
        const payload = { ...data };
        if (!pld.value) delete payload.nivel_riesgo;
        return payload;
    }).post(route("solicitudes.dictaminar", props.solicitud.id), { preserveScroll: true, onSuccess: () => form.reset("resultado", "fundamento", "fuentes", "metodologia", "nivel_riesgo") });
}
</script>

<template>
    <section class="space-y-3 rounded border border-surface-200 p-4 dark:border-surface-700">
        <h2 class="text-lg font-semibold">{{ pld ? "Revisión PLD" : "Evaluación manual preliminar" }}</h2>
        <p>{{ pld ? "Dictamen humano basado en las fuentes y metodología indicadas. No consulta listas automáticamente ni acredita cumplimiento legal." : "Documenta capacidad de pago y evidencias analizadas. No genera score, no sustituye una consulta SIC requerida y no aprueba el crédito." }}</p>
        <Message v-if="!registros.length" severity="info" :closable="false">No hay dictámenes registrados en esta especialidad.</Message>
        <p v-else-if="solicitud.estado === 'en_revision' && !registros.some(item => item.revision_actual)" role="status">Pendiente: los dictámenes anteriores no corresponden a una revisión actualmente evaluable.</p>
        <form v-if="puedeRegistrar && solicitud.estado === 'en_revision'" class="space-y-3" @submit.prevent="guardar">
            <div class="flex flex-col gap-1"><label :for="`${tipo}-resultado`">Resultado {{ pld ? 'PLD' : 'de evaluación' }}</label><Select :input-id="`${tipo}-resultado`" :aria-label="`Resultado ${pld ? 'PLD' : 'de evaluación'}`" v-model="form.resultado" :options="opciones" option-label="label" option-value="value" placeholder="Selecciona un resultado" /></div>
            <div v-if="pld" class="flex flex-col gap-1"><label for="pld-riesgo">Nivel de riesgo</label><Select input-id="pld-riesgo" aria-label="Nivel de riesgo" v-model="form.nivel_riesgo" :options="[{ label: 'Bajo', value: 'bajo' }, { label: 'Medio', value: 'medio' }, { label: 'Alto', value: 'alto' }, { label: 'Sin determinar', value: 'sin_determinar' }]" option-label="label" option-value="value" /></div>
            <div class="flex flex-col gap-1"><label :for="`${tipo}-metodologia`">Metodología y versión utilizadas ({{ tipo }})</label><InputText :id="`${tipo}-metodologia`" v-model="form.metodologia" required minlength="5" maxlength="500" /></div>
            <div class="flex flex-col gap-1"><label :for="`${tipo}-fuentes`">Fuentes y evidencias revisadas ({{ tipo }})</label><Textarea :id="`${tipo}-fuentes`" v-model="form.fuentes" required minlength="10" maxlength="2000" rows="2" /></div>
            <div class="flex flex-col gap-1"><label :for="`${tipo}-fundamento`">Fundamento reservado ({{ tipo }})</label><Textarea :id="`${tipo}-fundamento`" v-model="form.fundamento" required minlength="20" maxlength="4000" rows="3" /></div>
            <p class="text-sm">Cada registro conserva actor, fecha y revisión. Para corregir un dictamen registra otro: no se elimina el anterior. No copies respuestas completas de SIC ni credenciales.</p>
            <Message v-for="(error, key) in form.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
            <Button type="submit" :label="pld ? 'Registrar revisión PLD' : 'Registrar evaluación'" :loading="form.processing" />
        </form>
        <details v-if="registros.length" open>
            <summary class="cursor-pointer">Últimos 20 dictámenes, del más reciente al más antiguo</summary>
            <article v-for="(item, index) in registros" :key="item.id" class="mt-3 border-l-2 pl-3">
                <h3 class="font-semibold">{{ labels[item.resultado] }} · {{ item.revision_actual ? (index === 0 ? 'Último de la revisión actual' : 'Antecedente de la revisión actual') : 'Histórico; no aplica a la revisión actual' }}</h3>
                <p>{{ item.actor.name }} · {{ new Date(item.created_at).toLocaleString('es-MX') }}</p>
                <p class="whitespace-pre-wrap">Metodología: {{ item.contenido.metodologia }}</p>
                <p v-if="pld">Riesgo: {{ { bajo: 'Bajo', medio: 'Medio', alto: 'Alto', sin_determinar: 'Sin determinar' }[item.contenido.nivel_riesgo] }}</p>
                <p class="whitespace-pre-wrap">Fuentes: {{ item.contenido.fuentes }}</p>
                <p class="whitespace-pre-wrap">{{ item.contenido.fundamento }}</p>
            </article>
        </details>
    </section>
</template>
