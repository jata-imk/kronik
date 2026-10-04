<script setup>
import { onMounted, onUnmounted, ref } from "vue";

const canvas = ref(null);
const particles = Array.from({ length: 420 }, (_, index) => ({
    angle: index * 2.399963229728653,
    distance: Math.sqrt((index + 0.5) / 420),
    speed: 0.28 + (index % 7) * 0.1,
    tone: index % 11,
    size: index % 9 === 0 ? 2.2 : 1.6,
}));

let context;
let observer;
let motionPreference;
let frame;
let previousTime = 0;
let width = 0;
let height = 0;
let radius = 0;
let center = { x: 0, y: 0, vx: 0, vy: 0 };
let target = { x: 0, y: 0 };

function setPointer(clientX, clientY) {
    if (motionPreference?.matches || !canvas.value) return;
    const bounds = canvas.value.getBoundingClientRect();
    target = { x: clientX - bounds.left, y: clientY - bounds.top };
}

function resetPointer() {
    target = { x: width / 2, y: height / 2 };
}

defineExpose({ setPointer, resetPointer });

function resize() {
    if (!canvas.value || !context) return;
    const bounds = canvas.value.getBoundingClientRect();
    const oldWidth = width;
    const oldHeight = height;
    width = bounds.width;
    height = bounds.height;
    radius = Math.hypot(width, height) * 0.72;
    const density = Math.min(window.devicePixelRatio || 1, 2);
    canvas.value.width = Math.round(width * density);
    canvas.value.height = Math.round(height * density);
    context.setTransform(density, 0, 0, density, 0, 0);
    if (oldWidth && oldHeight) {
        center.x *= width / oldWidth;
        center.y *= height / oldHeight;
    } else {
        center.x = width / 2;
        center.y = height / 2;
    }
    resetPointer();
    if (motionPreference?.matches) draw();
}

function draw() {
    if (!context || !canvas.value) return;
    context.clearRect(0, 0, width, height);
    const brand = getComputedStyle(canvas.value).color;
    const tones = [brand, brand, brand, brand, "#e0a150", "#bd79ad", "#6593d5"];
    for (const particle of particles) {
        const distance = 78 + particle.distance * radius;
        const x = center.x + Math.cos(particle.angle) * distance - center.vx * particle.distance * 0.55;
        const y = center.y + Math.sin(particle.angle) * distance * 0.68 - center.vy * particle.distance * 0.55;
        if (x < -8 || x > width + 8 || y < -8 || y > height + 8) continue;
        const fade = Math.min((distance - 78) / 95, 1) * Math.max(0.35, 1 - distance / (radius * 1.5));
        context.globalAlpha = fade * 0.95;
        context.strokeStyle = tones[particle.tone % tones.length];
        context.lineWidth = particle.size;
        context.lineCap = "round";
        const length = 2 + particle.distance * 3.2;
        const dx = Math.cos(particle.angle) * length;
        const dy = Math.sin(particle.angle) * length;
        context.beginPath();
        context.moveTo(x - dx / 2, y - dy / 2);
        context.lineTo(x + dx / 2, y + dy / 2);
        context.stroke();
    }
    context.globalAlpha = 1;
}

function animate(time) {
    const delta = Math.min((time - (previousTime || time)) / 16.67, 2);
    previousTime = time;
    center.vx = (center.vx + (target.x - center.x) * 0.018 * delta) * Math.pow(0.85, delta);
    center.vy = (center.vy + (target.y - center.y) * 0.018 * delta) * Math.pow(0.85, delta);
    center.x += center.vx * delta;
    center.y += center.vy * delta;
    for (const particle of particles) particle.distance = (particle.distance + particle.speed * delta / radius) % 1;
    draw();
    frame = requestAnimationFrame(animate);
}

function updateMotion() {
    if (frame) cancelAnimationFrame(frame);
    frame = undefined;
    previousTime = 0;
    resetPointer();
    if (motionPreference?.matches) {
        center = { x: width / 2, y: height / 2, vx: 0, vy: 0 };
        draw();
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
    resize();
    updateMotion();
});

onUnmounted(() => {
    if (frame) cancelAnimationFrame(frame);
    observer?.disconnect();
    motionPreference?.removeEventListener("change", updateMotion);
});
</script>

<template>
    <canvas ref="canvas" class="k-hero-burst" aria-hidden="true" />
</template>
