<script setup>
import { onBeforeUnmount, ref, watch } from "vue";
import axios from "axios";
import MenubarModel from "./MenubarModel.vue";
const props = defineProps({ screens: { type: Array, default: () => [] }, itemId: Number, draft: Object, readonly: Boolean });
const screen = ref("");
const items = ref(null);
const loading = ref(false);
const error = ref("");
let controller;
let revision = 0;
const invalidate = () => { revision++; controller?.abort(); loading.value = false; items.value = null; error.value = ""; };
watch(() => [props.draft, props.itemId, props.readonly, props.screens], invalidate, { deep: true });
watch(screen, invalidate);
onBeforeUnmount(invalidate);
async function preview() {
    invalidate();
    if (!screen.value) { error.value = "Selecciona una pantalla para ver el menú."; return; }
    const current = revision;
    controller = new AbortController();
    loading.value = true;
    try {
        const { data } = await axios.post(route("admin.menubar-items.previsualizacion"), {
            screen: screen.value, selected_item_id: props.itemId ?? null,
            ...(props.readonly ? {} : { draft: props.draft }),
        }, { signal: controller.signal });
        if (current === revision) items.value = data.items;
    } catch (exception) {
        if (current !== revision || axios.isCancel(exception)) return;
        error.value = Object.values(exception.response?.data?.errors ?? {}).flat().join(" ") || "No pudimos cargar la vista previa. Inténtalo de nuevo.";
    } finally { if (current === revision) loading.value = false; }
}
</script>
<template>
    <section class="rounded-lg border border-surface-200 p-3 dark:border-surface-700" aria-label="Vista previa">
        <h3 class="font-medium">Vista previa sin guardar</h3>
        <p class="mb-3 mt-1 text-sm text-surface-500">Abre los grupos para revisar su organización. Las acciones no navegan ni ejecutan operaciones.</p>
        <label for="menubar-preview-screen" class="mb-1 block text-sm">Pantalla de vista previa</label>
        <Select inputId="menubar-preview-screen" v-model="screen" :options="screens" optionLabel="label" optionValue="key" filter fluid placeholder="Selecciona una pantalla" />
        <Button type="button" label="Ver vista previa" icon="pi pi-eye" severity="secondary" outlined size="small" class="my-3" :loading="loading" @click="preview" />
        <Message v-if="error" severity="error" role="alert">{{ error }}</Message>
        <div aria-live="polite" :aria-busy="loading">
            <span v-if="loading">Cargando vista previa…</span>
            <MenubarModel v-else-if="items?.length" :model="items" preview />
            <p v-else-if="items" class="text-sm">No hay acciones disponibles para esta pantalla y tu perfil. Revisa dónde aparecen y el contexto requerido.</p>
        </div>
    </section>
</template>
