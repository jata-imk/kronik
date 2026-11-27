<script setup>
import axios from "axios";
import { onBeforeUnmount, ref, watch } from "vue";

const props = defineProps({ source: { type: Object, default: null }, identity: [String, Number] });
const result = ref(null);
const loading = ref(false);
const error = ref("");
let controller;
let revision = 0;
function clear() {
    revision++;
    controller?.abort();
    controller = null;
    result.value = null;
    loading.value = false;
    error.value = "";
}
async function refresh() {
    clear();
    if (!props.source) return;
    const current = revision;
    controller = new AbortController();
    loading.value = true;
    try {
        const response = await axios.request({ ...props.source, signal: controller.signal, headers: { Accept: "application/json" } });
        if (current === revision) result.value = response.data;
    } catch (failure) {
        if (current !== revision || axios.isCancel(failure)) return;
        error.value = Object.values(failure.response?.data?.errors ?? {}).flat()[0] ?? "No fue posible preparar la previsualización. Inténtelo nuevamente.";
    } finally {
        if (current === revision) loading.value = false;
    }
}
watch(() => [props.source, props.identity], clear, { deep: true });
onBeforeUnmount(clear);
</script>

<template>
    <section class="min-w-0 rounded-xl border border-surface-200 p-4 dark:border-surface-700" aria-label="Previsualización">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold">Previsualización</h3>
            <button type="button" class="rounded bg-primary px-3 py-2 text-primary-contrast disabled:opacity-50" :disabled="loading || !source" @click="refresh">Actualizar previsualización</button>
        </div>
        <p v-if="loading" role="status">Preparando previsualización…</p>
        <p v-if="error" role="alert" class="text-red-600">{{ error }}</p>
        <template v-if="result">
            <p class="mb-2 text-sm">{{ result.notice }}</p>
            <p v-if="result.subject" class="mb-2 text-sm"><strong>Asunto:</strong> {{ result.subject }}</p>
            <p class="mb-2 text-sm">Formato: {{ result.format === "html" ? "HTML" : "Texto plano" }}</p>
            <pre v-if="result.format === 'text'" class="whitespace-pre-wrap break-words font-sans" data-testid="text-preview">{{ result.body }}</pre>
            <iframe v-else :srcdoc="result.html" sandbox="" referrerpolicy="no-referrer" title="Previsualización HTML" class="h-[60vh] w-full border-0 bg-white" />
            <p v-if="result.measurement" class="mt-3 text-sm" :class="result.measurement.segments > 2 && 'text-red-600'">Estimación: {{ result.measurement.segments }} segmentos · {{ result.measurement.encoding }} · {{ result.measurement.units }} unidades. Máximo permitido: dos segmentos después de sustituir los datos reales.</p>
        </template>
        <p v-else-if="!loading && !error" class="text-sm">Actualiza la previsualización para consultar el contenido con datos sintéticos.</p>
    </section>
</template>
