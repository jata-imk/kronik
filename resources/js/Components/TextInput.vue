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
        class="border-gray-300 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-90 dark:text-gray-100 placeholder-gray-500 k:placeholder-gray-40"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
