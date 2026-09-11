<script setup>
import axios from "axios";
import { nextTick, onBeforeUnmount, ref, watch } from "vue";
import "pdfjs-dist/web/pdf_viewer.css";
const props = defineProps({ visible: Boolean, source: { type: Object, default: null } });
const emit = defineEmits(["update:visible"]);
const loading = ref(false);
const error = ref("");
const pageNumber = ref(1);
const pages = ref(0);
const scale = ref(1);
const canvas = ref(null);
const textContainer = ref(null);
const pageContainer = ref(null);
const viewportContainer = ref(null);
let document;
let loadingTask;
let renderTask;
let textTask;
let request;
let generation = 0;
let renderGeneration = 0;
let pdfjs;

function release() {
    generation++;
    renderGeneration++;
    request?.abort(); renderTask?.cancel(); textTask?.cancel(); loadingTask?.destroy();
    document = null; pages.value = 0;
}
async function draw() {
    if (!document) return;
    const current = ++renderGeneration;
    renderTask?.cancel(); textTask?.cancel();
    try {
        const page = await document.getPage(pageNumber.value);
        if (current !== renderGeneration) return;
        const viewport = page.getViewport({ scale: scale.value });
        await nextTick();
        if (!canvas.value || current !== renderGeneration) return;
        pageContainer.value.style.width = `${viewport.width}px`;
        pageContainer.value.style.height = `${viewport.height}px`;
        const ratio = window.devicePixelRatio || 1;
        canvas.value.width = viewport.width * ratio;
        canvas.value.height = viewport.height * ratio;
        canvas.value.style.width = `${viewport.width}px`;
        canvas.value.style.height = `${viewport.height}px`;
        renderTask = page.render({ canvasContext: canvas.value.getContext("2d"), viewport, transform: ratio === 1 ? null : [ratio, 0, 0, ratio, 0, 0] });
        await renderTask.promise;
        if (current !== renderGeneration) return;
        textContainer.value.replaceChildren();
        textContainer.value.style.setProperty("--scale-factor", scale.value);
        textTask = new pdfjs.TextLayer({ textContentSource: await page.getTextContent(), container: textContainer.value, viewport });
        await textTask.render();
    } catch (e) {
        if (current === renderGeneration && e.name !== "RenderingCancelledException" && e.name !== "AbortException") error.value = "No fue posible mostrar esta página. Inténtalo nuevamente.";
    }
}
async function fitWidth() {
    if (!document) return;
    const page = await document.getPage(pageNumber.value);
    scale.value = Math.min(2, Math.max(0.25, ((viewportContainer.value?.clientWidth ?? 650) - 32) / page.getViewport({ scale: 1 }).width));
    await draw();
}
async function load() {
    release();
    if (!props.visible || !props.source) return;
    const current = generation;
    loading.value = true; error.value = ""; pageNumber.value = 1;
    request = new AbortController();
    try {
        pdfjs ??= await import("pdfjs-dist");
        pdfjs.GlobalWorkerOptions.workerSrc = new URL("pdfjs-dist/build/pdf.worker.min.mjs", import.meta.url).toString();
        const response = await axios({ ...props.source, responseType: "blob", signal: request.signal });
        if (current !== generation) return;
        const bytes = new Uint8Array(await response.data.arrayBuffer());
        loadingTask = pdfjs.getDocument({ data: bytes, isEvalSupported: false });
        document = await loadingTask.promise;
        if (current !== generation) return;
        pages.value = document.numPages;
        loading.value = false;
        await nextTick();
        await fitWidth();
    } catch (e) {
        if (current !== generation || axios.isCancel(e)) return;
        let data = e.response?.data;
        if (data instanceof Blob) { try { data = JSON.parse(await data.text()); } catch { data = null; } }
        error.value = Object.values(data?.errors ?? {}).flat()[0] ?? data?.message ?? "No fue posible preparar el PDF. Inténtalo nuevamente.";
    } finally { if (current === generation) loading.value = false; }
}
watch(() => [props.visible, props.source], load);
watch([pageNumber, scale], draw);
onBeforeUnmount(release);
</script>

<template>
    <Dialog :visible="visible" modal maximizable :draggable="false" header="Previsualización de plantilla" :style="{ width: 'min(1100px, 97vw)' }" @update:visible="emit('update:visible', $event)">
        <Message severity="warn" :closable="false"><strong>Vista previa con datos sintéticos.</strong> PDF de prueba; no representa una firma ni un documento del cliente.</Message>
        <div class="my-3 flex flex-wrap items-center justify-center gap-2">
            <Button aria-label="Página anterior" icon="pi pi-chevron-left" :disabled="loading || pageNumber <= 1" @click="pageNumber--" />
            <span role="status">Página {{ pages ? pageNumber : 0 }} / {{ pages }}</span>
            <Button aria-label="Página siguiente" icon="pi pi-chevron-right" :disabled="loading || pageNumber >= pages" @click="pageNumber++" />
            <Button aria-label="Alejar PDF" icon="pi pi-minus" :disabled="loading || !pages" @click="scale = Math.max(.25, scale - .25)" /><span>{{ Math.round(scale * 100) }}%</span><Button aria-label="Acercar PDF" icon="pi pi-plus" :disabled="loading || !pages" @click="scale = Math.min(3, scale + .25)" />
            <Button label="Ajustar al ancho" severity="secondary" :disabled="loading || !pages" @click="fitWidth" />
        </div>
        <div ref="viewportContainer" class="h-[65vh] overflow-auto rounded-xl bg-surface-200 p-4">
            <div v-if="loading" role="status"><Skeleton height="28rem" /><p>Preparando PDF…</p></div>
            <div v-else-if="error"><Message severity="error" :closable="false">{{ error }}</Message><Button class="mt-3" label="Reintentar" @click="load" /></div>
            <div v-else-if="pages" ref="pageContainer" class="relative mx-auto bg-white shadow-lg" aria-label="Previsualización segura de la plantilla"><canvas ref="canvas" aria-hidden="true" /><div ref="textContainer" class="textLayer" /></div>
        </div>
    </Dialog>
</template>
