<script setup>
import { useLayout } from "@sakai-vue/layout/composables/layout";
import { computed, ref, watch } from "vue";
import { Head, usePage } from "@inertiajs/vue3";


import AppTopbar from "./AppTopbar.vue";
import Banner from "@/Components/Banner.vue";
import AppSidebar from "./AppSidebar.vue";
import AppFooter from "./AppFooter.vue";

import Toast from "primevue/toast";

const page = usePage();
const menubarItems = computed(() => page.props.menubarItems ?? []);

const opcionesSuperAdmin = [
    {
        label: "Configurar menubar",
        icon: "pi pi-bars",
        command: () => {
            window.location.href = route("admin.menubar-items.index");
        },
    },
];

const { layoutConfig, layoutState, isSidebarActive } = useLayout();

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
        document.removeEventListener("click", outsideClickListener);
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
</script>

<template>
    <Head :title="title" />

    <div class="layout-wrapper" :class="containerClass">
        <app-topbar></app-topbar>
        <app-sidebar></app-sidebar>
        
        <div class="layout-main-container">
            <Banner />

            <!-- Page Heading -->
            <header v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main class="layout-main">
                <Card v-if="$slots['card-header'] || $slots['card-content']" :pt="$attrs.pt?.['card-content-body'] && { body: $attrs.pt['card-content-body'] }">
                    <template #header>
                        <Menubar :model="menubarItems">
                            <template #item="{ item, props }">
                                <a v-if="item.url" :href="item.url" v-bind="props.action">
                                    <span :class="item.icon" />
                                    <span>{{ item.label }}</span>
                                </a>
                                <span v-else v-bind="props.action">
                                    <span :class="item.icon" />
                                    <span>{{ item.label }}</span>
                                </span>
                            </template>
                            <template #end>
                                <SplitButton
                                    :buttonProps="{ class: '!hidden' }"
                                    dropdownIcon="pi pi-cog"
                                    :model="opcionesSuperAdmin" />
                            </template>
                        </Menubar>
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
        <div class="layout-mask animate-fadein"></div>
    </div>

    <Toast position="bottom-right" />
</template>
