<script setup>
defineProps({
    value: { type: [String, Number], default: "—" },
    maxWidth: { type: String, default: "16rem" },
});
</script>

<template>
    <span
        class="-my-px block overflow-hidden text-ellipsis whitespace-nowrap py-px leading-normal"
        :style="{ maxWidth }"
    >
        {{ value ?? "—" }}
    </span>
</template>
