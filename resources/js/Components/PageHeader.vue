<script setup>
defineProps({
    eyebrow: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, default: "" },
});
</script>

<template>
    <header class="k-module-header">
        <div class="k-module-header-copy">
            <p class="k-eyebrow">{{ eyebrow }}</p>
            <h1 class="k-page-title">{{ title }}</h1>
            <p v-if="description" class="k-page-description">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="k-module-header-actions">
            <slot name="actions" />
        </div>
    </header>
</template>
