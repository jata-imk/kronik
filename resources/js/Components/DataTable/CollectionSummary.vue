<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";

const props = defineProps({
    items: { type: Array, default: () => [] },
    emptyLabel: { type: String, default: "—" },
    maxWidth: { type: String, default: "16rem" },
    appearance: { type: String, default: "chip" },
    tooltip: { type: String, default: "" },
});

const labels = computed(() => props.items.filter(Boolean).map(String));
const fullLabel = computed(
    () => props.tooltip || labels.value.join(", ") || props.emptyLabel,
);
const element = ref(null);
const isOverflowing = ref(false);
const showTooltip = computed(
    () => labels.value.length > 1 || isOverflowing.value,
);
let observer;

const updateOverflow = () => {
    const label = element.value?.querySelector(".p-chip-label, .p-tag-label");
    isOverflowing.value = Boolean(
        label && label.scrollWidth > label.clientWidth,
    );
};

onMounted(() => {
    nextTick(updateOverflow);
    if (typeof ResizeObserver !== "undefined") {
        observer = new ResizeObserver(updateOverflow);
        observer.observe(element.value);
    }
});
onBeforeUnmount(() => observer?.disconnect());
watch(labels, () => nextTick(updateOverflow));
</script>

<template>
    <div ref="element" class="collection-summary inline-flex w-full min-w-0 items-center gap-1 whitespace-nowrap" :style="{ maxWidth }" v-tooltip.top="showTooltip ? fullLabel : null">
        <template v-if="labels.length">
            <Tag
                v-if="appearance === 'contrast'"
                :value="labels[0]"
                severity="contrast"
                class="collection-chip min-w-0 whitespace-nowrap"
                :pt="{ label: { class: 'block truncate' } }"
            />
            <Chip v-else :label="labels[0]" class="collection-chip min-w-0" />
            <Badge v-if="labels.length > 1" class="collection-count" :value="`+${labels.length - 1}`" severity="secondary" />
        </template>
        <span v-else class="text-surface-500">{{ emptyLabel }}</span>
    </div>
</template>

<style scoped>
.collection-chip {
    flex: 0 1 auto;
    max-width: calc(100% - 2rem);
}

.collection-chip:only-child {
    max-width: 100%;
}

:deep(.collection-chip .p-chip-label) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.collection-count {
    flex: 0 0 auto;
}
</style>
