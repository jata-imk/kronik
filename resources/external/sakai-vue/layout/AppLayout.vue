<script setup>
import { useLayout } from "@sakai-vue/layout/composables/layout";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { Head, usePage } from "@inertiajs/vue3";


import AppTopbar from "./AppTopbar.vue";
import Banner from "@/Components/Banner.vue";
import AppSidebar from "./AppSidebar.vue";
import AppFooter from "./AppFooter.vue";
import AppMenubar from "./AppMenubar.vue";

import Toast from "primevue/toast";

const { layoutConfig, layoutState, isSidebarActive, closeMenu } = useLayout();
const page = usePage();
const hasContextualMenu = computed(() => (page.props.menubarItems ?? []).some((item) => !["Inicio", "Regresar", "Administración"].includes(item.label)));

const outsideClickListener = ref(null);

defineProps({
    title: String,
});

watch(isSidebarActive, (newVal) => {
    if (newVal) {
        bindOutsideClickListener();
    } else {
        unbindOutsideClickListener();
    }
});

const containerClass = computed(() => {
    return {
        "layout-overlay": layoutConfig.menuMode === "overlay",
        "layout-static": layoutConfig.menuMode === "static",
        "layout-static-inactive":
            layoutState.staticMenuDesktopInactive &&
            layoutConfig.menuMode === "static",
        "layout-overlay-active": layoutState.overlayMenuActive,
        "layout-mobile-active": layoutState.staticMenuMobileActive,
    };
});

function bindOutsideClickListener() {
    if (!outsideClickListener.value) {
        outsideClickListener.value = (event) => {
            if (isOutsideClicked(event)) {
                layoutState.overlayMenuActive = false;
                layoutState.staticMenuMobileActive = false;
                layoutState.menuHoverActive = false;
            }
        };
        document.addEventListener("click", outsideClickListener.value);
    }
}

function unbindOutsideClickListener() {
    if (outsideClickListener.value) {
        document.removeEventListener("click", outsideClickListener.value);
        outsideClickListener.value = null;
    }
}

function isOutsideClicked(event) {
    const sidebarEl = document.querySelector(".layout-sidebar");
    const topbarEl = document.querySelector(".layout-menu-button");

    return !(
        sidebarEl.isSameNode(event.target) ||
        sidebarEl.contains(event.target) ||
        topbarEl.isSameNode(event.target) ||
        topbarEl.contains(event.target)
    );
}
const closeOnEscape = (event) => {
    if (event.key === "Escape" && isSidebarActive.value) closeMenu();
};
onMounted(() => document.addEventListener("keydown", closeOnEscape));
onUnmounted(() => {
    document.removeEventListener("keydown", closeOnEscape);
    unbindOutsideClickListener();
});
</script>

<template>
    <Head :title="title" />

    <div class="layout-wrapper" :class="containerClass">
        <app-topbar></app-topbar>
        <app-sidebar></app-sidebar>
        
        <div class="layout-main-container">
            <Banner />

            <!-- Page Heading -->
            <div v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </div>

            <main class="layout-main">
                <Card v-if="$slots['card-header'] || $slots['card-content']" :pt="$attrs.pt?.['card-content-body'] && { body: $attrs.pt['card-content-body'] }">
                    <template v-if="hasContextualMenu || $slots['card-header']" #header>
                        <AppMenubar v-if="hasContextualMenu" />
                        <slot v-if="$slots['card-header']" name="card-header" />
                    </template>

                    <template v-if="$slots['card-content']" #content>
                        <slot name="card-content" />
                    </template>
                </Card>

                <slot v-else />
            </main>
            <app-footer></app-footer>
        </div>
        <div class="layout-mask animate-fadein" @click="closeMenu"></div>
    </div>

    <Toast position="bottom-right" />
</template>
