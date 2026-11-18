<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import MenubarIconPicker from "@/Components/MenubarIconPicker.vue";
import MenubarPreview from "@/Components/MenubarPreview.vue";

const props = defineProps({
    readonly: Boolean, item: Object, initialScreen: { type: String, default: "" },
    modules: { type: Array, default: () => [] }, menubarItems: { type: Array, default: () => [] },
    catalogo: { type: Object, default: null }, canPreview: { type: Boolean, default: true },
});
const emit = defineEmits(["close"]);
const toast = useToast();
const saved = props.item ?? {};
const originalModules = Object.fromEntries((saved.modules ?? []).map(module => [module.id, { routes: [...((module.menubar_item_module ?? module.menubarItemModule)?.routes ?? [])] }]));
const form = useForm({
    label: saved.label ?? "", icon: saved.icon ?? "", type: saved.type ?? "route:name",
    value: saved.value ?? "", params: saved.params == null ? "" : typeof saved.params === "string" ? saved.params : JSON.stringify(saved.params),
    parent_id: saved.parent_id ?? null, sort_order: saved.sort_order ?? 0, modules: originalModules,
});
const catalogo = ref(props.catalogo);
const loading = ref(false);
const loadError = ref("");
const discardVisible = ref(false);
const visible = ref(true);
const selectedScreens = ref(Object.values(originalModules).flatMap(module => module.routes));
const types = [
    { label: "Acción del catálogo", value: "route:name" }, { label: "Grupo de menú", value: "menu" },
    { label: "Enlace fijo (avanzado)", value: "route:static" }, { label: "Regresar (avanzado)", value: "route:referer_fallback" },
    { label: "Destino condicional (avanzado)", value: "route:dynamic" },
];
let controller;
let revision = 0;
const actions = computed(() => catalogo.value?.acciones ?? []);
const screens = computed(() => {
    const current = catalogo.value?.pantallas ?? [];
    const legacy = Object.entries(originalModules).flatMap(([moduleId, link]) => link.routes.filter(key => !current.some(screen => screen.key === key))
        .map((key, index) => ({ key, module_id: Number(moduleId), label: `Pantalla guardada ${index + 1} · configuración avanzada` })));
    return [...current, ...legacy];
});
const selectedAction = computed({
    get: () => actions.value.some(action => action.key === form.value) ? form.value : null,
    set: key => {
        if (props.readonly) return;
        const action = actions.value.find(candidate => candidate.key === key);
        if (!action) return;
        form.value = action.key;
        form.params = action.params ? JSON.stringify(action.params) : "";
        if (!form.label) form.label = action.label;
    },
});
const advanced = computed(() => !["menu", "route:name"].includes(form.type) || Boolean(saved.value && form.type === "route:name" && catalogo.value && !selectedAction.value));
const groups = computed(() => {
    const source = [...(catalogo.value?.grupos ?? [])];
    if (saved.parent && !source.some(group => group.id === saved.parent.id)) source.push(saved.parent);
    const lookup = new Map(source.map(group => [group.id, group]));
    const path = group => {
        const labels = [group.label];
        const seen = new Set([group.id]);
        let parent = lookup.get(group.parent_id);
        while (parent && !seen.has(parent.id)) { seen.add(parent.id); labels.unshift(parent.label); parent = lookup.get(parent.parent_id); }
        return { ...group, path: labels.join(" / "), ancestors: seen };
    };
    return source.map(path).filter(group => !group.ancestors.has(saved.id));
});
const previewDraft = computed(() => ({
    label: form.label, icon: form.icon, type: form.type, value: form.value, params: form.params,
    parent_id: form.parent_id, sort_order: form.sort_order, modules: form.modules,
}));

