<script setup>
import { computed, ref } from "vue";

const props = defineProps({ variables: { type: Array, default: () => [] }, readonly: Boolean, document: Boolean });
const emit = defineEmits(["insert"]);
const query = ref("");
const tokenLabel = (item) => item.token ?? (props.document ? "{{" + item.clave + "}}" : "{" + item.clave + "}");
const groups = computed(() => {
    const map = new Map();
    for (const item of props.variables) {
        if (query.value && !`${item.nombre} ${item.clave} ${item.origen}`.toLocaleLowerCase("es-MX").includes(query.value.toLocaleLowerCase("es-MX"))) continue;
        const key = props.document ? item.clave.split(".")[0] : "notificacion";
        if (!map.has(key)) map.set(key, { key, label: ({ documento: "Documento", empresa: "Empresa", cliente: "Cliente", garantia: "Garantía" })[key] ?? "Notificación", items: [] });
        map.get(key).items.push(item);
    }
    return [...map.values()];
});
</script>

<template>
    <aside class="variable-panel min-w-0 rounded-2xl border border-primary-100 bg-primary-50/70 p-4 dark:border-primary-900 dark:bg-primary-950/20">
        <h3 class="font-semibold">Variables permitidas</h3>
        <p class="text-xs">{{ readonly ? "Consulta las variables; no se insertan en este modo." : "Coloca el cursor y selecciona una variable para insertarla." }}</p>
        <label class="mt-3 block text-sm">Buscar variables<input v-model="query" type="search" class="mt-1 w-full rounded border p-2 dark:bg-surface-900" placeholder="Buscar variable" /></label>
        <div class="mt-3 max-h-[55vh] space-y-4 overflow-auto">
            <section v-for="group in groups" :key="group.key">
                <h4 class="mb-2 text-xs font-bold uppercase">{{ group.label }}</h4>
                <button v-for="item in group.items" :key="item.clave" type="button" :disabled="readonly" class="mb-2 block w-full rounded-xl bg-surface-0 p-3 text-left disabled:cursor-default dark:bg-surface-900" @mousedown.prevent @click="!readonly && emit('insert', item)">
                    <span class="block text-sm font-medium">{{ item.nombre }}</span>
                    <code class="block break-all text-xs">{{ tokenLabel(item) }}</code>
                    <span class="block text-xs">{{ item.origen }} · {{ item.formato }}</span>
                    <span v-if="item.requerida" class="block text-xs text-amber-700">{{ document ? "Requerida para generar si se utiliza." : "Requiere un dato del evento si se utiliza." }}</span>
                </button>
            </section>
            <p v-if="!groups.length" class="text-sm">No hay variables que coincidan con la búsqueda.</p>
        </div>
        <p class="mt-3 text-xs">Las variables no admiten código ni acceso a otros datos.</p>
    </aside>
</template>
