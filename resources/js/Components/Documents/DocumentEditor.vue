<script setup>
import Quill from "quill";
import "quill/dist/quill.snow.css";
import "../../../css/document-content.css";
import { onMounted, onBeforeUnmount, ref, watch } from "vue";
import DocumentImageLibrary from "./DocumentImageLibrary.vue";

const props = defineProps({ modelValue: { type: String, default: "" }, section: { type: String, required: true } });
const emit = defineEmits(["update:modelValue"]);
const host = ref(null);
const toolbar = ref(null);
const library = ref(false);
const selectedImage = ref(null);
const imageWidth = ref(160);
const imageAlt = ref("");
let quill;
let selection = { index: 0, length: 0 };
let internalValue = "";
const Embed = Quill.import("formats/image");
class ResourceImage extends Embed {
    static blotName = "resourceImage";
    static tagName = "IMG";
    static create(value) {
        const node = super.create();
        node.setAttribute("data-recurso-id", value.id);
        node.setAttribute("src", route("documento-recursos.show", value.id));
        node.setAttribute("alt", value.alt ?? "");
        node.setAttribute("width", value.width ?? 160);
        return node;
    }
    static value(node) { return { id: node.getAttribute("data-recurso-id"), alt: node.getAttribute("alt"), width: Number(node.getAttribute("width")) || 160 }; }
    static formats(node) { return { width: node.getAttribute("width"), alt: node.getAttribute("alt") }; }
    format(name, value) {
        if (name === "width" || name === "alt") this.domNode.setAttribute(name, value);
        else super.format(name, value);
    }
}
const BlockEmbed = Quill.import("blots/block/embed");
class PageBreak extends BlockEmbed {
    static blotName = "pageBreak";
    static tagName = "DIV";
    static className = "document-page-break";
    static value() { return true; }
}
Quill.register(ResourceImage, true);
Quill.register(PageBreak, true);
const Size = Quill.import("attributors/style/size");
Size.whitelist = ["8pt", "9pt", "10pt", "11pt", "12pt", "14pt", "16pt", "18pt", "20pt", "24pt", "28pt", "32pt", "36pt"];
Quill.register(Size, true);

function publish() {
    internalValue = quill.getSemanticHTML();
    emit("update:modelValue", internalValue);
}
function insert(value, type = "text") {
    if (!quill) return;
    const range = quill.getSelection() ?? selection;
    const index = Math.min(range.index, quill.getLength() - 1);
    quill.history.cutoff();
    const Delta = Quill.import("delta");
    quill.updateContents(new Delta().retain(index).delete(range.length).insert(type === "text" ? value : { [type]: value }), "user");
    quill.setSelection(index + (type === "text" ? value.length : 1), 0, "user");
    quill.focus();
    quill.history.cutoff();
}
function insertVariable(variable) { insert(`{{${variable.clave}}}`); }
function openLibrary() {
    selection = quill.getSelection() ?? selection;
    library.value = true;
}
function chooseImage(resource) { insert({ id: resource.id, alt: resource.nombre, width: 160 }, "resourceImage"); }
function updateImage() {
    if (!selectedImage.value?.isConnected) return;
    const blot = Quill.find(selectedImage.value);
    const index = quill.getIndex(blot);
    quill.formatText(index, 1, { width: Math.max(24, Math.min(650, Number(imageWidth.value) || 160)), alt: imageAlt.value.slice(0, 160) }, "user");
}
function imageClick(event) {
    const node = event.target.closest("img[data-recurso-id]");
    selectedImage.value = node;
    if (node) {
        imageWidth.value = Number(node.getAttribute("width")) || 160;
        imageAlt.value = node.getAttribute("alt") ?? "";
        selection = { index: quill.getIndex(Quill.find(node)), length: 1 };
    }
}
onMounted(() => {
    quill = new Quill(host.value, {
        theme: "snow",
        formats: ["header", "font", "size", "bold", "italic", "underline", "strike", "align", "indent", "list", "blockquote", "color", "background", "resourceImage", ...(props.section === "contenido_html" ? ["pageBreak"] : [])],
        modules: { toolbar: toolbar.value, history: { userOnly: true } },
    });
    quill.root.classList.add("document-content");
    quill.root.setAttribute("aria-label", `Editor de ${props.section === 'contenido_html' ? 'contenido' : props.section === 'encabezado_html' ? 'encabezado' : 'pie de página'}`);
    quill.clipboard.addMatcher("IMG", (node) => {
        const Delta = Quill.import("delta");
        return /^[a-f0-9-]{36}$/.test(node.getAttribute("data-recurso-id") ?? "") ? new Delta().insert({ resourceImage: ResourceImage.value(node) }) : new Delta();
    });
    quill.setContents(quill.clipboard.convert({ html: props.modelValue ?? "" }));
    quill.history.clear();
    quill.on("text-change", publish);
    quill.on("selection-change", range => { if (range) selection = range; });
    quill.root.addEventListener("click", imageClick);
});
watch(() => props.modelValue, value => {
    if (quill && value !== internalValue) {
        quill.setContents(quill.clipboard.convert({ html: value ?? "" }), "silent");
        quill.history.clear(); selection = { index: 0, length: 0 };
    }
});
onBeforeUnmount(() => { quill?.off("text-change", publish); quill?.root.removeEventListener("click", imageClick); quill = null; });
defineExpose({ insertVariable });
</script>

