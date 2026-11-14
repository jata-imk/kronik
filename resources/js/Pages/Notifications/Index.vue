<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import axios from "axios";
import { ref } from "vue";

defineProps({ notifications: { type: Object, required: true } });
const error = ref(false);
const busy = ref(null);

async function markRead(item) {
    busy.value = item.id;
    try {
        await axios.patch(route("notifications.read", item.id));
        router.reload({ only: ["notifications"] });
        error.value = false;
    } catch {
        error.value = true;
    } finally {
        busy.value = null;
    }
}
</script>

<template>
    <AppLayout title="Notificaciones">
        <div class="mx-auto w-full max-w-4xl space-y-5">
            <div><h1 class="text-2xl font-semibold">Notificaciones</h1><p class="text-surface-600 dark:text-surface-300">Historial de avisos internos del equipo y sucursal activos.</p></div>
            <p v-if="error" class="rounded border border-red-300 p-3 text-red-700 dark:text-red-300" role="alert">No se pudo marcar la notificación como leída. Intenta de nuevo.</p>
            <div v-if="!notifications.data.length" class="rounded-lg border border-surface-200 bg-surface-0 p-8 text-center dark:border-surface-700 dark:bg-surface-900">Aún no hay notificaciones en esta sucursal.</div>
            <ul v-else class="divide-y divide-surface-200 rounded-lg border border-surface-200 bg-surface-0 dark:divide-surface-700 dark:border-surface-700 dark:bg-surface-900">
                <li v-for="item in notifications.data" :key="item.id" class="p-4">
                    <div class="flex items-start justify-between gap-4"><div><h2 class="font-semibold">{{ item.title }}</h2><p class="mt-1 text-sm">{{ item.body }}</p><time class="mt-2 block text-xs text-surface-500" :datetime="item.created_at">{{ new Date(item.created_at).toLocaleString("es-MX") }}</time></div><button v-if="!item.read_at" type="button" class="shrink-0 text-sm text-primary-600 hover:underline disabled:opacity-50" :disabled="busy === item.id" @click="markRead(item)">Marcar leída</button></div>
                </li>
            </ul>
            <nav v-if="notifications.prev_page_url || notifications.next_page_url" class="flex justify-between" aria-label="Páginas de notificaciones"><Link v-if="notifications.prev_page_url" :href="notifications.prev_page_url">Anterior</Link><span v-else /><Link v-if="notifications.next_page_url" :href="notifications.next_page_url">Siguiente</Link></nav>
        </div>
    </AppLayout>
</template>
