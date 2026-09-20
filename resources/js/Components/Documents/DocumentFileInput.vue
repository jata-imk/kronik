<script setup>
import { ref, watch } from "vue";
const props = defineProps({ modelValue: { default: null }, error: { type: String, default: "" } });
const emit = defineEmits(["update:modelValue", "clear-error"]);
const input = ref(null);
const localError = ref("");
function clear() {
    emit("update:modelValue", null); emit("clear-error"); localError.value = "";
    if (input.value) input.value.value = "";
}
function select(event) {
    const file = event.target.files?.[0];
    clear();
    if (!file) return;
    if (!/\.(pdf|png|jpe?g)$/i.test(file.name)) localError.value = "Selecciona un archivo PDF, JPG o PNG.";
    else if (file.size > 10485760) localError.value = "El archivo no debe superar 10 MB.";
    else emit("update:modelValue", file);
}
watch(() => props.modelValue, value => { if (!value && input.value) input.value.value = ""; });
</script>
<template>
    <div class="min-w-0 space-y-2">
        <input ref="input" type="file" accept=".pdf,.png,.jpg,.jpeg" class="sr-only" aria-label="Seleccionar PDF o imagen" @change="select" />
        <div class="flex min-w-0 flex-wrap items-center gap-2"><Button type="button" label="Seleccionar PDF o imagen" icon="pi pi-upload" @click="input.click()" /><Button v-if="modelValue" type="button" label="Quitar selección" severity="secondary" text @click="clear" /></div>
        <p v-if="modelValue" class="break-all text-sm">{{ modelValue.name }}</p>
        <p v-else class="text-sm text-surface-500">Sin archivo seleccionado. PDF, JPG o PNG de hasta 10 MB.</p>
        <div class="min-h-6"><Message v-if="localError || error" severity="error" size="small" :closable="false" class="break-words">{{ localError || error }}</Message></div>
    </div>
</template>
