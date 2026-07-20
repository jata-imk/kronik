<script setup>
import { reactive, ref, computed, onMounted } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

const toast = useToast();
const page = usePage();
const visible = ref(false);

const menubarAdmin = computed(() => page.props.menubarAdmin);
const modules = computed(() => menubarAdmin.value?.modules ?? []);
const currentRouteName = computed(() => menubarAdmin.value?.currentRouteName ?? "");

// Detectar módulo actual desde el route_name
const currentModule = computed(() => {
    const routeName = currentRouteName.value;
    if (!routeName) return null;
    return modules.value
        .filter((m) => routeName.startsWith(m.route_name))
        .sort((a, b) => b.route_name.length - a.route_name.length)[0] ?? null;
});

// --- AutoComplete de rutas ---
const availableRoutes = ref([]);
const routeSuggestions = ref([]);

onMounted(async () => {
    if (!menubarAdmin.value) return;
    try {
        const res = await fetch(route("admin.menubar-items.available-routes"));
        availableRoutes.value = await res.json();
    } catch (e) {}
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
    "user", "users", "user-plus", "user-minus",
    "file", "folder", "folder-open", "save", "download", "upload",
    "search", "filter", "refresh", "eye", "eye-slash",
    "pencil", "trash", "lock", "lock-open", "send",
    "envelope", "phone", "bell",
    "chart-bar", "chart-line", "chart-pie", "dollar", "wallet", "credit-card",
    "info-circle", "exclamation-circle", "check-circle", "ban",
    "flag", "star", "heart", "bookmark", "tag",
    "code", "cog", "cogs", "wrench", "globe", "link", "directions", "shield",
    "calendar", "clock", "history", "map-marker",
    "book", "list", "table", "shopping-cart", "briefcase", "building",
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

// --- Tipos simplificados ---
const simpleTypes = [
    { label: "Página de la aplicación", value: "route:name", icon: "pi pi-directions" },
    { label: "URL externa", value: "route:static", icon: "pi pi-link" },
    { label: "Carpeta / Agrupador", value: "menu", icon: "pi pi-folder" },
];

const actionTypes = [
    { label: "Listado / Vista principal", value: "index" },
    { label: "Formulario de creación", value: "create" },
    { label: "Vista de detalle", value: "show" },
    { label: "Formulario de edición", value: "edit" },
];

// --- Estado del formulario ---
const selectedModules = ref([]);
const selectedActions = reactive({});

const form = useForm({
    label: "",
    icon: "",
    type: "route:name",
    value: "",
    params: "",
    parent_id: null,
    modules: {},
});

const open = () => {
    form.reset();
    form.type = "route:name";

    selectedModules.value = currentModule.value ? [currentModule.value.id] : [];
    if (currentModule.value) {
        selectedActions[currentModule.value.id] = ["index"];
    }

    visible.value = true;
};

const buildModules = () => {
    return Object.fromEntries(
        selectedModules.value.map((id) => {
            const mod = modules.value.find((m) => m.id === id);
            return [
                id,
                {
                    routes: (selectedActions[id] ?? []).map((a) => `${mod?.route_name}.${a}`),
                },
            ];
        }),
    );
};

defineExpose({ open });

const submit = () => {
    form.modules = buildModules();
    form.post(route("admin.menubar-items.store"), {
        only: ["menubarItems"],
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Item agregado",
                detail: `"${form.label}" agregado al menú`,
                life: 4000,
            });
            visible.value = false;
        },
    });
};
</script>

