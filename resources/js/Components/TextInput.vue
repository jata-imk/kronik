<script setup>
import { onMounted, ref } from "vue";

defineProps({
    modelValue: String,
});

defineEmits(["update:modelValue"]);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute("autofocus")) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        class="rounded-lg border-slate-300 bg-white text-slate-900 placeholder-slate-500 shadow-sm focus:border-primary focus:ring-primary dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
