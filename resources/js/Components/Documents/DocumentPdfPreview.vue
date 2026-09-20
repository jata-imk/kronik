<script setup>
import axios from "axios";
import { nextTick, onBeforeUnmount, ref, watch } from "vue";
import "pdfjs-dist/web/pdf_viewer.css";

const props = defineProps({
    visible: Boolean,
    source: { type: Object, default: null },
});
const emit = defineEmits(["update:visible"]);

const loading = ref(false);
const rendering = ref(false);
const error = ref("");
const pageNumber = ref(1);
const pages = ref(0);
const scale = ref(1);
const viewportContainer = ref(null);
const pageElements = new Map();
const renderTasks = new Map();
const textTasks = new Map();

let document;
let loadingTask;
let request;
let generation = 0;
let renderGeneration = 0;
let scrollFrame;
let pdfjs;

const setPageElement = (number, element) => {
    if (element) pageElements.set(number, element);
    else pageElements.delete(number);
};

function cancelRendering() {
    renderGeneration++;
    for (const task of renderTasks.values()) task?.cancel();
    for (const task of textTasks.values()) task?.cancel();
    renderTasks.clear();
    textTasks.clear();
}

function release() {
    generation++;
    cancelRendering();
    cancelAnimationFrame(scrollFrame);
    request?.abort();
    loadingTask?.destroy();
    document = null;
    pages.value = 0;
    pageNumber.value = 1;
    pageElements.clear();
}

async function drawPage(number, current) {
    const element = pageElements.get(number);
    if (!document || !element) return;

    const page = await document.getPage(number);
    if (current !== renderGeneration) return;
    const viewport = page.getViewport({ scale: scale.value });
    const canvas = element.querySelector("canvas");
    const textContainer = element.querySelector(".textLayer");
    if (!canvas || !textContainer) return;

    element.style.width = `${viewport.width}px`;
    element.style.height = `${viewport.height}px`;
    const ratio = window.devicePixelRatio || 1;
    canvas.width = viewport.width * ratio;
    canvas.height = viewport.height * ratio;
    canvas.style.width = `${viewport.width}px`;
    canvas.style.height = `${viewport.height}px`;

    const renderTask = page.render({
        canvasContext: canvas.getContext("2d"),
        viewport,
        transform: ratio === 1 ? null : [ratio, 0, 0, ratio, 0, 0],
    });
    renderTasks.set(number, renderTask);
    await renderTask.promise;
    if (current !== renderGeneration) return;

    textContainer.replaceChildren();
    textContainer.style.setProperty("--scale-factor", scale.value);
    const textTask = new pdfjs.TextLayer({
        textContentSource: await page.getTextContent(),
        container: textContainer,
        viewport,
    });
    textTasks.set(number, textTask);
    await textTask.render();
}

async function drawAll() {
    if (!document) return;
    cancelRendering();
    const current = renderGeneration;
    rendering.value = true;
    error.value = "";
    try {
        await nextTick();
        for (let number = 1; number <= pages.value; number++) {
            await drawPage(number, current);
            if (current !== renderGeneration) return;
        }
    } catch (exception) {
        if (
            current === renderGeneration &&
            exception.name !== "RenderingCancelledException" &&
            exception.name !== "AbortException"
        ) {
            error.value =
                "No fue posible mostrar el PDF. Inténtalo nuevamente.";
        }
    } finally {
        if (current === renderGeneration) rendering.value = false;
    }
}

async function changeScale(nextScale) {
    scale.value = Math.min(3, Math.max(0.25, nextScale));
    await drawAll();
}

async function fitWidth() {
    if (!document) return;
    const page = await document.getPage(1);
    const availableWidth =
        (viewportContainer.value?.clientWidth ?? 650) - 32;
    scale.value = Math.min(
        2,
        Math.max(0.25, availableWidth / page.getViewport({ scale: 1 }).width),
    );
    await drawAll();
}

function goToPage(number) {
    const target = Math.min(pages.value, Math.max(1, number));
    pageNumber.value = target;
    pageElements
        .get(target)
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
}

