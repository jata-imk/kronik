<script setup>
import { reactive, ref, watch, computed, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import { useForm } from "@inertiajs/vue3";

const toast = useToast();
const props = defineProps({
    modules: Array,
    menubarItems: Array,
    item: Object,
});
const emit = defineEmits(["close"]);

const modules = ref(props.modules);
const menubarItemsWithChildren = ref([
    {
        label: "Base",
        children: props.menubarItems.filter((i) => i.children?.length > 0),
    },
    ...props.menubarItems
        .flatMap((i) => [i, ...i.children.flatMap((c) => [c, ...c.children])])
        .filter((i) => i.children?.length > 0),
]);
const item = reactive(props.item ?? {});
const visible = ref(true);

// --- Tipos con etiquetas de usuario ---
const menubarItemTypes = [
    { label: "Carpeta / Agrupador", value: "menu", icon: "pi pi-folder", description: "Solo contiene sub-items, sin enlace propio" },
    { label: "Página de la aplicación", value: "route:name", icon: "pi pi-directions", description: "Redirige a una página del sistema por nombre de ruta" },
    { label: "URL externa", value: "route:static", icon: "pi pi-link", description: "Enlace fijo a cualquier URL" },
    { label: "Botón Regresar", value: "route:referer_fallback", icon: "pi pi-arrow-left", description: "Vuelve a la página anterior, o al listado indicado si no hay historial" },
    { label: "Condicional avanzado", value: "route:dynamic", icon: "pi pi-code", description: "Destino cambia según la URL de origen (avanzado)" },
];

const actionTypes = [
    { label: "Listado / Vista principal", value: "index" },
    { label: "Formulario de creación", value: "create" },
    { label: "Vista de detalle", value: "show" },
    { label: "Formulario de edición", value: "edit" },
];

// --- AutoComplete de rutas ---
const availableRoutes = ref([]);
const routeSuggestions = ref([]);

onMounted(async () => {
    try {
        const res = await fetch(route("admin.menubar-items.available-routes"));
        availableRoutes.value = await res.json();
    } catch (e) {
        // silent — AutoComplete funciona como texto libre como fallback
    }
});

const searchRoutes = (event) => {
    const q = event.query.toLowerCase();
    routeSuggestions.value = availableRoutes.value.filter((r) => r.toLowerCase().includes(q));
};

// --- Selector de íconos ---
const iconPickerVisible = ref(false);
const iconSearch = ref("");

const allIcons = [
    "home", "bars", "times", "check", "plus", "minus",
    "arrow-left", "arrow-right", "arrow-up", "arrow-down",
    "angle-left", "angle-right", "angle-up", "angle-down",
    "chevron-left", "chevron-right", "chevron-up", "chevron-down",
    "caret-left", "caret-right", "caret-up", "caret-down",
    "user", "users", "user-plus", "user-minus", "user-edit",
    "file", "file-pdf", "file-excel", "file-word", "folder", "folder-open",
    "save", "download", "upload", "copy", "database", "server",
    "cloud", "cloud-upload", "cloud-download",
    "search", "filter", "refresh", "sync", "eye", "eye-slash",
    "pencil", "trash", "lock", "lock-open", "key", "send",
    "share-alt", "print", "external-link",
    "envelope", "phone", "comments", "comment", "bell", "bell-slash",
    "chart-bar", "chart-line", "chart-pie", "calculator",
    "percentage", "dollar", "wallet", "credit-card",
    "info-circle", "exclamation-circle", "exclamation-triangle",
    "question-circle", "check-circle", "times-circle", "ban",
    "flag", "star", "star-fill", "heart", "bookmark", "tag", "tags",
    "thumbs-up", "thumbs-down",
    "code", "cog", "cogs", "wrench", "desktop", "mobile", "tablet",
    "globe", "link", "directions", "sitemap", "shield",
    "calendar", "clock", "history", "map-marker", "compass",
    "book", "paperclip", "list", "table", "th-large",
    "grip-vertical", "grip-horizontal", "shopping-cart", "briefcase",
    "building", "ticket", "id-card", "hashtag", "at", "qrcode",
    "image", "images", "video",
    "sort-amount-up", "sort-amount-down", "sort-alpha-up", "sort-alpha-down",
    "th", "fw",
];

const filteredIcons = computed(() => {
    if (!iconSearch.value) return allIcons;
    return allIcons.filter((i) => i.includes(iconSearch.value.toLowerCase()));
});

const selectIcon = (iconName) => {
    form.icon = `pi pi-fw pi-${iconName}`;
    iconPickerVisible.value = false;
    iconSearch.value = "";
};

// --- Builder para route:dynamic ---
const dynamicDefault = reactive({ routeName: "", params: "" });
const dynamicConditions = ref([]);

if (item?.type === "route:dynamic" && item?.value) {
    try {
        const parsed = JSON.parse(item.value);
        const def = parsed.find((c) => c.condition_type === "default");
        if (def) {
            dynamicDefault.routeName = def.route_name ?? "";
            dynamicDefault.params = def.params ? JSON.stringify(def.params) : "";
        }
        const conds = parsed.filter((c) => c.condition_type === "route_regexp");
        dynamicConditions.value = conds.map((c) => ({
            triggerRouteName: c.condition_value?.route_name ?? "",
            destinationRouteName: c.route_name ?? "",
            params: c.params ? JSON.stringify(c.params) : "",
        }));
    } catch (e) {
        // keep empty
    }
}

const addDynamicCondition = () =>
    dynamicConditions.value.push({ triggerRouteName: "", destinationRouteName: "", params: "" });
const removeDynamicCondition = (i) => dynamicConditions.value.splice(i, 1);

const safeParseParams = (raw) => {
    if (!raw) return undefined;
    try { return JSON.parse(raw); } catch (e) { return undefined; }
};

const buildDynamicValue = () => {
    const result = [];
    if (dynamicDefault.routeName) {
        const entry = { condition_type: "default", route_name: dynamicDefault.routeName };
        const p = safeParseParams(dynamicDefault.params);
        if (p) entry.params = p;
        result.push(entry);
    }
    dynamicConditions.value.forEach((c) => {
        if (c.triggerRouteName && c.destinationRouteName) {
            const entry = {
                condition_type: "route_regexp",
                condition_value: { pregmatch_subject_type: "referer", route_name: c.triggerRouteName },
                route_name: c.destinationRouteName,
            };
            const p = safeParseParams(c.params);
            if (p) entry.params = p;
            result.push(entry);
        }
    });
    return JSON.stringify(result);
};

// --- Formulario ---
const form = useForm({
    modules:
        item?.id &&
        Object.fromEntries(
            item?.modules?.map((m) => [m.id, { routes: m.menubar_item_module.routes }]) ?? [],
        ),
    label: item?.label || "",
    icon: item?.icon || "",
    type: item?.type || "route:name",
    value: item?.type !== "route:dynamic" ? (item?.value || "") : "",
    params: (item?.params && Object.values(item?.params).join(",")) || "",
    parent_id: item?.parent_id || null,
});

const modulesSelected = ref(item?.id ? Object.keys(form.modules).map(Number) : []);
const modulesSelectedMenubarItemModule = reactive(
    item?.id
        ? Object.fromEntries(
              modulesSelected.value.map((id) => [
                  id,
                  form.modules[id].routes?.map((r) => r.split(".").reverse().shift() || ""),
              ]),
          )
        : {},
);
const flagAnyActionModify = ref(false);

watch(
    () => [modulesSelected, modulesSelectedMenubarItemModule],
    () => {
        form.modules = Object.fromEntries(
            modulesSelected.value.map((id) => [
                id,
                {
                    routes: modulesSelectedMenubarItemModule[id]?.map(
                        (r) => `${modules.value.find((m) => m.id === id)?.route_name}.${r}`,
                    ),
                },
            ]),
        );
        flagAnyActionModify.value = false;
        for (const [, routes] of Object.entries(modulesSelectedMenubarItemModule)) {
            if (routes?.includes("edit") || routes?.includes("show")) {
                flagAnyActionModify.value = true;
            }
        }
        if (flagAnyActionModify.value) {
            let params = "";
            for (const moduleId of modulesSelected.value) {
                const module = modules.value.find((m) => m.id === moduleId);
                const moduleName = module.name;
                const moduleNameSingular = moduleName.endsWith("s") ? moduleName.slice(0, -1) : moduleName;
                params += `"${moduleNameSingular}": "{${moduleNameSingular}}",`;
            }
            form.params = `{${params.slice(0, -1)}}`;
        }
    },
    { deep: true, immediate: true },
);

watch(() => form.type, () => {
    if (form.type !== "route:dynamic") form.value = "";
});

const submit = () => {
    if (form.type === "route:dynamic") form.value = buildDynamicValue();
    if (item?.id) {
        form.put(route("admin.menubar-items.update", item.id), {
            only: ["menubarItems"],
            onSuccess: () => {
                toast.add({ severity: "success", summary: "Item actualizado", life: 3000 });
                emit("close");
            },
        });
    } else {
        form.post(route("admin.menubar-items.store"), {
            only: ["menubarItems"],
            onSuccess: () => {
                toast.add({ severity: "success", summary: "Item creado", life: 3000 });
                emit("close");
            },
        });
    }
};
</script>

<template>
    <Dialog v-model:visible="visible" header="Configurar item de menú" :modal="true" :style="{ width: '600px' }" @hide="emit('close')">
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-5">

                <!-- Título -->
                <div>
                    <label class="block text-sm font-medium mb-1">Título *</label>
                    <InputText fluid v-model="form.label" placeholder="Ej: Ver clientes, Nueva consulta..." />
                </div>

                <!-- Tipo de item -->
                <div>
                    <label class="block text-sm font-medium mb-2">Tipo de item *</label>
                    <div class="flex flex-col gap-2">
                        <div
                            v-for="t in menubarItemTypes"
                            :key="t.value"
                            class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors select-none"
                            :class="form.type === t.value
                                ? 'border-primary bg-primary/5 dark:bg-primary/10'
                                : 'border-surface-200 dark:border-surface-700 hover:border-primary/40'"
                            @click="form.type = t.value">
                            <span :class="[t.icon, 'text-primary flex-shrink-0']"></span>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-sm">{{ t.label }}</div>
                                <div class="text-xs text-surface-500 truncate">{{ t.description }}</div>
                            </div>
                            <span v-if="form.type === t.value" class="pi pi-check text-primary flex-shrink-0"></span>
                        </div>
                    </div>
                </div>

                <!-- Ícono -->
                <div>
                    <label class="block text-sm font-medium mb-1">Ícono</label>
                    <div class="flex gap-2 items-center">
                        <span :class="[form.icon || 'pi pi-question-circle', 'text-2xl w-8 text-center flex-shrink-0', !form.icon && 'text-surface-300']"></span>
                        <InputText v-model="form.icon" fluid placeholder="pi pi-fw pi-home" />
                        <Button type="button" icon="pi pi-th-large" @click="iconPickerVisible = true" outlined :title="'Abrir selector de íconos'" />
                    </div>
                    <p class="text-xs text-surface-400 mt-1">Escribe la clase directamente o usa el selector visual</p>
                </div>

                <!-- Valor según tipo -->

                <!-- route:name -->
                <div v-if="form.type === 'route:name'">
                    <label class="block text-sm font-medium mb-1">Página de destino *</label>
                    <AutoComplete
                        v-model="form.value"
                        :suggestions="routeSuggestions"
                        @complete="searchRoutes"
                        fluid dropdown
                        placeholder="clientes.index, clientes.show..." />
                    <p class="text-xs text-surface-400 mt-1">Nombre de ruta Laravel. Escribe para buscar.</p>
                </div>

                <!-- route:static -->
                <div v-else-if="form.type === 'route:static'">
                    <label class="block text-sm font-medium mb-1">URL *</label>
                    <InputText v-model="form.value" fluid placeholder="/clientes, https://ejemplo.com/..." />
                </div>

                <!-- route:referer_fallback -->
                <div v-else-if="form.type === 'route:referer_fallback'">
                    <label class="block text-sm font-medium mb-1">
                        Ruta de fallback
                        <span class="text-surface-400 font-normal">(si no hay página anterior)</span>
                    </label>
                    <AutoComplete
                        v-model="form.value"
                        :suggestions="routeSuggestions"
                        @complete="searchRoutes"
                        fluid dropdown
                        placeholder="clientes.index..." />
                    <p class="text-xs text-surface-400 mt-1">
                        Cuando el usuario llegó directo a esta página sin navegar desde otra, se usará esta ruta
                    </p>
                </div>

                <!-- route:dynamic builder -->
                <div v-else-if="form.type === 'route:dynamic'" class="flex flex-col gap-3">
                    <label class="block text-sm font-medium">Configuración condicional</label>

                    <div class="p-3 rounded-lg bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700">
                        <p class="text-sm font-medium mb-3">Si ninguna condición coincide, ir a:</p>
                        <div class="flex flex-col gap-2">
                            <div>
                                <label class="text-xs text-surface-500 mb-1 block">Ruta de destino</label>
                                <AutoComplete
                                    v-model="dynamicDefault.routeName"
                                    :suggestions="routeSuggestions"
                                    @complete="searchRoutes"
                                    fluid dropdown
                                    placeholder="clientes.index" />
                            </div>
                            <div>
                                <label class="text-xs text-surface-500 mb-1 block">Parámetros (JSON, opcional)</label>
                                <InputText v-model="dynamicDefault.params" fluid placeholder='{"cliente": "{cliente}"}' />
                            </div>
                        </div>
                    </div>

                    <div
                        v-for="(cond, i) in dynamicConditions"
                        :key="i"
                        class="p-3 rounded-lg bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700">
                        <div class="flex justify-between items-center mb-3">
                            <p class="text-sm font-medium">Condición {{ i + 1 }}</p>
                            <Button type="button" icon="pi pi-times" size="small" severity="danger" text @click="removeDynamicCondition(i)" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <div>
                                <label class="text-xs text-surface-500 mb-1 block">Si vengo de (patrón del referer):</label>
                                <AutoComplete
                                    v-model="cond.triggerRouteName"
                                    :suggestions="routeSuggestions"
                                    @complete="searchRoutes"
                                    fluid dropdown
                                    placeholder="clientes.historial-crediticio.index" />
                            </div>
                            <div>
                                <label class="text-xs text-surface-500 mb-1 block">Ir a:</label>
                                <AutoComplete
                                    v-model="cond.destinationRouteName"
                                    :suggestions="routeSuggestions"
                                    @complete="searchRoutes"
                                    fluid dropdown
                                    placeholder="clientes.historial-crediticio.index" />
                            </div>
                            <div>
                                <label class="text-xs text-surface-500 mb-1 block">Parámetros (JSON, opcional)</label>
                                <InputText v-model="cond.params" fluid placeholder='{"historial": "{historial}"}' />
                            </div>
                        </div>
                    </div>

                    <Button type="button" icon="pi pi-plus" label="Agregar condición" size="small" outlined @click="addDynamicCondition" />
                </div>

                <!-- Parámetros (solo para route:name con show/edit) -->
                <div v-if="flagAnyActionModify && form.type === 'route:name'">
                    <label class="block text-sm font-medium mb-1">Parámetros de ruta</label>
                    <InputText fluid v-model="form.params" />
                    <p class="text-xs text-surface-400 mt-1">Auto-generado a partir de los módulos seleccionados. Formato: <code class="bg-surface-100 dark:bg-surface-700 px-1 rounded">{"clave": "{clave}"}</code></p>
                </div>

                <!-- Módulos -->
                <div>
                    <label class="block text-sm font-medium mb-1">Módulo(s) donde aparece *</label>
                    <MultiSelect
                        v-model="modulesSelected"
                        :options="modules"
                        optionLabel="name"
                        optionValue="id"
                        fluid filter
                        placeholder="Selecciona módulos" />
                </div>

                <!-- Rutas por módulo -->
                <div v-for="moduleId in modulesSelected" :key="moduleId">
                    <label class="block text-sm font-medium mb-1">
                        Vistas de <strong>{{ modules.find((m) => m.id === moduleId)?.name }}</strong> donde aparece
                    </label>
                    <MultiSelect
                        v-model="modulesSelectedMenubarItemModule[moduleId]"
                        :options="actionTypes"
                        optionLabel="label"
                        optionValue="value"
                        fluid filter
                        placeholder="Selecciona vistas" />
                </div>

                <!-- Padre -->
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Item padre
                        <span class="text-surface-400 font-normal">(opcional — para crear sub-menús)</span>
                    </label>
                    <Select
                        v-model="form.parent_id"
                        fluid
                        :options="menubarItemsWithChildren"
                        optionLabel="label"
                        optionValue="id"
                        optionGroupLabel="label"
                        optionGroupChildren="children"
                        showClear
                        placeholder="Sin padre (nivel raíz)">
                        <template #optiongroup="slotProps">
                            <div class="flex items-center">
                                <span :class="`pi ${slotProps.option.icon} mr-2`"></span>
                                <div>{{ slotProps.option.label.split("-").map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(" ") }}</div>
                            </div>
                        </template>
                        <template #option="slotProps">
                            <div class="flex items-center">
                                <span class="pi pi-arrow-right !text-[0.5rem] ml-4 mr-2"></span>
                                <div>{{ slotProps.option.label }}</div>
                            </div>
                        </template>
                    </Select>
                </div>

                <!-- Acciones del formulario -->
                <div class="flex justify-end gap-2 pt-2 border-t border-surface-200 dark:border-surface-700">
                    <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click.prevent="emit('close')" />
                    <Button label="Guardar" icon="pi pi-check" type="submit" :loading="form.processing" />
                </div>
            </div>
        </form>
    </Dialog>

    <!-- Dialog selector de íconos -->
    <Dialog v-model:visible="iconPickerVisible" header="Seleccionar ícono" :modal="true" :style="{ width: '520px' }">
        <div class="flex flex-col gap-3">
            <InputText v-model="iconSearch" fluid placeholder="Buscar ícono..." autofocus />
            <div class="grid grid-cols-9 gap-1 max-h-72 overflow-y-auto">
                <button
                    v-for="icon in filteredIcons"
                    :key="icon"
                    type="button"
                    class="flex items-center justify-center p-2 rounded hover:bg-primary/10 transition-colors cursor-pointer border border-transparent hover:border-primary/30"
                    :title="`pi pi-fw pi-${icon}`"
                    @click="selectIcon(icon)">
                    <span :class="`pi pi-fw pi-${icon} text-lg`"></span>
                </button>
            </div>
            <p class="text-xs text-surface-400">{{ filteredIcons.length }} íconos. Haz clic para seleccionar.</p>
        </div>
    </Dialog>
</template>