watch(selectedScreens, values => {
    if (props.readonly) return;
    const links = {};
    for (const key of values) {
        const screen = screens.value.find(option => option.key === key);
        if (!screen) continue;
        (links[screen.module_id] ??= { routes: [] }).routes.push(key);
    }
    form.modules = links;
}, { deep: true, flush: "sync" });
function preselectScreen() {
    if (!saved.id && props.initialScreen && !selectedScreens.value.length && screens.value.some(screen => screen.key === props.initialScreen)) selectedScreens.value = [props.initialScreen];
}
async function loadCatalog() {
    if (props.readonly) return;
    const current = ++revision;
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    loadError.value = "";
    try {
        const { data } = await axios.get(route("admin.menubar-items.catalogo"), { signal: controller.signal });
        if (current !== revision) return;
        catalogo.value = data;
        preselectScreen();
    } catch (exception) {
        if (current === revision && !axios.isCancel(exception)) loadError.value = "No pudimos cargar las acciones disponibles. Inténtalo de nuevo.";
    } finally { if (current === revision) loading.value = false; }
}
onMounted(() => { if (catalogo.value) preselectScreen(); else if (!props.readonly) loadCatalog(); });
onBeforeUnmount(() => { revision++; controller?.abort(); form.cancel?.(); });
function requestClose() {
    if (!props.readonly && form.isDirty) discardVisible.value = true;
    else emit("close");
}
function submit() {
    if (props.readonly || loading.value || !catalogo.value || form.processing) return;
    const options = {
        only: ["menubarCatalogo", "menubarItems", "menubarEstado", "menubarSeleccionado", "modoMenu", "auth", "modules", "catalogoMenu", "listado"],
        onSuccess: () => { toast.add({ severity: "success", summary: saved.id ? "Elemento actualizado" : "Elemento creado", life: 3000 }); emit("close"); },
    };
    if (saved.id) form.put(route("admin.menubar-items.update", saved.id), options);
    else form.post(route("admin.menubar-items.store"), options);
}
defineExpose({ requestClose, form, selectedScreens, selectedAction });
</script>

