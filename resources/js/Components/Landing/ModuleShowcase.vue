<script setup>
defineProps({ module: { type: Object, required: true }, reverse: Boolean });
defineEmits(["open-image"]);
</script>

<template>
    <article
        :id="module.id"
        class="k-landing-showcase"
        :class="{ 'k-landing-showcase-reverse': reverse }"
        data-reveal
    >
        <div class="k-landing-showcase-copy">
            <span class="k-landing-flow-number">{{ module.stage }}</span>
            <h3>{{ module.title }}</h3>
            <p>{{ module.description }}</p>
            <span class="k-landing-showcase-label"
                ><i :class="module.icon" aria-hidden="true" /> {{ module.name }}</span
            >
            <ol class="k-module-steps" :aria-label="`Cómo usar ${module.name}`">
                <li v-for="(step, index) in module.steps" :key="step">
                    <span aria-hidden="true">{{ index + 1 }}</span
                    >{{ step }}
                </li>
            </ol>
        </div>
        <figure class="k-landing-shot">
            <button
                type="button"
                class="k-shot-button"
                :aria-label="`Ampliar captura: ${module.shot.title}`"
                @click="$emit('open-image', module.shot)"
            >
                <img
                    :src="module.shot.src"
                    :alt="module.shot.alt"
                    :width="module.shot.width"
                    :height="module.shot.height"
                    loading="lazy"
                    decoding="async"
                />
                <span class="k-shot-expand"
                    ><i class="pi pi-expand" aria-hidden="true" /> Ampliar</span
                >
            </button>
            <figcaption>{{ module.shot.caption }}</figcaption>
        </figure>
    </article>
</template>
