<script setup>
import DoodleUnderline from "./DoodleUnderline.vue";
import { sicShots } from "./modules";
defineEmits(["open-gallery"]);
</script>

<template>
    <section id="sic" class="k-sic-showcase" aria-labelledby="sic-title">
        <div class="k-sic-intro" data-reveal>
            <div>
                <p class="k-eyebrow">Círculo de Crédito · SIC</p>
                <h2 id="sic-title">
                    Más contexto.<br /><em class="k-display-accent"
                        ><DoodleUnderline>Mejor lectura.</DoodleUnderline></em
                    >
                </h2>
            </div>
            <div>
                <span class="k-sic-availability"
                    ><span aria-hidden="true" /> Disponible en simulación</span
                >
                <p>
                    Del score al detalle del reporte: reúne la información disponible para que tu
                    equipo pueda revisarla y documentar su decisión.
                </p>
                <p class="k-sic-services">
                    FICO Score v2 <span aria-hidden="true">/</span> Fintech Score
                    <span aria-hidden="true">/</span> RC + FICO
                </p>
            </div>
        </div>
        <div class="k-sic-gallery">
            <figure
                v-for="(shot, index) in sicShots"
                :key="shot.src"
                class="k-sic-tile"
                :class="{ 'k-sic-tile-lead': index === 0 }"
                data-reveal
            >
                <button
                    type="button"
                    class="k-shot-button"
                    :aria-label="`Ampliar captura: ${shot.title}`"
                    @click="$emit('open-gallery', index)"
                >
                    <img
                        :src="shot.src"
                        :alt="shot.alt"
                        :width="shot.width"
                        :height="shot.height"
                        loading="lazy"
                        decoding="async"
                    />
                    <span class="k-shot-expand"
                        ><i class="pi pi-expand" aria-hidden="true" /> Explorar captura</span
                    >
                </button>
                <figcaption>
                    <span class="k-sic-tile-number">0{{ index + 1 }}</span>
                    <div>
                        <h3>{{ shot.title }}</h3>
                        <p>{{ shot.caption }}</p>
                    </div>
                </figcaption>
            </figure>
        </div>
        <ol class="k-sic-how" aria-label="Cómo consultar SIC" data-reveal>
            <li>
                <span>01</span>
                <h3>Prepara</h3>
                <p>
                    Completa el cliente y registra su consentimiento vigente. Vincula la revisión de
                    la solicitud cuando corresponda.
                </p>
            </li>
            <li>
                <span>02</span>
                <h3>Consulta</h3>
                <p>
                    Elige el servicio y sigue el estado del intento. Cada respuesta conserva su
                    fecha y procedencia.
                </p>
            </li>
            <li>
                <span>03</span>
                <h3>Interpreta</h3>
                <p>
                    Revisa score, razones y secciones presentes. Compara las gráficas con su detalle
                    antes de emitir tu dictamen.
                </p>
            </li>
        </ol>
        <p class="k-sic-footnote">
            Capturas con datos sintéticos. Las secciones dependen del servicio y de la respuesta; un
            score no aprueba por sí mismo un crédito.
        </p>
    </section>
</template>
