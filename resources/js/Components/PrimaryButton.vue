<script setup>
defineProps({
    type: {
        type: String,
        default: "submit",
    },
});
</script>

<template>
    <button :type="type" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-transparent bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50">
        <slot />
    </button>
</template>
