<script setup>
import { onMounted, onUnmounted, ref } from "vue";

const canvas = ref(null);
const turn = Math.PI * 2;
let seed = 82716;
function random() {
    seed = (1664525 * seed + 1013904223) >>> 0;
    return seed / 4294967296;
}
const particles = Array.from({ length: 560 }, () => ({
    angle: random() * turn,
    distance: Math.pow(random(), 0.9),
    drift: 0.08 + random() * 0.22,
    tone: Math.floor(random() * 11),
    size: 0.9 + random() * 0.8,
}));

let context;
let observer;
let motionPreference;
let frame;
let previousTime = 0;
let elapsed = 0;
let pulsePhase = -Math.PI / 2;
let width = 0;
let height = 0;
let radius = 0;
let center = { x: 0, y: 0, vx: 0, vy: 0 };
let target = { x: 0, y: 0 };

function trackPointer(event) {
    if (event.pointerType === "touch" || motionPreference?.matches || !canvas.value) return;
    const bounds = canvas.value.getBoundingClientRect();
    const hero = canvas.value.parentElement.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < hero.top || event.clientY > hero.bottom) return;
    target = { x: event.clientX - bounds.left, y: event.clientY - bounds.top };
}

function resize() {
    if (!canvas.value || !context) return;
    const bounds = canvas.value.getBoundingClientRect();
    const oldWidth = width;
    const oldHeight = height;
    width = bounds.width;
    height = bounds.height;
    radius = Math.hypot(width, height) * 0.62;
    const density = Math.min(window.devicePixelRatio || 1, 2);
    canvas.value.width = Math.round(width * density);
    canvas.value.height = Math.round(height * density);
    context.setTransform(density, 0, 0, density, 0, 0);
    if (oldWidth && oldHeight) {
        center.x *= width / oldWidth;
        center.y *= height / oldHeight;
        target.x *= width / oldWidth;
        target.y *= height / oldHeight;
    } else {
        center.x = width / 2;
        center.y = height / 2;
        target = { x: center.x, y: center.y };
    }
    if (motionPreference?.matches) draw();
}

function draw(time = 0) {
    if (!context || !canvas.value) return;
    context.clearRect(0, 0, width, height);
    const brand = getComputedStyle(canvas.value).color;
    const tones = [brand, brand, brand, brand, "#e0a150", "#bd79ad", "#6593d5"];
    const pulse = 1 + 0.16 * Math.sin(pulsePhase);
    for (const particle of particles) {
        const distance = (76 + particle.distance * radius) * pulse;
        const angle = particle.angle + time * particle.drift * 0.00006;
        const x = center.x + Math.cos(angle) * distance - center.vx * particle.distance * 0.4;
        const y = center.y + Math.sin(angle) * distance * 0.74 - center.vy * particle.distance * 0.4;
        if (x < -8 || x > width + 8 || y < -8 || y > height + 8) continue;
        const fade = Math.max(0, Math.min((distance - 60) / 95, 1)) * Math.max(0.35, 1 - distance / (radius * 1.5));
        context.globalAlpha = fade * 0.95;
        context.fillStyle = tones[particle.tone % tones.length];
        context.beginPath();
        context.arc(x, y, particle.size, 0, turn);
        context.fill();
    }
    context.globalAlpha = 1;
}

function animate(time) {
    const delta = Math.min((time - (previousTime || time)) / 16.67, 2);
    previousTime = time;
    elapsed += delta * 16.67;
    pulsePhase += delta * 0.026;
    center.vx = Math.max(-11, Math.min(11, (center.vx + (target.x - center.x) * 0.009 * delta) * Math.pow(0.9, delta)));
    center.vy = Math.max(-11, Math.min(11, (center.vy + (target.y - center.y) * 0.009 * delta) * Math.pow(0.9, delta)));
    center.x += center.vx * delta;
    center.y += center.vy * delta;
    draw(elapsed);
    frame = requestAnimationFrame(animate);
}

function updateMotion() {
    if (frame) cancelAnimationFrame(frame);
    frame = undefined;
    previousTime = 0;
    if (motionPreference?.matches) {
        center = { x: width / 2, y: height / 2, vx: 0, vy: 0 };
        target = { x: center.x, y: center.y };
        pulsePhase = -Math.PI / 2;
        draw(0);
    } else {
        frame = requestAnimationFrame(animate);
    }
}

onMounted(() => {
    context = canvas.value?.getContext("2d");
    motionPreference = window.matchMedia("(prefers-reduced-motion: reduce)");
    observer = new ResizeObserver(resize);
    observer.observe(canvas.value);
    motionPreference.addEventListener("change", updateMotion);
    window.addEventListener("pointermove", trackPointer, { passive: true });
    resize();
    updateMotion();
});

onUnmounted(() => {
    if (frame) cancelAnimationFrame(frame);
    observer?.disconnect();
    motionPreference?.removeEventListener("change", updateMotion);
    window.removeEventListener("pointermove", trackPointer);
});
</script>

<template>
    <canvas ref="canvas" class="k-hero-burst" aria-hidden="true" />
</template>