<template>
    <Dialog :visible="visible" :header="readonly ? 'Consultar elemento de menú' : 'Configurar elemento de menú'" modal :style="{ width: '760px', maxWidth: '96vw' }"
        @update:visible="value => { if (!value) requestClose(); }">
        <Message :severity="readonly ? 'info' : 'secondary'" class="mb-4">{{ readonly ? "Consulta · solo lectura." : "Configuración compartida: los cambios afectan a toda la instalación, respetando los permisos de cada usuario." }}</Message>
        <Message v-if="loadError" severity="error" role="alert" class="mb-4">{{ loadError }} <Button type="button" label="Reintentar carga" text @click="loadCatalog" /></Message>
        <p v-if="loading" role="status" class="mb-4">Cargando acciones y pantallas…</p>
        <form @submit.prevent="submit" class="flex flex-col gap-5" :aria-busy="form.processing || loading">
            <Message v-if="Object.keys(form.errors).length" severity="error" role="alert"><ul><li v-for="(message, field) in form.errors" :key="field">{{ message }}</li></ul></Message>
            <div>
                <label for="menubar-type" class="mb-1 block text-sm font-medium">Tipo de elemento</label>
                <Select inputId="menubar-type" v-model="form.type" :options="types" optionLabel="label" optionValue="value" :disabled="readonly" fluid />
            </div>
            <section v-if="form.type === 'route:name' && !readonly" aria-label="Qué abre">
                <label for="menubar-action" class="mb-1 block text-sm font-medium">Qué abre</label>
                <Select inputId="menubar-action" v-model="selectedAction" :options="actions" optionLabel="label" optionValue="key" filter fluid placeholder="Elige una acción existente">
                    <template #option="{ option }"><span>{{ option.label }}</span><span class="ml-2 text-xs text-surface-500">{{ option.group }}</span></template>
                </Select>
                <p v-if="!selectedAction && saved.value" class="mt-2 text-sm text-surface-500">El destino guardado se conserva en las opciones avanzadas. No necesitas cambiarlo para ajustar la etiqueta o ubicación.</p>
                <p v-if="actions.find(action => action.key === selectedAction)?.required.length" class="mt-2 text-sm text-surface-500">Esta acción necesita un registro abierto. La vista previa indica cuándo falta ese contexto.</p>
            </section>
            <div>
                <label for="menubar-label" class="mb-1 block text-sm font-medium">Etiqueta</label>
                <InputText id="menubar-label" v-model="form.label" :disabled="readonly" fluid placeholder="Ejemplo: Cliente, Abrir expediente…" :aria-invalid="!!form.errors.label" />
            </div>
            <div>
                <span class="mb-2 block text-sm font-medium">Icono opcional</span>
                <MenubarIconPicker v-model="form.icon" :readonly="readonly" />
                <p class="mt-1 text-xs text-surface-500">Los grupos superiores muestran texto y flecha. El icono se utiliza dentro de los submenús.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="menubar-parent" class="mb-1 block text-sm font-medium">Dentro del grupo</label>
                    <Select inputId="menubar-parent" v-model="form.parent_id" :options="groups" optionLabel="path" optionValue="id" :disabled="readonly" showClear fluid placeholder="Nivel superior" />
                </div>
                <div>
                    <label for="menubar-order" class="mb-1 block text-sm font-medium">Orden</label>
                    <InputNumber inputId="menubar-order" v-model="form.sort_order" :min="0" :max="2147483647" :useGrouping="false" :disabled="readonly" fluid />
                    <p class="mt-1 text-xs text-surface-500">Los números menores aparecen primero dentro del mismo grupo.</p>
                </div>
            </div>
            <section aria-label="Dónde aparece">
                <label for="menubar-screens" class="mb-1 block text-sm font-medium">Dónde aparece</label>
                <MultiSelect inputId="menubar-screens" v-model="selectedScreens" :options="screens" optionLabel="label" optionValue="key" :disabled="readonly" filter fluid placeholder="Elige pantallas" :maxSelectedLabels="3" selectedItemsLabel="{0} pantallas" />
                <p class="mt-2 text-sm text-surface-500">{{ selectedScreens.length ? "La etiqueta, ubicación y orden se comparten en estas pantallas." : "Sin pantallas asignadas: este elemento queda guardado, pero no aparece por sí solo en la barra." }}</p>
                <p v-if="form.type === 'menu'" class="mt-1 text-sm text-surface-500">Un grupo aparece cuando contiene acciones disponibles en la pantalla. Configura también dónde aparecen sus acciones.</p>
            </section>
            <details :open="advanced || readonly" class="rounded-lg border border-surface-200 p-3 dark:border-surface-700">
                <summary class="cursor-pointer text-sm font-medium">Opciones avanzadas{{ readonly ? " · valores guardados" : "" }}</summary>
                <div class="mt-3 flex flex-col gap-3">
                    <p class="text-sm text-surface-500">Conserva la configuración existente. Modifica estos valores solo si conoces el destino y su contexto.</p>
                    <div><label for="menubar-value" class="mb-1 block text-sm">Destino guardado{{ form.type === "route:dynamic" ? " (JSON condicional)" : "" }}</label><Textarea id="menubar-value" v-model="form.value" :readonly="readonly" rows="4" fluid /></div>
                    <div><label for="menubar-params" class="mb-1 block text-sm">Parámetros guardados (JSON)</label><Textarea id="menubar-params" v-model="form.params" :readonly="readonly" rows="2" fluid /></div>
                    <div><label for="menubar-icon-class" class="mb-1 block text-sm">Clase del icono guardado</label><InputText id="menubar-icon-class" v-model="form.icon" :disabled="readonly" fluid /></div>
                    <pre v-if="readonly" class="whitespace-pre-wrap break-all text-xs">{{ form.modules }}</pre>
                </div>
            </details>
            <MenubarPreview v-if="catalogo && canPreview" :screens="catalogo.pantallas" :item-id="saved.id" :draft="previewDraft" :readonly="readonly" />
            <div class="flex justify-end gap-2 border-t border-surface-200 pt-3 dark:border-surface-700">
                <Button type="button" :label="readonly ? 'Cerrar consulta' : 'Cancelar'" severity="secondary" @click="requestClose" />
                <Button v-if="!readonly" label="Guardar" type="submit" icon="pi pi-check" :loading="form.processing" :disabled="loading || !catalogo" />
            </div>
        </form>
    </Dialog>
    <Dialog v-if="!readonly" v-model:visible="discardVisible" header="¿Descartar los cambios?" modal :style="{ width: '420px', maxWidth: '95vw' }">
        <p>Los cambios de este elemento todavía no se han guardado.</p>
        <template #footer><Button label="Seguir editando" severity="secondary" @click="discardVisible = false" /><Button label="Descartar cambios" severity="danger" @click="emit('close')" /></template>
    </Dialog>
</template>
