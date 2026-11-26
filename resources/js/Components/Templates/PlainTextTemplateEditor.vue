<script setup>
import { nextTick, ref } from "vue";
const props = defineProps({ modelValue: { type: String, default: "" }, readonly: Boolean });
const emit = defineEmits(["update:modelValue"]);
const input = ref(null);
let selection = { start: 0, end: 0 };
function remember() {
    if (input.value) selection = { start: input.value.selectionStart, end: input.value.selectionEnd };
}
async function insertVariable(variable) {
    if (props.readonly) return;
    const value = variable.token ?? "{" + variable.clave + "}";
    const start = Math.min(selection.start, props.modelValue.length);
    const end = Math.min(selection.end, props.modelValue.length);
    emit("update:modelValue", props.modelValue.slice(0, start) + value + props.modelValue.slice(end));
    await nextTick();
    input.value?.focus();
    input.value?.setSelectionRange(start + value.length, start + value.length);
    remember();
}
defineExpose({ insertVariable });
</script>

<template>
    <label class="grid gap-1 text-sm">Contenido
        <textarea ref="input" :value="modelValue" :readonly="readonly" :aria-readonly="readonly" rows="10" class="w-full rounded border p-3 dark:bg-surface-900" @input="!readonly && emit('update:modelValue', $event.target.value)" @select="remember" @keyup="remember" @click="remember" @blur="remember" @drop.prevent @paste.capture="readonly && $event.preventDefault()" />
    </label>
</template>
