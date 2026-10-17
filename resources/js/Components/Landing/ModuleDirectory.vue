<script setup>
import { supportingModules } from "./modules";
import DoodleUnderline from "./DoodleUnderline.vue";
</script>

<template>
    <section id="modulos" class="k-module-directory" aria-labelledby="modules-title">
        <div class="k-landing-section-head" data-reveal>
            <p class="k-eyebrow">Cada parte de tu operación</p>
            <h2 id="modules-title">
                Conoce las piezas.<br /><em class="k-display-accent"
                    ><DoodleUnderline>Conecta el recorrido.</DoodleUnderline></em
                >
            </h2>
            <p>
                Desde el expediente hasta la organización de tu equipo. Abre un módulo para ver cómo
                se usa.
            </p>
        </div>
        <div class="k-module-grid">
            <article
                v-for="module in supportingModules"
                :key="module.id"
                class="k-module-card"
                data-reveal
            >
                <i :class="module.icon" class="k-module-icon" aria-hidden="true" />
                <h3>{{ module.name }}</h3>
                <p>{{ module.description }}</p>
                <details>
                    <summary>
                        Cómo se usa <span class="sr-only">{{ module.name }}</span
                        ><i class="pi pi-plus" aria-hidden="true" />
                    </summary>
                    <ol>
                        <li v-for="step in module.steps" :key="step">{{ step }}</li>
                    </ol>
                </details>
            </article>
        </div>
    </section>
</template>