<template>
    <div class="document-editor min-w-0">
        <div ref="toolbar" class="flex flex-wrap gap-y-2">
            <span class="ql-formats"><select class="ql-header" aria-label="Estilo de párrafo"><option value="">Texto normal</option><option value="1">Título 1</option><option value="2">Título 2</option><option value="3">Título 3</option></select><select class="ql-font" aria-label="Fuente"><option value="">Sans</option><option value="serif">Serif</option><option value="monospace">Monoespaciada</option></select><select class="ql-size" aria-label="Tamaño de letra"><option v-for="size in Size.whitelist" :key="size" :value="size" :selected="size === '11pt'">{{ size }}</option></select></span>
            <span class="ql-formats"><button type="button" class="ql-bold" aria-label="Negrita" title="Negrita" /><button type="button" class="ql-italic" aria-label="Cursiva" title="Cursiva" /><button type="button" class="ql-underline" aria-label="Subrayado" title="Subrayado" /><button type="button" class="ql-strike" aria-label="Tachado" title="Tachado" /></span>
            <span class="ql-formats"><select class="ql-align" aria-label="Alineación"><option value="" /><option value="center" /><option value="right" /><option value="justify" /></select><button type="button" class="ql-indent" value="-1" aria-label="Reducir sangría" title="Reducir sangría" /><button type="button" class="ql-indent" value="+1" aria-label="Aumentar sangría" title="Aumentar sangría" /></span>
            <span class="ql-formats"><button type="button" class="ql-list" value="ordered" aria-label="Lista numerada" title="Lista numerada" /><button type="button" class="ql-list" value="bullet" aria-label="Lista con viñetas" title="Lista con viñetas" /><button type="button" class="ql-blockquote" aria-label="Cita" title="Cita" /><select class="ql-color" aria-label="Color de texto" /><select class="ql-background" aria-label="Resaltado de texto" /><button type="button" class="ql-clean" aria-label="Limpiar formato" title="Limpiar formato" /></span>
        </div>
        <div class="flex flex-wrap gap-2 border-x border-surface-300 bg-surface-50 p-2 dark:bg-surface-900">
            <Button type="button" label="Deshacer" icon="pi pi-undo" text size="small" @click="quill?.history.undo()" />
            <Button type="button" label="Rehacer" icon="pi pi-refresh" text size="small" @click="quill?.history.redo()" />
            <Button type="button" label="Imagen" icon="pi pi-image" text size="small" @mousedown.prevent @click="openLibrary" />
            <Button v-if="section === 'contenido_html'" type="button" label="Salto de página" icon="pi pi-file" text size="small" @click="insert(true, 'pageBreak')" />
        </div>
        <div ref="host" />
        <div v-if="selectedImage" class="flex flex-wrap items-end gap-3 border border-surface-200 p-3">
            <label class="text-sm">Ancho de imagen (px)<input v-model="imageWidth" type="number" min="24" max="650" class="ml-2 w-24 rounded border p-2" @change="updateImage" /></label>
            <label class="text-sm">Texto alternativo<input v-model="imageAlt" maxlength="160" class="ml-2 rounded border p-2" @change="updateImage" /></label>
            <span class="text-xs text-surface-500">Usa la alineación del párrafo para colocar la imagen.</span>
        </div>
        <DocumentImageLibrary v-model:visible="library" @select="chooseImage" />
    </div>