function updateCurrentPage() {
    cancelAnimationFrame(scrollFrame);
    scrollFrame = requestAnimationFrame(() => {
        const viewport = viewportContainer.value;
        if (!viewport || !pages.value) return;
        const top = viewport.getBoundingClientRect().top;
        let closest = pageNumber.value;
        let distance = Number.POSITIVE_INFINITY;
        for (const [number, element] of pageElements) {
            const nextDistance = Math.abs(
                element.getBoundingClientRect().top - top - 16,
            );
            if (nextDistance < distance) {
                closest = number;
                distance = nextDistance;
            }
        }
        pageNumber.value = closest;
    });
}

async function load() {
    release();
    if (!props.visible || !props.source) return;
    const current = generation;
    loading.value = true;
    error.value = "";
    request = new AbortController();
    try {
        pdfjs ??= await import("pdfjs-dist");
        pdfjs.GlobalWorkerOptions.workerSrc = new URL(
            "pdfjs-dist/build/pdf.worker.min.mjs",
            import.meta.url,
        ).toString();
        const response = await axios({
            ...props.source,
            responseType: "blob",
            signal: request.signal,
        });
        if (current !== generation) return;
        const bytes = new Uint8Array(await response.data.arrayBuffer());
        loadingTask = pdfjs.getDocument({ data: bytes, isEvalSupported: false });
        document = await loadingTask.promise;
        if (current !== generation) return;
        pages.value = document.numPages;
        loading.value = false;
        await nextTick();
        await fitWidth();
    } catch (exception) {
        if (current !== generation || axios.isCancel(exception)) return;
        let data = exception.response?.data;
        if (data instanceof Blob) {
            try {
                data = JSON.parse(await data.text());
            } catch {
                data = null;
            }
        }
        error.value =
            Object.values(data?.errors ?? {}).flat()[0] ??
            data?.message ??
            "No fue posible preparar el PDF. Inténtalo nuevamente.";
    } finally {
        if (current === generation) loading.value = false;
    }
}

watch(() => [props.visible, props.source], load);
onBeforeUnmount(release);
</script>

<template>
    <Dialog :visible="visible" modal maximizable :draggable="false" header="Previsualización de plantilla" :style="{ width: 'min(1100px, 97vw)' }" @update:visible="emit('update:visible', $event)">
        <Message severity="warn" :closable="false"><strong>Vista previa con datos sintéticos.</strong> PDF de prueba; no representa una firma ni un documento del cliente.</Message>
        <div class="my-3 flex flex-wrap items-center justify-center gap-2">
            <Button aria-label="Página anterior" icon="pi pi-chevron-left" :disabled="loading || pageNumber <= 1" @click="goToPage(pageNumber - 1)" />
            <span role="status">Página {{ pages ? pageNumber : 0 }} / {{ pages }}</span>
            <Button aria-label="Página siguiente" icon="pi pi-chevron-right" :disabled="loading || pageNumber >= pages" @click="goToPage(pageNumber + 1)" />
            <Button aria-label="Alejar PDF" icon="pi pi-minus" :disabled="loading || !pages || rendering" @click="changeScale(scale - .25)" />
            <span>{{ Math.round(scale * 100) }}%</span>
            <Button aria-label="Acercar PDF" icon="pi pi-plus" :disabled="loading || !pages || rendering" @click="changeScale(scale + .25)" />
            <Button label="Ajustar al ancho" severity="secondary" :disabled="loading || !pages || rendering" @click="fitWidth" />
        </div>
        <div ref="viewportContainer" class="h-[65vh] overflow-auto rounded-xl bg-surface-200 p-4" @scroll.passive="updateCurrentPage">
            <div v-if="loading" role="status"><Skeleton height="28rem" /><p>Preparando PDF…</p></div>
            <div v-else-if="error"><Message severity="error" :closable="false">{{ error }}</Message><Button class="mt-3" label="Reintentar" @click="load" /></div>
            <div v-else-if="pages" class="space-y-4" aria-label="Previsualización segura de la plantilla">
                <div v-for="number in pages" :key="number" :ref="element => setPageElement(number, element)" class="relative mx-auto bg-white shadow-lg" :aria-label="`Página ${number} de ${pages}`">
                    <canvas aria-hidden="true" />
                    <div class="textLayer" />
                </div>
            </div>
        </div>
    </Dialog>
</template>