<template>
    <!-- Dialog de quick-add -->
    <Dialog v-model:visible="visible" header="Agregar item al menú de esta página" :modal="true" :style="{ width: '480px' }">
        <form @submit.prevent="submit" class="flex flex-col gap-4">

            <!-- Título -->
            <div>
                <label class="block text-sm font-medium mb-1">Título *</label>
                <InputText v-model="form.label" fluid placeholder="Ej: Ver clientes, Nueva consulta..." autofocus />
            </div>

            <!-- Tipo -->
            <div>
                <label class="block text-sm font-medium mb-2">Tipo</label>
                <div class="flex gap-2">
                    <button
                        v-for="t in simpleTypes"
                        :key="t.value"
                        type="button"
                        class="flex-1 flex flex-col items-center gap-1 p-2 rounded-lg border text-xs transition-colors cursor-pointer"
                        :class="form.type === t.value
                            ? 'border-primary bg-primary/5 dark:bg-primary/10 text-primary'
                            : 'border-surface-200 dark:border-surface-700 hover:border-primary/40'"
                        @click="form.type = t.value">
                        <span :class="t.icon"></span>
                        <span class="text-center leading-tight">{{ t.label }}</span>
                    </button>
                </div>
            </div>

            <!-- Valor de destino -->
            <div v-if="form.type !== 'menu'">
                <label class="block text-sm font-medium mb-1">
                    {{ form.type === "route:name" ? "Página de destino" : "URL" }}
                </label>
                <AutoComplete
                    v-if="form.type === 'route:name'"
                    v-model="form.value"
                    :suggestions="routeSuggestions"
                    @complete="searchRoutes"
                    fluid dropdown
                    placeholder="clientes.index..." />
                <InputText
                    v-else
                    v-model="form.value"
                    fluid
                    placeholder="https://... o /ruta" />
            </div>

            <!-- Ícono -->
            <div>
                <label class="block text-sm font-medium mb-1">
                    Ícono
                    <span class="text-surface-400 font-normal">(opcional)</span>
                </label>
                <div class="flex gap-2 items-center">
                    <span :class="[form.icon || 'pi pi-question-circle', 'text-xl w-7 text-center flex-shrink-0', !form.icon && 'text-surface-300']"></span>
                    <InputText v-model="form.icon" fluid placeholder="pi pi-fw pi-home" />
                    <Button type="button" icon="pi pi-th-large" @click="iconPickerVisible = true" outlined size="small" />
                </div>
            </div>

            <!-- Módulos -->
            <div>
                <label class="block text-sm font-medium mb-1">Módulo(s) donde aparece</label>
                <MultiSelect
                    v-model="selectedModules"
                    :options="modules"
                    optionLabel="name"
                    optionValue="id"
                    fluid filter
                    placeholder="Módulos" />
            </div>

            <!-- Acciones por módulo -->
            <div v-for="moduleId in selectedModules" :key="moduleId">
                <label class="block text-sm font-medium mb-1">
                    Vistas de <strong>{{ modules.find((m) => m.id === moduleId)?.name }}</strong>
                </label>
                <MultiSelect
                    v-model="selectedActions[moduleId]"
                    :options="actionTypes"
                    optionLabel="label"
                    optionValue="value"
                    fluid filter
                    placeholder="Listado, detalle..." />
            </div>

            <!-- Acciones del formulario -->
            <div class="flex justify-end gap-2 pt-2 border-t border-surface-200 dark:border-surface-700">
                <Button label="Cancelar" severity="secondary" @click.prevent="visible = false" />
                <Button
                    label="Agregar al menú"
                    icon="pi pi-plus"
                    type="submit"
                    :loading="form.processing"
                    :disabled="!form.label" />
            </div>
        </form>
    </Dialog>

    <!-- Dialog selector de íconos -->
    <Dialog v-model:visible="iconPickerVisible" header="Seleccionar ícono" :modal="true" :style="{ width: '440px' }">
        <div class="flex flex-col gap-3">
            <InputText v-model="iconSearch" fluid placeholder="Buscar ícono..." autofocus />
            <div class="grid grid-cols-8 gap-1 max-h-56 overflow-y-auto">
                <button
                    v-for="icon in filteredIcons"
                    :key="icon"
                    type="button"
                    class="flex items-center justify-center p-2 rounded hover:bg-primary/10 cursor-pointer border border-transparent hover:border-primary/30"
                    :title="`pi pi-fw pi-${icon}`"
                    @click="selectIcon(icon)">
                    <span :class="`pi pi-fw pi-${icon} text-lg`"></span>
                </button>
            </div>
            <p class="text-xs text-surface-400">{{ filteredIcons.length }} íconos disponibles.</p>
        </div>
    </Dialog>
</template>
