<script setup>
import { computed, ref } from "vue";
const props = defineProps({ modelValue: { type: String, default: "" }, readonly: Boolean });
const emit = defineEmits(["update:modelValue"]);
const open = ref(false);
const search = ref("");
const icons = [
    ["", "Sin icono"], ["user", "Persona"], ["users", "Clientes"], ["folder-open", "Expediente"],
    ["file", "Documento"], ["file-pdf", "PDF"], ["file-edit", "Solicitud"], ["list", "Listado"],
    ["plus", "Agregar"], ["pencil", "Editar"], ["eye", "Consultar"], ["search", "Buscar"],
    ["wallet", "Créditos"], ["credit-card", "Pagos"], ["calculator", "Calcular"], ["chart-line", "Gráficas"],
    ["briefcase", "Empleo"], ["building", "Empresa"], ["map-marker", "Sucursal"], ["sitemap", "Equipo"],
    ["shield", "Cumplimiento"], ["key", "Permisos"], ["history", "Historial"], ["cog", "Configuración"],
    ["home", "Inicio"], ["arrow-left", "Regresar"], ["check", "Confirmar"], ["calendar", "Calendario"],
    ["phone", "Teléfono"], ["envelope", "Correo"], ["download", "Descargar"], ["print", "Imprimir"],
    ["star", "Favorito"], ["bookmark", "Marcador"], ["link", "Enlace"], ["globe", "Sitio web"],
];
const filtered = computed(() => icons.filter(([name, label]) => (name + " " + label).toLocaleLowerCase("es").includes(search.value.toLocaleLowerCase("es"))));
const selectedLabel = computed(() => icons.find(([name]) => (name ? `pi pi-${name}` : "") === props.modelValue.replace("pi-fw ", ""))?.[1] ?? "Icono guardado");
function choose(name) {
    if (props.readonly) return;
    emit("update:modelValue", name ? `pi pi-${name}` : "");
    open.value = false;
}
</script>
<template>
    <div class="flex items-center gap-3">
        <span v-if="modelValue" :class="modelValue" aria-hidden="true" />
        <span>{{ selectedLabel }}</span>
        <Button v-if="!readonly" type="button" label="Elegir icono" severity="secondary" outlined size="small" @click="open = true" />
    </div>
    <Dialog v-if="!readonly" v-model:visible="open" header="Elegir icono" modal :style="{ width: '480px', maxWidth: '95vw' }">
        <InputText v-model="search" aria-label="Buscar icono" placeholder="Buscar icono" fluid />
        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
            <Button v-for="[name, label] in filtered" :key="name" type="button" :label="label" :icon="name ? `pi pi-${name}` : undefined" severity="secondary" text @click="choose(name)" />
        </div>
        <p v-if="!filtered.length" role="status" class="mt-4">No hay iconos con esa búsqueda.</p>
    </Dialog>
</template>
