<script setup>
import axios from "axios";
import { ref, watch, onBeforeUnmount } from "vue";
const props = defineProps({ visible: Boolean });
const emit = defineEmits(["update:visible", "select"]);
const resources = ref([]);
const query = ref("");
const currentPage = ref(1);
const lastPage = ref(1);
const loading = ref(false);
const uploading = ref(false);
const error = ref("");
let request;
let uploadRequest;
async function load(page = 1) {
    request?.abort();
    const controller = new AbortController();
    request = controller;
    loading.value = true;
    error.value = "";
    try {
        const { data } = await axios.get(route("documento-recursos.index"), { params: { q: query.value, page }, signal: controller.signal });
        resources.value = data.data;
        currentPage.value = data.current_page;
        lastPage.value = data.last_page;
    } catch (e) {
        if (!axios.isCancel(e)) error.value = "No fue posible cargar la biblioteca. Inténtalo nuevamente.";
    } finally { if (request === controller) loading.value = false; }
}
async function upload(event) {
    const file = event.target.files?.[0];
    event.target.value = "";
    if (!file) return;
    error.value = "";
    if (!/\.(png|jpe?g)$/i.test(file.name) || file.size > 2097152) {
        error.value = "Selecciona una imagen PNG o JPG de hasta 2 MB.";
        return;
    }
    const body = new FormData(); body.append("archivo", file);
    uploading.value = true;
    const controller = new AbortController();
    uploadRequest = controller;
    try {
        const { data } = await axios.post(route("documento-recursos.store"), body, { signal: controller.signal });
        if (controller.signal.aborted || !props.visible) return;
        emit("select", data);
        emit("update:visible", false);
    } catch (e) {
        if (!axios.isCancel(e)) error.value = Object.values(e.response?.data?.errors ?? {}).flat()[0] ?? e.response?.data?.message ?? "No fue posible subir la imagen.";
    } finally { if (uploadRequest === controller) uploading.value = false; }
}
watch(() => props.visible, visible => { if (visible) load(); else { request?.abort(); uploadRequest?.abort(); } });
onBeforeUnmount(() => { request?.abort(); uploadRequest?.abort(); });
</script>

<template>
    <Dialog :visible="visible" modal header="Biblioteca de imágenes" :style="{ width: 'min(850px, 96vw)' }" @update:visible="emit('update:visible', $event)">
        <p class="mb-4 text-sm text-surface-500">Reutiliza logotipos e imágenes en tus plantillas. PNG o JPG, hasta 2 MB y 2048 × 2048 píxeles.</p>
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <InputText v-model="query" aria-label="Buscar imágenes" placeholder="Buscar imagen" @keydown.enter.prevent="load()" />
            <Button label="Buscar" icon="pi pi-search" :loading="loading" @click="load()" />
            <label class="cursor-pointer rounded-lg border border-primary px-3 py-2 text-primary" :class="uploading && 'pointer-events-none opacity-50'">{{ uploading ? 'Subiendo…' : 'Subir imagen' }}<input class="sr-only" type="file" accept=".png,.jpg,.jpeg" aria-label="Subir imagen a la biblioteca" :disabled="uploading" @change="upload" /></label>
        </div>
        <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
        <p v-if="loading" role="status" class="p-8 text-center">Cargando imágenes…</p>
        <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <button v-for="resource in resources" :key="resource.id" type="button" class="min-w-0 rounded-xl border border-surface-200 p-3 text-left hover:border-primary focus-visible:outline-primary" :aria-label="`Insertar ${resource.nombre}`" @click="emit('select', resource); emit('update:visible', false)">
                <img :src="route('documento-recursos.show', resource.id)" :alt="resource.nombre" class="h-28 w-full bg-surface-100 object-contain" loading="lazy" />
                <span class="mt-2 block break-words text-sm font-semibold">{{ resource.nombre }}</span>
                <span class="text-xs text-surface-500">{{ resource.ancho }} × {{ resource.alto }} px</span>
            </button>
        </div>
        <p v-if="!loading && !resources.length" class="p-8 text-center text-surface-500">No hay imágenes. Sube la primera o cambia la búsqueda.</p>
        <div class="mt-4 flex justify-end gap-2"><Button label="Anterior" severity="secondary" :disabled="loading || currentPage <= 1" @click="load(currentPage - 1)" /><span class="p-2">{{ currentPage }} / {{ lastPage }}</span><Button label="Siguiente" severity="secondary" :disabled="loading || currentPage >= lastPage" @click="load(currentPage + 1)" /></div>
    </Dialog>
</template>
