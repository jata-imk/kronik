<script setup>
import axios from "axios";
import { computed, onBeforeUnmount, ref, watch } from "vue";

const props = defineProps({
    visible: { type: Boolean, default: false },
    url: { type: String, default: "" },
    downloadUrl: { type: String, default: "" },
    name: { type: String, default: "Documento privado" },
});
const emit = defineEmits(["update:visible"]);
const loading = ref(false);
const error = ref("");
const objectUrl = ref("");
const mime = ref("");
const zoom = ref(1);
const rotation = ref(0);
const isPdf = computed(() => mime.value === "application/pdf");
const isImage = computed(() =>
    ["image/jpeg", "image/png"].includes(mime.value),
);

const release = () => {
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
    objectUrl.value = "";
    mime.value = "";
    zoom.value = 1;
    rotation.value = 0;
};
const load = async () => {
    release();
    error.value = "";
    if (!props.url) return;
    loading.value = true;
    try {
        const response = await axios.get(props.url, { responseType: "blob" });
        mime.value = response.data.type;
        if (
            !["application/pdf", "image/jpeg", "image/png"].includes(mime.value)
        ) {
            throw new Error("Formato no permitido");
        }
        objectUrl.value = URL.createObjectURL(response.data);
    } catch (_exception) {
        error.value =
            "No fue posible abrir el archivo. Verifica tus permisos o inténtalo nuevamente.";
    } finally {
        loading.value = false;
    }
};
watch(
    () => [props.visible, props.url],
    ([visible]) => (visible ? load() : release()),
);
onBeforeUnmount(release);
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        maximizable
        :draggable="false"
        class="private-viewer"
        :style="{ width: 'min(96vw, 1180px)' }"
        @update:visible="emit('update:visible', $event)"
    >
        <template #header>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <i class="pi pi-shield text-emerald-600" aria-hidden="true" />
                    <span class="font-semibold">Visor seguro</span>
                    <Tag value="Privado" severity="success" rounded />
                </div>
                <p class="mt-1 max-w-[65vw] truncate text-sm text-surface-500">{{ name }}</p>
            </div>
        </template>

        <div class="flex min-h-[65vh] flex-col overflow-hidden rounded-xl border border-surface-200 bg-surface-950">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/10 bg-surface-900 px-3 py-2">
                <span class="text-xs text-surface-300"><i class="pi pi-lock mr-2" />Acceso autorizado · sin URL pública</span>
                <div class="flex items-center gap-1">
                    <template v-if="isImage">
                        <Button icon="pi pi-minus" text rounded aria-label="Alejar" @click="zoom = Math.max(0.5, zoom - 0.25)" />
                        <span class="w-14 text-center text-xs text-white">{{ Math.round(zoom * 100) }}%</span>
                        <Button icon="pi pi-plus" text rounded aria-label="Acercar" @click="zoom = Math.min(3, zoom + 0.25)" />
                        <Button icon="pi pi-refresh" text rounded aria-label="Girar" @click="rotation = (rotation + 90) % 360" />
                    </template>
                    <Button v-if="downloadUrl" as="a" :href="downloadUrl" icon="pi pi-download" label="Descargar" size="small" severity="secondary" />
                </div>
            </div>
            <div class="relative flex flex-1 items-center justify-center overflow-auto p-3 sm:p-6">
                <div v-if="loading" class="w-full max-w-lg space-y-3" aria-live="polite">
                    <Skeleton height="2rem" />
                    <Skeleton height="24rem" />
                    <p class="text-center text-sm text-surface-300">Abriendo documento privado…</p>
                </div>
                <Message v-else-if="error" severity="error" :closable="false">{{ error }}</Message>
                <iframe v-else-if="isPdf && objectUrl" :src="objectUrl" :title="`PDF: ${name}`" class="h-[68vh] w-full rounded bg-white" />
                <img
                    v-else-if="isImage && objectUrl"
                    :src="objectUrl"
                    :alt="name"
                    class="max-h-[68vh] max-w-full rounded-lg bg-white object-contain shadow-2xl transition-transform"
                    :style="{ transform: `scale(${zoom}) rotate(${rotation}deg)` }"
                />
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
:deep(.p-dialog-content) { padding-top: 0.5rem; }
@media (max-width: 640px) {
    :deep(.p-dialog) { width: 100vw !important; height: 100dvh; max-height: 100dvh; margin: 0; border-radius: 0; }
    :deep(.p-dialog-content) { flex: 1; padding: 0.5rem; }
}
</style>
