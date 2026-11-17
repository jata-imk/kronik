<script setup>
import { useLayout } from "@sakai-vue/layout/composables/layout";
import { findMenuSelection } from "@/Utils/sidebarNavigation";
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const { layoutState, toggleMenuGroup, closeMenu } = useLayout();
const page = usePage();
const props = defineProps({
    item: { type: Object, default: () => ({}) },
    index: { type: Number, default: 0 },
    root: { type: Boolean, default: true },
    parentItemKey: { type: String, default: null },
});

const itemKey = computed(() => props.parentItemKey ? `${props.parentItemKey}-${props.index}` : String(props.index));
const submenuId = computed(() => `k-sidebar-submenu-${itemKey.value}`);
const selectionId = computed(() => `k-sidebar-selection-${itemKey.value}`);
const isExpanded = computed(() => layoutState.expandedMenuItem === itemKey.value ||
    layoutState.expandedMenuItem?.startsWith(`${itemKey.value}-`) || false);
const selection = computed(() => {
    // Ziggy reads the browser location; page.url makes selection reactive to Inertia.
    if (!page.url) return null;
    return findMenuSelection([props.item], (name) => route().current(name));
});

function itemClick(event, item) {
    if (item.disabled) {
        event.preventDefault();
        return;
    }
    if (item.items) {
        toggleMenuGroup(itemKey.value);
        return;
    }
    if (item.to || item.url) closeMenu();
    if (item.command) item.command({ originalEvent: event, item });
}
</script>

<template>
    <li v-if="item.visible !== false" :class="{ 'layout-root-menuitem': root, 'active-menuitem': isExpanded }">
        <div v-if="root" class="layout-menuitem-root-text">{{ item.label }}</div>
        <button v-if="!root && item.items" type="button" class="k-sidebar-group" :class="[item.class, { 'active-route': selection }]" :disabled="item.disabled" :aria-label="item.label" :aria-expanded="isExpanded" :aria-controls="submenuId" :aria-describedby="selection ? selectionId : undefined" @click="itemClick($event, item)">
            <i :class="item.icon" class="layout-menuitem-icon" aria-hidden="true"></i>
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <span v-if="selection" :id="selectionId" class="sr-only">Página actual: {{ selection.item.label }}</span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" aria-hidden="true"></i>
        </button>
        <a v-else-if="!item.to && !item.items" :href="item.url || '#'" :class="item.class" :target="item.target" :aria-disabled="item.disabled || undefined" @click="itemClick($event, item)">
            <i :class="item.icon" class="layout-menuitem-icon" aria-hidden="true"></i>
            <span class="layout-menuitem-text">{{ item.label }}</span>
        </a>
        <Link v-else-if="item.to && !item.items" :href="route(item.to, item.toParams || {})" :class="[item.class, { 'active-route': selection }]" :aria-current="selection ? 'page' : undefined" :aria-disabled="item.disabled || undefined" @click="itemClick($event, item)">
            <i :class="item.icon" class="layout-menuitem-icon" aria-hidden="true"></i>
            <span class="layout-menuitem-text">{{ item.label }}</span>
        </Link>
        <Transition v-if="item.items" name="layout-submenu">
            <ul v-show="root || isExpanded" :id="submenuId" :inert="!root && !isExpanded ? true : undefined" class="layout-submenu">
                <app-menu-item v-for="(child, i) in item.items" :key="i" :index="i" :item="child" :parentItemKey="itemKey" :root="false" />
            </ul>
        </Transition>
    </li>
</template>
