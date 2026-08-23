<script setup>
import { computed } from "vue";

const props = defineProps({
    state: { type: String, required: true },
    used: { type: Boolean, default: false },
});

const meta = computed(
    () =>
        ({
            borrador: ["Borrador", "secondary"],
            programada: ["Programada", "warn"],
            activa: ["Activa", "success"],
            retirada: ["Retirada", "contrast"],
        })[props.state] ?? [props.state, "secondary"],
);
</script>

<template>
    <div class="inline-flex flex-col items-start gap-1">
        <Tag :value="meta[0]" :severity="meta[1]" rounded />
        <span v-if="used" class="text-xs text-surface-500"><i class="pi pi-lock mr-1" />En uso</span>
    </div>
</template>
