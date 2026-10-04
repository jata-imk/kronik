<script setup>
import { useLayout } from "@sakai-vue/layout/composables/layout";
import { computed, ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";

const { layoutState, setActiveMenuItem, toggleMenu } = useLayout();

const props = defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
    index: {
        type: Number,
        default: 0,
    },
    root: {
        type: Boolean,
        default: true,
    },
    parentItemKey: {
        type: String,
        default: null,
    },
});

const itemKey = computed(() => props.parentItemKey ? `${props.parentItemKey}-${props.index}` : String(props.index));
const openByUser = ref(false);
const matchesRoute = (item) =>
    (item.to && route().current(item.to)) ||
    item.activeRoutes?.some((name) => route().current(name)) ||
    item.items?.some(matchesRoute) || false;
const isActiveMenu = computed(() => openByUser.value || matchesRoute(props.item) ||
    layoutState.activeMenuItem === itemKey.value ||
    layoutState.activeMenuItem?.startsWith(`${itemKey.value}-`));

watch(() => layoutState.activeMenuItem, (key) => {
    if (key && !key.startsWith(itemKey.value)) openByUser.value = false;
});

function itemClick(event, item) {
    if (item.disabled) {
        event.preventDefault();
        return;
    }

    if (
        (item.to || item.url) &&
        (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive)
    ) {
        toggleMenu();
    }

    if (item.command) {
        item.command({ originalEvent: event, item: item });
    }

    if (item.items) openByUser.value = !isActiveMenu.value;
    setActiveMenuItem(itemKey.value);
}

function checkActiveRoute(item) {
    return matchesRoute({ to: item.to, activeRoutes: item.activeRoutes });
}
</script>

<template>
    <li :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActiveMenu }">
        <div v-if="root && item.visible !== false" class="layout-menuitem-root-text">{{ item.label }}</div>
        <a v-if="(!item.to || item.items) && item.visible !== false" :href="item.url || '#'" @click.prevent="itemClick($event, item)" :class="item.class" :target="item.target" :aria-expanded="item.items ? isActiveMenu : undefined" tabindex="0">
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" v-if="item.items"></i>
        </a>
        <Link v-if="item.to && !item.items && item.visible !== false" @click="itemClick($event, item, index)" :class="[item.class, { 'active-route': checkActiveRoute(item) }]" tabindex="0" :href="route(item.to, item.toParams || {})">
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" v-if="item.items"></i>
        </Link>
        <Transition v-if="item.items && item.visible !== false" name="layout-submenu">
            <ul v-show="root ? true : isActiveMenu" class="layout-submenu">
                <app-menu-item v-for="(child, i) in item.items" :key="child" :index="i" :item="child" :parentItemKey="itemKey" :root="false"></app-menu-item>
            </ul>
        </Transition>
    </li>
</template>

<style lang="scss" scoped>
</style>
