<script setup>
import Dialog from "primevue/dialog";
import { computed, ref, watch } from "vue";

const props = defineProps({
    visible: Boolean,
    images: { type: Array, default: () => [] },
    index: { type: Number, default: 0 },
});
const emit = defineEmits(["update:visible", "update:index"]);
const current = computed(() => props.images[props.index]);
const enlarged = ref(false);
watch([() => props.index, () => props.visible], () => {
    enlarged.value = false;
});
function move(direction) {
    if (props.images.length > 1)
        emit("update:index", (props.index + direction + props.images.length) % props.images.length);
}
function onArrow(event, direction) {
    // A focused, enlarged image uses native arrow scrolling within its viewport.
    if (enlarged.value && event.target.classList.contains("k-image-viewer-stage")) return;
    event.preventDefault();
    move(direction);
}
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        :header="current?.title"
        modal
        block-scroll
        dismissable-mask
        class="k-image-viewer"
        :close-button-props="{ 'aria-label': 'Cerrar captura' }"
        @keydown.left="onArrow($event, -1)"
        @keydown.right="onArrow($event, 1)"
    >
        <figure v-if="current" class="k-image-viewer-figure">
            <div
                class="k-image-viewer-stage"
                :class="{ 'is-enlarged': enlarged }"
                :style="{ '--capture-width': `${current.width}px` }"
                :tabindex="enlarged ? 0 : undefined"
                :aria-label="enlarged ? 'Captura ampliada; desplázate para recorrerla' : undefined"
            >
                <img
                    :src="current.src"
                    :alt="current.alt"
                    :width="current.width"
                    :height="current.height"
                />
            </div>
            <figcaption>{{ current.caption }}</figcaption>
        </figure>
        <template #footer>
            <div class="k-image-viewer-nav">
                <button
                    v-if="images.length > 1"
                    type="button"
                    aria-label="Captura anterior"
                    @click="move(-1)"
                >
                    <i class="pi pi-arrow-left" aria-hidden="true" />
                </button>
                <span v-if="images.length > 1" aria-live="polite" aria-atomic="true"
                    >{{ index + 1 }} / {{ images.length }}</span
                >
                <button
                    v-if="images.length > 1"
                    type="button"
                    aria-label="Captura siguiente"
                    @click="move(1)"
                >
                    <i class="pi pi-arrow-right" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    class="k-image-viewer-zoom"
                    :aria-pressed="enlarged"
                    @click="enlarged = !enlarged"
                >
                    <i
                        :class="enlarged ? 'pi pi-search-minus' : 'pi pi-search-plus'"
                        aria-hidden="true"
                    />{{ enlarged ? "Ajustar captura" : "Acercar captura" }}
                </button>
            </div>
        </template>
    </Dialog>
</template>
