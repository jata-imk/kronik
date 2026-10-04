<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import { useScrollReveal } from "@/Composables/useScrollReveal";
import DoodleUnderline from "@/Components/Landing/DoodleUnderline.vue";
import HeroParticleBurst from "@/Components/Landing/HeroParticleBurst.vue";

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
onUnmounted(() => window.clearInterval(typingTimer));
const destination = () => page.props.auth?.user ? "dashboard" : "login";
const journey = [
    { number: "01", title: "Prepara", text: "Productos, políticas y documentos listos para operar.", icon: "pi pi-sliders-h" },
    { number: "02", title: "Origina", text: "Clientes y expedientes conectados con cada solicitud.", icon: "pi pi-folder-open" },
    { number: "03", title: "Resuelve", text: "Revisión, decisiones y formalización con contexto.", icon: "pi pi-check-circle" },
    { number: "04", title: "Acompaña", text: "Créditos, movimientos y pagos en un mismo recorrido.", icon: "pi pi-chart-line" },
];
</script>

<template>
    <Head title="Kronik · Gestión del crédito" />
    <div ref="landing" class="k-public k-landing">
        <header class="k-public-nav">
            <Link href="/" class="k-public-brand" aria-label="Kronik, inicio"><span class="k-brand-mark">K</span><span>Kronik</span></Link>
            <nav aria-label="Acceso"><Link v-if="canLogin" :href="route(destination())" class="k-public-login">{{ destination() === 'dashboard' ? 'Ir al tablero' : 'Iniciar sesión' }} <i class="pi pi-arrow-right" aria-hidden="true" /></Link></nav>
        </header>
        <main>
            <section class="k-landing-hero">
                <HeroParticleBurst />
                <div class="k-landing-intro">
                    <p class="k-eyebrow">Un espacio para todo el recorrido</p>
                    <h1 aria-label="Crédito claro, en cada paso.">Crédito claro,<span class="k-hero-type-line" aria-hidden="true"><span class="k-hero-type-reserve">{{ fullLine }}</span><em class="k-display-accent k-hero-typed">{{ typedLine }}<span class="k-hero-caret" /></em></span></h1>
                    <p class="k-landing-lead">De la primera ficha del cliente al seguimiento de sus pagos: tu equipo trabaja con el mismo contexto, las mismas reglas y un siguiente paso visible.</p>
                    <div class="k-landing-actions"><Link v-if="canLogin" :href="route(destination())" class="k-action-link k-hero-primary">{{ destination() === 'dashboard' ? 'Abrir mi espacio' : 'Entrar a Kronik' }} <i class="pi pi-arrow-right" aria-hidden="true" /></Link><a href="#recorrido" class="k-landing-secondary">Conoce el recorrido <i class="pi pi-arrow-down" aria-hidden="true" /></a></div>
                    <p class="k-landing-note"><i class="pi pi-lock" aria-hidden="true" /> Cada función tiene su lugar. Cada decisión conserva su contexto.</p>
                </div>
                <div class="k-landing-scene" aria-label="Vista conceptual del recorrido del crédito">
                    <div class="k-landing-window">
                        <div class="k-landing-window-bar"><span class="k-brand-mark">K</span><span>Espacio de trabajo</span><span class="k-public-dot" /></div>
                        <div class="k-landing-window-body">
                            <p class="k-eyebrow">Vista de la operación</p>
                            <h2>Todo avanza con claridad.</h2>
                            <div class="k-landing-progress"><span style="width: 72%" /></div>
                            <div class="k-landing-preview-row"><span class="k-landing-preview-icon"><i class="pi pi-user" aria-hidden="true" /></span><span><strong>Cliente y expediente</strong><small>Información conectada</small></span><i class="pi pi-check-circle" aria-hidden="true" /></div>
                            <div class="k-landing-preview-row"><span class="k-landing-preview-icon"><i class="pi pi-file-edit" aria-hidden="true" /></span><span><strong>Solicitud</strong><small>Un siguiente paso visible</small></span><i class="pi pi-arrow-right" aria-hidden="true" /></div>
                            <div class="k-landing-preview-row"><span class="k-landing-preview-icon"><i class="pi pi-wallet" aria-hidden="true" /></span><span><strong>Crédito y pagos</strong><small>Seguimiento continuo</small></span><i class="pi pi-clock" aria-hidden="true" /></div>
                        </div>
                    </div>
                    <div class="k-landing-float"><i class="pi pi-sparkles" aria-hidden="true" /><span>Una operación, una identidad.</span></div>
                </div>
            </section>
            <section id="recorrido" class="k-landing-flow" aria-labelledby="journey-title">
                <div class="k-landing-section-head" data-reveal><p class="k-eyebrow">El recorrido</p><h2 id="journey-title">Del primer dato al <em class="k-display-accent"><DoodleUnderline>siguiente paso.</DoodleUnderline></em></h2><p>Una forma de trabajar que une configuración, operación y seguimiento.</p></div>
                <ol class="k-landing-flow-grid"><li v-for="step in journey" :key="step.number" data-reveal><span class="k-landing-flow-number">{{ step.number }}</span><i :class="step.icon" aria-hidden="true" /><h3>{{ step.title }}</h3><p>{{ step.text }}</p></li></ol>
            </section>
            <section class="k-landing-photo-break" aria-labelledby="photo-break-title" data-reveal>
                <div class="k-landing-photo-break-content"><p class="k-eyebrow">Un mismo contexto</p><h2 id="photo-break-title">Cada detalle cuenta.<br /><em>Todo queda conectado.</em></h2><p>Del documento a la decisión, la información acompaña el trabajo de tu equipo.</p></div>
            </section>
            <section id="producto" class="k-landing-gallery" aria-labelledby="gallery-title">
                <div class="k-landing-section-head" data-reveal><p class="k-eyebrow">Kronik por dentro</p><h2 id="gallery-title">Cada pantalla tiene <em class="k-display-accent"><DoodleUnderline>su propósito.</DoodleUnderline></em></h2><p>Vistas reales del entorno de prueba, con datos de las tablas ocultos en estas capturas.</p></div>
                <article class="k-landing-showcase" data-reveal><div class="k-landing-showcase-copy"><span class="k-landing-flow-number">01 / Preparar</span><h3>Define una base clara.</h3><p>Configura condiciones crediticias y conserva sus versiones antes de iniciar una solicitud.</p><span class="k-landing-showcase-label"><i class="pi pi-sliders-h" aria-hidden="true" /> Productos crediticios</span></div><figure class="k-landing-shot"><a href="/images/landing/productos.webp" target="_blank" rel="noopener noreferrer" aria-label="Ampliar captura de productos crediticios"><img src="/images/landing/productos.webp" alt="Vista del catálogo de productos crediticios y sus versiones" width="1061" height="620" loading="lazy" /></a><figcaption>Productos y versiones en un mismo contexto.</figcaption></figure></article>
                <article class="k-landing-showcase k-landing-showcase-reverse" data-reveal><div class="k-landing-showcase-copy"><span class="k-landing-flow-number">01 / Preparar</span><h3>Documentos listos para cada etapa.</h3><p>Plantillas, borradores y versiones con el mismo lenguaje visual que el resto de la operación.</p><span class="k-landing-showcase-label"><i class="pi pi-file-edit" aria-hidden="true" /> Documentos y plantillas</span></div><figure class="k-landing-shot"><a href="/images/landing/documentos.webp" target="_blank" rel="noopener noreferrer" aria-label="Ampliar captura de documentos y plantillas"><img src="/images/landing/documentos.webp" alt="Vista del centro documental con plantillas y versiones" width="1061" height="620" loading="lazy" /></a><figcaption>Una biblioteca para preparar y revisar.</figcaption></figure></article>
                <article class="k-landing-showcase" data-reveal><div class="k-landing-showcase-copy"><span class="k-landing-flow-number">02–03 / Originar y resolver</span><h3>El siguiente paso siempre a la vista.</h3><p>Sigue las solicitudes desde la captura hasta la formalización con filtros, estados y acciones contextualizadas.</p><span class="k-landing-showcase-label"><i class="pi pi-folder-open" aria-hidden="true" /> Solicitudes</span></div><figure class="k-landing-shot"><a href="/images/landing/solicitudes.webp" target="_blank" rel="noopener noreferrer" aria-label="Ampliar captura de solicitudes"><img src="/images/landing/solicitudes.webp" alt="Vista de solicitudes con filtros, estados y próximos pasos" width="1076" height="620" loading="lazy" /></a><figcaption>Consulta el estado sin perder el recorrido.</figcaption></figure></article>
                <article class="k-landing-showcase k-landing-showcase-reverse" data-reveal><div class="k-landing-showcase-copy"><span class="k-landing-flow-number">04 / Acompañar</span><h3>Continuidad después de la firma.</h3><p>Encuentra créditos y pagos en el mismo espacio, con una vista preparada para el seguimiento de cartera.</p><span class="k-landing-showcase-label"><i class="pi pi-wallet" aria-hidden="true" /> Créditos y pagos</span></div><figure class="k-landing-shot"><a href="/images/landing/creditos.webp" target="_blank" rel="noopener noreferrer" aria-label="Ampliar captura de créditos y pagos"><img src="/images/landing/creditos.webp" alt="Vista de créditos y pagos con resumen y filtros" width="1076" height="620" loading="lazy" /></a><figcaption>Seguimiento de cartera desde una vista clara.</figcaption></figure></article>
            </section>
            <section class="k-landing-close k-glow-frame" data-reveal><div><p class="k-eyebrow">El siguiente paso</p><h2>Haz espacio para <em class="k-display-accent"><DoodleUnderline>lo que sigue.</DoodleUnderline></em></h2><p>Un mismo lugar para preparar, decidir y acompañar cada crédito con claridad.</p></div><svg class="k-landing-arrow" viewBox="0 0 180 120" fill="none" aria-hidden="true"><path d="M9 18C21 87 80 84 91 39c8-36-45-40-38-4 7 39 73 43 111 37m-27-17 29 17-23 22" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" /></svg><Link v-if="canLogin" :href="route(destination())" class="k-landing-close-cta">{{ destination() === 'dashboard' ? 'Ir al tablero' : 'Entrar a Kronik' }} <i class="pi pi-arrow-up-right" aria-hidden="true" /></Link></section>
        </main>
        <footer class="k-landing-footer"><div class="k-landing-footer-inner"><div class="k-landing-footer-top"><div><p class="k-eyebrow">Kronik</p><h2>Más claridad.<br /><em>Mejores decisiones.</em></h2></div><p>Una plataforma para conectar personas, documentos, decisiones y pagos en un solo recorrido.</p></div><div class="k-landing-footer-bottom"><Link href="/" class="k-public-brand" aria-label="Kronik, inicio"><span class="k-brand-mark">K</span><span>Kronik</span></Link><nav aria-label="Enlaces de la portada"><a href="#recorrido">Recorrido</a><a href="#producto">La plataforma</a><Link v-if="canLogin" :href="route(destination())">{{ destination() === 'dashboard' ? 'Tablero' : 'Acceso' }}</Link></nav><span>© {{ new Date().getFullYear() }} Kronik</span></div></div></footer>
    </div>
</template>
