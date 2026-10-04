<script setup>
const particles = Array.from({ length: 220 }, (_, index) => {
    const angle = index * 2.399963229728653;
    const ring = Math.sqrt((index + 0.5) / 220);
    const radius = 145 + ring * 415;
    const x = 600 + Math.cos(angle) * radius;
    const y = 380 + Math.sin(angle) * radius * 0.72;
    return { x: x.toFixed(1), y: y.toFixed(1), rotation: ((angle * 180) / Math.PI).toFixed(1), length: index % 7 === 0 ? 7 : 3.5, tone: index % 9, delay: `-${((index * 17) % 41) / 10}s`, duration: `${2.4 + (index % 5) * 0.45}s` };
});
</script>

<template>
    <svg class="k-hero-burst" viewBox="0 0 1200 760" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
        <g v-for="(particle, index) in particles" :key="index" :class="['k-hero-particle', `k-hero-particle-${particle.tone}`]" :transform="`translate(${particle.x} ${particle.y}) rotate(${particle.rotation})`"><line :x1="-particle.length / 2" :x2="particle.length / 2" stroke-width="2.7" stroke-linecap="round" :style="{ animationDelay: particle.delay, animationDuration: particle.duration }" /></g>
    </svg>
</template>
