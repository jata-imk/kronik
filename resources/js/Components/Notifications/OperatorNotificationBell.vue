<script setup>
import axios from "axios";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

const page = usePage();
const open = ref(false);
const loading = ref(false);
const error = ref(false);
const unreadCount = ref(0);
const items = ref([]);
const context = computed(() =>
    JSON.stringify(page.props.contextoOperativo ?? {}),
);
let timer;
let generation = 0;
let requestSerial = 0;
let readQueue = Promise.resolve();
const pendingReadIds = new Set();

async function refresh() {
    if (pendingReadIds.size) return;
    const requestGeneration = generation;
    const serial = ++requestSerial;
    loading.value = true;
    try {
        const { data } = await axios.get(route("notifications.summary"));
        if (requestGeneration !== generation || serial !== requestSerial)
            return;
        unreadCount.value = data.unread_count;
        items.value = data.items;
        error.value = false;
    } catch {
        if (requestGeneration === generation && serial === requestSerial)
            error.value = true;
    } finally {
        if (requestGeneration === generation && serial === requestSerial)
            loading.value = false;
    }
}

async function markRead(item) {
    if (item.read_at || pendingReadIds.has(item.id)) return;
    const requestGeneration = generation;
    pendingReadIds.add(item.id);
    ++requestSerial;
    loading.value = false;
    readQueue = readQueue.then(async () => {
        if (requestGeneration !== generation) return;
        try {
            const { data } = await axios.patch(route("notifications.read", item.id));
            if (requestGeneration !== generation) return;
            ++requestSerial;
            unreadCount.value = data.unread_count;
            items.value = data.items;
            error.value = false;
        } catch {
            if (requestGeneration === generation) error.value = true;
        } finally {
            if (requestGeneration === generation) pendingReadIds.delete(item.id);
        }
    });
    return readQueue;
}

function toggle() {
    open.value = !open.value;
    if (open.value) refresh();
}

function closeOnEscape(event) {
    if (event.key === "Escape") open.value = false;
}

watch(context, () => {
    generation += 1;
    requestSerial += 1;
    open.value = false;
    unreadCount.value = 0;
    items.value = [];
    pendingReadIds.clear();
    readQueue = Promise.resolve();
    refresh();
});

onMounted(() => {
    refresh();
    timer = window.setInterval(() => {
        if (document.visibilityState === "visible") refresh();
    }, 60000);
    document.addEventListener("keydown", closeOnEscape);
});
onUnmounted(() => {
    generation += 1;
    requestSerial += 1;
    window.clearInterval(timer);
    document.removeEventListener("keydown", closeOnEscape);
});
</script>

<template>
    <div class="relative">
        <button type="button" class="layout-topbar-action relative" :aria-label="`Notificaciones: ${unreadCount} sin leer`" :aria-expanded="open" aria-controls="operator-notification-menu" @click="toggle">
            <i class="pi pi-bell" aria-hidden="true" />
            <span v-if="unreadCount" class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1 text-center text-xs text-white">{{ unreadCount > 99 ? "99+" : unreadCount }}</span>
        </button>
        <div v-if="open" id="operator-notification-menu" class="fixed left-1/2 top-14 z-50 w-[min(22rem,90vw)] -translate-x-1/2 rounded-lg border border-surface-200 bg-surface-0 p-3 shadow-lg dark:border-surface-700 dark:bg-surface-900 sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-2 sm:translate-x-0" role="region" aria-label="Notificaciones recientes">
            <div class="mb-2 flex items-center justify-between gap-2"><strong>Notificaciones</strong><Link :href="route('notifications.index')" class="text-primary-600 hover:underline" @click="open = false">Ver historial</Link></div>
            <p v-if="loading && !items.length" class="py-4 text-sm">Cargando notificaciones…</p>
            <p v-else-if="error" class="py-4 text-sm" role="alert">No se pudieron cargar las notificaciones. <button type="button" class="underline" @click="refresh">Reintentar</button></p>
            <p v-else-if="!items.length" class="py-4 text-sm text-surface-500">No hay notificaciones en esta sucursal.</p>
            <ul v-else class="max-h-80 divide-y divide-surface-200 overflow-y-auto dark:divide-surface-700">
                <li v-for="item in items" :key="item.id" class="py-3">
                    <p class="font-medium">{{ item.title }}</p>
                    <p class="text-sm text-surface-600 dark:text-surface-300">{{ item.body }}</p>
                    <div class="mt-1 flex items-center justify-between text-xs text-surface-500"><time :datetime="item.created_at">{{ new Date(item.created_at).toLocaleString("es-MX") }}</time><button v-if="!item.read_at" type="button" class="text-primary-600 hover:underline" @click="markRead(item)">Marcar leída</button></div>
                </li>
            </ul>
        </div>
    </div>
</template>