</template>

<style>
@font-face { font-family: 'Document Sans'; src: url('/fonts/liberation/LiberationSans-Regular.ttf'); }
@font-face { font-family: 'Document Sans'; src: url('/fonts/liberation/LiberationSans-Bold.ttf'); font-weight: 700; }
@font-face { font-family: 'Document Sans'; src: url('/fonts/liberation/LiberationSans-Italic.ttf'); font-style: italic; }
@font-face { font-family: 'Document Sans'; src: url('/fonts/liberation/LiberationSans-BoldItalic.ttf'); font-weight: 700; font-style: italic; }
@font-face { font-family: 'Document Serif'; src: url('/fonts/liberation/LiberationSerif-Regular.ttf'); }
@font-face { font-family: 'Document Serif'; src: url('/fonts/liberation/LiberationSerif-Bold.ttf'); font-weight: 700; }
@font-face { font-family: 'Document Serif'; src: url('/fonts/liberation/LiberationSerif-Italic.ttf'); font-style: italic; }
@font-face { font-family: 'Document Serif'; src: url('/fonts/liberation/LiberationSerif-BoldItalic.ttf'); font-weight: 700; font-style: italic; }
@font-face { font-family: 'Document Mono'; src: url('/fonts/liberation/LiberationMono-Regular.ttf'); }
@font-face { font-family: 'Document Mono'; src: url('/fonts/liberation/LiberationMono-Bold.ttf'); font-weight: 700; }
@font-face { font-family: 'Document Mono'; src: url('/fonts/liberation/LiberationMono-Italic.ttf'); font-style: italic; }
@font-face { font-family: 'Document Mono'; src: url('/fonts/liberation/LiberationMono-BoldItalic.ttf'); font-weight: 700; font-style: italic; }
.document-editor .ql-editor { min-height: 24rem; max-height: 55vh; background: white; }
.document-editor .ql-editor .document-page-break { height: 1.6rem; border-bottom: 2px dashed #94a3b8; margin: 1rem 0; }
.document-editor .ql-editor .document-page-break::before { content: 'Salto de página'; color: #64748b; font-size: 11px; }
.document-editor .ql-editor li[data-list] { list-style-type: none; }
.document-editor .ql-editor li[data-list='bullet'] > .ql-ui::before { content: '\2022'; }
.document-editor .ql-picker.ql-size .ql-picker-label::before, .document-editor .ql-picker.ql-size .ql-picker-item::before { content: attr(data-value); }
.document-editor .ql-picker.ql-font .ql-picker-label::before, .document-editor .ql-picker.ql-font .ql-picker-item::before { content: 'Sans'; }
.document-editor .ql-picker.ql-font [data-value='serif']::before { content: 'Serif'; }
.document-editor .ql-picker.ql-font [data-value='monospace']::before { content: 'Mono'; }
.document-editor .ql-picker.ql-header .ql-picker-label::before, .document-editor .ql-picker.ql-header .ql-picker-item::before { content: 'Texto normal'; }
.document-editor .ql-picker.ql-header [data-value='1']::before { content: 'Título 1'; }
.document-editor .ql-picker.ql-header [data-value='2']::before { content: 'Título 2'; }
.document-editor .ql-picker.ql-header [data-value='3']::before { content: 'Título 3'; }
.document-editor .ql-picker-options { z-index: 20; }
.document-editor .ql-picker.ql-header { width: 124px; }
</style>
