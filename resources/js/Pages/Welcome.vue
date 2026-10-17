<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import { useScrollReveal } from "@/Composables/useScrollReveal";
import DoodleUnderline from "@/Components/Landing/DoodleUnderline.vue";
import HeroParticleBurst from "@/Components/Landing/HeroParticleBurst.vue";
import ModuleShowcase from "@/Components/Landing/ModuleShowcase.vue";
import ModuleDirectory from "@/Components/Landing/ModuleDirectory.vue";
import SicShowcase from "@/Components/Landing/SicShowcase.vue";
import ImageViewer from "@/Components/Landing/ImageViewer.vue";
import { featuredModules, accessModule, sicShots } from "@/Components/Landing/modules";
import "../../css/landing-modules.css";

defineProps({ canLogin: Boolean });
const page = usePage();
const landing = ref(null);
useScrollReveal(landing);
const typedLine = ref("");
const fullLine = "en cada paso.";
let typingTimer;
onMounted(() => {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        typedLine.value = fullLine;
        return;
    }
    let character = 0;
    typingTimer = window.setInterval(() => {
        typedLine.value = fullLine.slice(0, ++character);
        if (character === fullLine.length) window.clearInterval(typingTimer);
    }, 90);
});
onUnmounted(() => {
    window.clearInterval(typingTimer);
});
const destination = () => (page.props.auth?.user ? "dashboard" : "login");
const viewerVisible = ref(false);
const viewerImages = ref([]);
const viewerIndex = ref(0);
function openImage(shot) {
    viewerImages.value = [shot];
    viewerIndex.value = 0;
    viewerVisible.value = true;
}
function openSicGallery(index) {
    viewerImages.value = sicShots;
    viewerIndex.value = index;
    viewerVisible.value = true;
}
const journey = [
    {
        number: "01",
        title: "Prepara",
        text: "Productos, políticas y documentos listos para operar.",
        icon: "pi pi-sliders-h",
    },
    {
        number: "02",
        title: "Origina",
        text: "Clientes y expedientes conectados con cada solicitud.",
        icon: "pi pi-folder-open",
    },
    {
        number: "03",
        title: "Resuelve",
        text: "Revisión, decisiones y formalización con contexto.",
        icon: "pi pi-check-circle",
    },
    {
        number: "04",
        title: "Acompaña",
        text: "Créditos, movimientos y pagos en un mismo recorrido.",
        icon: "pi pi-chart-line",
    },
];
</script>

