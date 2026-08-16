<script setup>
import { ref } from "vue";

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    example: { type: String, default: "" },
    note: { type: String, default: "" },
});

const popover = ref();
</script>

<template>
    <Button
        type="button"
        icon="pi pi-question-circle"
        text
        rounded
        size="small"
        class="!h-7 !w-7"
        :aria-label="`Ayuda: ${title}`"
        @click="popover.toggle($event)"
    />
    <Popover ref="popover">
        <div class="max-w-80 space-y-2 text-sm">
            <p class="font-semibold text-surface-900 dark:text-surface-0">{{ title }}</p>
            <p class="leading-relaxed text-surface-600 dark:text-surface-300">{{ description }}</p>
            <p v-if="example" class="rounded-lg bg-surface-100 p-2 text-xs dark:bg-surface-800">
                <strong>Ejemplo:</strong> {{ example }}
            </p>
            <p v-if="note" class="text-xs text-surface-500"><i class="pi pi-info-circle mr-1" />{{ note }}</p>
        </div>
    </Popover>
</template>
