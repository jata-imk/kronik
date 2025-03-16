<script setup>
import { Head, Link } from '@inertiajs/vue3';

import FeaturesWidget from '@sakai-vue/components/landing/FeaturesWidget.vue';
import FooterWidget from '@sakai-vue/components/landing/FooterWidget.vue';
import HeroWidget from '@sakai-vue/components/landing/HeroWidget.vue';
import PricingWidget from '@sakai-vue/components/landing/PricingWidget.vue';
import TopbarWidget from '@sakai-vue/components/landing/TopbarWidget.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

function smoothScroll(id) {
    document.body.click();
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
    <Head title="Bienvenido" />
    
    <div class="bg-surface-100 dark:bg-surface-600">
    <div id="home" class="landing-wrapper overflow-hidden">
        <div class="py-6 px-6 mx-0 md:mx-12 lg:mx-20 lg:px-20 flex items-center justify-between relative lg:static">
            <TopbarWidget :canLogin="canLogin" :canRegister="canRegister" :smoothScroll="smoothScroll" />
        </div>
        <HeroWidget :smoothScroll="smoothScroll" />
        <FeaturesWidget />
        <PricingWidget />
        <FooterWidget />
    </div>
</div>
</template>