<template>
    <Head title="Kronik · Gestión del crédito" />
    <div ref="landing" class="k-public k-landing">
        <header class="k-public-nav">
            <Link href="/" class="k-public-brand" aria-label="Kronik, inicio"
                ><span class="k-brand-mark">K</span><span>Kronik</span></Link
            >
            <nav aria-label="Acceso" class="k-landing-nav-links">
                <a href="#producto" class="k-landing-nav-section">Plataforma</a
                ><a href="#sic" class="k-landing-nav-section">SIC</a
                ><a href="#accesos" class="k-landing-nav-section">Equipo</a
                ><Link v-if="canLogin" :href="route(destination())" class="k-public-login"
                    >{{ destination() === "dashboard" ? "Ir al tablero" : "Iniciar sesión" }}
                    <i class="pi pi-arrow-right" aria-hidden="true"
                /></Link>
            </nav>
        </header>
        <main>
            <section class="k-landing-hero">
                <HeroParticleBurst />
                <div class="k-landing-intro">
                    <p class="k-eyebrow">Un espacio para todo el recorrido</p>
                    <h1 aria-label="Crédito claro, en cada paso.">
                        Crédito claro,<span class="k-hero-type-line" aria-hidden="true"
                            ><span class="k-hero-type-reserve">{{ fullLine }}</span
                            ><em class="k-display-accent k-hero-typed"
                                >{{ typedLine }}<span class="k-hero-caret" /></em
                        ></span>
                    </h1>
                    <p class="k-landing-lead">
                        De la primera ficha del cliente al seguimiento de sus pagos: tu equipo
                        trabaja con el mismo contexto, las mismas reglas y un siguiente paso
                        visible.
                    </p>
                    <div class="k-landing-actions">
                        <Link
                            v-if="canLogin"
                            :href="route(destination())"
                            class="k-action-link k-hero-primary"
                            >{{
                                destination() === "dashboard"
                                    ? "Abrir mi espacio"
                                    : "Entrar a Kronik"
                            }}
                            <i class="pi pi-arrow-right" aria-hidden="true" /></Link
                        ><a href="#recorrido" class="k-landing-secondary"
                            >Conoce el recorrido <i class="pi pi-arrow-down" aria-hidden="true"
                        /></a>
                    </div>
                    <p class="k-landing-note">
                        <i class="pi pi-lock" aria-hidden="true" /> Cada función tiene su lugar.
                        Cada decisión conserva su contexto.
                    </p>
                </div>
                <div
                    class="k-landing-scene"
                    aria-label="Vista conceptual del recorrido del crédito"
                >
                    <div class="k-landing-window">
                        <div class="k-landing-window-bar">
                            <span class="k-brand-mark">K</span><span>Espacio de trabajo</span
                            ><span class="k-public-dot" />
                        </div>
                        <div class="k-landing-window-body">
                            <p class="k-eyebrow">Vista de la operación</p>
                            <h2>Todo avanza con claridad.</h2>
                            <div class="k-landing-progress"><span style="width: 72%" /></div>
                            <div class="k-landing-preview-row">
                                <span class="k-landing-preview-icon"
                                    ><i class="pi pi-user" aria-hidden="true" /></span
                                ><span
                                    ><strong>Cliente y expediente</strong
                                    ><small>Información conectada</small></span
                                ><i class="pi pi-check-circle" aria-hidden="true" />
                            </div>
                            <div class="k-landing-preview-row">
                                <span class="k-landing-preview-icon"
                                    ><i class="pi pi-file-edit" aria-hidden="true" /></span
                                ><span
                                    ><strong>Solicitud</strong
                                    ><small>Un siguiente paso visible</small></span
                                ><i class="pi pi-arrow-right" aria-hidden="true" />
                            </div>
                            <div class="k-landing-preview-row">
                                <span class="k-landing-preview-icon"
                                    ><i class="pi pi-wallet" aria-hidden="true" /></span
                                ><span
                                    ><strong>Crédito y pagos</strong
                                    ><small>Seguimiento continuo</small></span
                                ><i class="pi pi-clock" aria-hidden="true" />
                            </div>
                        </div>
                    </div>
                    <div class="k-landing-float">
                        <i class="pi pi-sparkles" aria-hidden="true" /><span
                            >Una operación, una identidad.</span
                        >
                    </div>
                </div>
            </section>
            <section id="recorrido" class="k-landing-flow" aria-labelledby="journey-title">
                <div class="k-landing-section-head" data-reveal>
                    <p class="k-eyebrow">El recorrido</p>
                    <h2 id="journey-title">
                        Del primer dato al
                        <em class="k-display-accent"
                            ><DoodleUnderline>siguiente paso.</DoodleUnderline></em
                        >
                    </h2>
                    <p>Una forma de trabajar que une configuración, operación y seguimiento.</p>
                </div>
                <ol class="k-landing-flow-grid">
                    <li v-for="step in journey" :key="step.number" data-reveal>
                        <span class="k-landing-flow-number">{{ step.number }}</span
                        ><i :class="step.icon" aria-hidden="true" />
                        <h3>{{ step.title }}</h3>
                        <p>{{ step.text }}</p>
                    </li>
                </ol>
            </section>
            <section class="k-landing-photo-break" aria-labelledby="photo-break-title" data-reveal>
                <div class="k-landing-photo-break-content">
                    <p class="k-eyebrow">Un mismo contexto</p>
                    <h2 id="photo-break-title">
                        Cada detalle cuenta.<br /><em>Todo queda conectado.</em>
                    </h2>
                    <p>
                        Del documento a la decisión, la información acompaña el trabajo de tu
                        equipo.
                    </p>
                </div>
            </section>
            <section id="producto" class="k-landing-gallery" aria-labelledby="gallery-title">
                <div class="k-landing-section-head" data-reveal>
                    <p class="k-eyebrow">Kronik por dentro</p>
                    <h2 id="gallery-title">
                        Cada pantalla tiene
                        <em class="k-display-accent"
                            ><DoodleUnderline>su propósito.</DoodleUnderline></em
                        >
                    </h2>
                    <p>
                        Conoce qué hace cada módulo y cómo se usa. Capturas del entorno de prueba
                        con datos sintéticos u ocultos; toca una imagen para explorarla.
                    </p>
                </div>

                <ModuleShowcase
                    v-for="(module, index) in featuredModules"
                    :key="module.id"
                    :module="module"
                    :reverse="index % 2 === 1"
                    @open-image="openImage"
                />
                <SicShowcase @open-gallery="openSicGallery" />
                <ModuleShowcase :module="accessModule" @open-image="openImage" />
                <ModuleDirectory />
            </section>
            <section class="k-landing-close k-glow-frame" data-reveal>
                <div>
                    <p class="k-eyebrow">El siguiente paso</p>
                    <h2>
                        Haz espacio para
                        <em class="k-display-accent"
                            ><DoodleUnderline>lo que sigue.</DoodleUnderline></em
                        >
                    </h2>
                    <p>
                        Un mismo lugar para preparar, decidir y acompañar cada crédito con claridad.
                    </p>
                </div>
                <svg class="k-landing-arrow" viewBox="0 0 180 120" fill="none" aria-hidden="true">
                    <path
                        d="M9 18C21 87 80 84 91 39c8-36-45-40-38-4 7 39 73 43 111 37m-27-17 29 17-23 22"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    /></svg
                ><Link v-if="canLogin" :href="route(destination())" class="k-landing-close-cta"
                    >{{ destination() === "dashboard" ? "Ir al tablero" : "Entrar a Kronik" }}
                    <i class="pi pi-arrow-up-right" aria-hidden="true"
                /></Link>
            </section>
        </main>
        <ImageViewer
            v-model:visible="viewerVisible"
            v-model:index="viewerIndex"
            :images="viewerImages"
        />
        <footer class="k-landing-footer">
            <div class="k-landing-footer-inner">
                <div class="k-landing-footer-top">
                    <div>
                        <p class="k-eyebrow">Kronik</p>
                        <h2>Más claridad.<br /><em>Mejores decisiones.</em></h2>
                    </div>
                    <p>
                        Una plataforma para conectar personas, documentos, decisiones y pagos en un
                        solo recorrido.
                    </p>
                </div>
                <div class="k-landing-footer-bottom">
                    <Link href="/" class="k-public-brand" aria-label="Kronik, inicio"
                        ><span class="k-brand-mark">K</span><span>Kronik</span></Link
                    >
                    <nav aria-label="Enlaces de la portada">
                        <a href="#recorrido">Recorrido</a><a href="#producto">La plataforma</a
                        ><a href="#sic">SIC</a><a href="#accesos">Roles y permisos</a
                        ><a href="#modulos">Módulos</a
                        ><Link v-if="canLogin" :href="route(destination())">{{
                            destination() === "dashboard" ? "Tablero" : "Acceso"
                        }}</Link>
                    </nav>
                    <span>© {{ new Date().getFullYear() }} Kronik</span>
                </div>
            </div>
        </footer>
    </div>
</template>
