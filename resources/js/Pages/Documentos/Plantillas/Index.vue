<script setup>
import DocumentEditor from "@/Components/Documents/DocumentEditor.vue";
import DocumentPdfPreview from "@/Components/Documents/DocumentPdfPreview.vue";
import DocumentVersionStatus from "@/Components/Documents/DocumentVersionStatus.vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { computed, nextTick, ref, watch } from "vue";

const props = defineProps({
    plantillas: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },
    variables: { type: Object, default: () => ({}) },
});
const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const query = ref("");
const typeFilter = ref(null);
const typeFilters = computed(() => [
    { label: "Todos", value: null },
    ...props.tipos.map((tipo) => ({
        ...tipo,
        label: tipo.value === "consentimiento_sic" ? "SIC" : tipo.label,
    })),
]);
const preferredVersion = (item) => item?.versiones?.find(v => v.estado === "activa") ?? item?.versiones?.[0];
const selectedId = ref(props.plantillas[0]?.id ?? null);
const selectedVersionId = ref(preferredVersion(props.plantillas[0])?.id ?? null);
const editorVisible = ref(false);
const previewVisible = ref(false);
const previewSource = ref(null);
const section = ref("contenido_html");
const editingVersion = ref(null);
const editorRefs = {};
const sections = [{label:"Encabezado",value:"encabezado_html"},{label:"Contenido",value:"contenido_html"},{label:"Pie de página",value:"pie_html"}];

const can = (permission) =>
    page.props.auth.is_super_admin ||
    page.props.auth.permissions?.[permission.replaceAll(" ", "-")] === true;
const filtered = computed(() =>
    props.plantillas.filter((item) => {
        const matchesText = `${item.nombre} ${item.clave}`
            .toLowerCase()
            .includes(query.value.toLowerCase());
        return (
            matchesText && (!typeFilter.value || item.tipo === typeFilter.value)
        );
    }),
);
const selected = computed(
    () =>
        props.plantillas.find((item) => item.id === selectedId.value) ??
        filtered.value[0] ??
        null,
);
const selectedVersion = computed(
    () =>
        selected.value?.versiones?.find(
            (item) => item.id === selectedVersionId.value,
        ) ??
        preferredVersion(selected.value) ??
        null,
);
const typeLabel = (value) =>
    props.tipos.find((item) => item.value === value)?.label ?? value;
const typeIcon = (value) =>
    ({
        consentimiento_sic: "pi-shield",
        garantia: "pi-verified",
        contrato: "pi-briefcase",
    })[value] ?? "pi-file";
const tokenLabel = (key) => `{${`{${key}}`}}`;
const activeCount = computed(
    () =>
        props.plantillas.filter((item) =>
            item.versiones.some((version) => version.estado === "activa"),
        ).length,
);
const draftCount = computed(() =>
    props.plantillas.reduce(
        (total, item) =>
            total +
            item.versiones.filter((version) => version.estado === "borrador")
                .length,
        0,
    ),
);
const usedCount = computed(() =>
    props.plantillas.reduce(
        (total, item) =>
            total +
            item.versiones.reduce(
                (sum, version) =>
                    sum + Number(version.documentos_generados_count ?? 0),
                0,
            ),
        0,
    ),
);
const availableVariables = computed(() => props.variables[form.tipo] ?? []);
const sectionLabel = computed(
    () =>
        ({
            encabezado_html: "Encabezado",
            contenido_html: "Contenido",
            pie_html: "Pie de página",
        })[section.value],
);
const emptyForm = () => ({
    clave: "",
    nombre: "",
    tipo: "consentimiento_sic",
    descripcion: "",
    encabezado_html: "<p>{{empresa.razon_social}}</p>",
    contenido_html:
        "<h1>Título del documento</h1><p>Documento preparado para {{cliente.nombre_completo}}.</p>",
    pie_html: "<p>Generado el {{documento.fecha_generacion}}</p>",
    resumen_cambios: "Versión inicial",
    presentacion: { marca_agua: "" },
});
const form = useForm(emptyForm());

const selectTemplate = (item) => {
    selectedId.value = item.id;
    selectedVersionId.value = preferredVersion(item)?.id ?? null;
};
const openCreate = () => {
    editingVersion.value = null;
    form.defaults(emptyForm());
    form.reset();
    form.clearErrors();
    section.value = "contenido_html";
    editorVisible.value = true;
};
const openEdit = (version) => {
    if (
        version.estado !== "borrador" ||
        Number(version.documentos_generados_count) > 0
    )
        return;
    editingVersion.value = version;
    form.defaults({
        clave: selected.value.clave,
        nombre: selected.value.nombre,
        tipo: selected.value.tipo,
        descripcion: selected.value.descripcion ?? "",
        encabezado_html: version.encabezado_html ?? "",
        contenido_html: version.contenido_html,
        pie_html: version.pie_html ?? "",
        resumen_cambios: version.resumen_cambios ?? "",
        presentacion: { marca_agua: version.presentacion?.marca_agua ?? "" },
    });
    form.reset();
    form.clearErrors();
    section.value = "contenido_html";
    editorVisible.value = true;
};
const closeEditor = async () => { document.activeElement?.blur(); editorVisible.value = false; };
const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: async () => {
            const target = props.plantillas.find(item => item.clave === form.clave);
            if (target) { selectedId.value = target.id; selectedVersionId.value = editingVersion.value?.id ?? target.versiones[0]?.id; }
            await closeEditor();
            toast.add({
                severity: "success",
                summary: editingVersion.value
                    ? "Borrador guardado"
                    : "Plantilla creada",
                detail: "El historial permanece listo para revisión.",
                life: 3500,
            });
        },
        onError: async (errors) => {
            toast.add({
                severity: "error",
                summary: "No se pudo guardar",
                detail:
                    Object.values(errors)[0] ?? "Revisa los campos marcados.",
                life: 5000,
            });
            await nextTick();
            document
                .querySelector("#template-editor [aria-invalid='true']")
                ?.focus();
        },
    };
    if (editingVersion.value) {
        form.put(
            route("plantillas-documentos.update", [
                selected.value.id,
                editingVersion.value.id,
            ]),
            options,
        );
    } else {
        form.post(route("plantillas-documentos.store"), options);
    }
};
const insertVariable = (variable) => editorRefs[section.value]?.insertVariable(variable);
const showPreview = (version) => {
    previewSource.value = { method: "get", url: route("plantillas-documentos.preview-pdf", version.id) };
    previewVisible.value = true;
};
const previewDraft = () => {
    previewSource.value = { method: "post", url: route("plantillas-documentos.preview-draft"), data: JSON.parse(JSON.stringify(form.data())) };
    previewVisible.value = true;
};
const action = (message, url, success, after = () => {}) =>
    confirm.require({
        header: message.header,
        message: message.body,
        icon: message.icon,
        rejectLabel: "Cancelar",
        acceptLabel: message.accept,
        acceptClass: message.danger ? "p-button-danger" : undefined,
        accept: () =>
            router.post(
                url,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        after();
                        toast.add({
                            severity: "success",
                            summary: success,
                            life: 3200,
                        });
                    },
                },
            ),
    });
const duplicate = (version) =>
    action(
        {
            header: "Crear una nueva versión",
            body: `Se copiará el contenido de la versión ${version.numero} en un borrador editable.`,
            icon: "pi pi-copy",
            accept: "Crear borrador",
        },
        route("plantillas-documentos.versionar", [
            selected.value.id,
            version.id,
        ]),
        "Nueva versión creada",
        () => { selectedVersionId.value = selected.value?.versiones?.[0]?.id; },
    );
const activate = (version) =>
    action(
        {
            header: "Activar versión",
            body: "El contenido quedará inmutable y sustituirá a la versión activa para nuevas generaciones.",
            icon: "pi pi-lock",
            accept: "Activar",
        },
        route("plantillas-documentos.activar", version.id),
        "Versión activada",
    );
const retire = (version) =>
    action(
        {
            header: "Retirar versión",
            body: "No podrá usarse en documentos nuevos. El historial y los archivos existentes se conservarán.",
            icon: "pi pi-ban",
            accept: "Retirar",
            danger: true,
        },
        route("plantillas-documentos.retirar", version.id),
        "Versión retirada",
    );
watch(selected, (item) => {
    if (
        item &&
        !item.versiones.some(
            (version) => version.id === selectedVersionId.value,
        )
    )
        selectedVersionId.value = preferredVersion(item)?.id ?? null;
});
</script>

<template>
    <AppLayout title="Documentos y plantillas">
        <template #card-header>
            <div class="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.16em] text-primary"><i class="pi pi-file-edit" />Centro documental</div>
                    <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">Documentos y plantillas</h1>
                    <p class="mt-1 text-sm text-surface-500">Redacciones versionadas, generación trazable y archivos siempre privados.</p>
                </div>
                <Button v-if="can('create plantillas-documentos')" label="Nueva plantilla" icon="pi pi-plus" @click="openCreate" />
            </div>
        </template>

        <template #card-content>
            <ConfirmDialog />
            <div class="mb-5 grid gap-3 sm:grid-cols-3">
                <div class="metric-card"><span class="metric-icon bg-emerald-100 text-emerald-700"><i class="pi pi-check-circle" /></span><div><p class="metric-label">Plantillas activas</p><p class="metric-value">{{ activeCount }}</p></div></div>
                <div class="metric-card"><span class="metric-icon bg-amber-100 text-amber-700"><i class="pi pi-pencil" /></span><div><p class="metric-label">Borradores en revisión</p><p class="metric-value">{{ draftCount }}</p></div></div>
                <div class="metric-card"><span class="metric-icon bg-blue-100 text-blue-700"><i class="pi pi-file-pdf" /></span><div><p class="metric-label">Documentos generados</p><p class="metric-value">{{ usedCount }}</p></div></div>
            </div>

            <div class="grid min-h-[38rem] gap-5 xl:grid-cols-[20rem_minmax(0,1fr)]">
                <aside class="rounded-2xl border border-surface-200 bg-surface-50/70 p-3 dark:border-surface-700 dark:bg-surface-900/50">
                    <div class="space-y-2">
                        <IconField><InputIcon class="pi pi-search" /><InputText v-model="query" aria-label="Buscar plantillas" placeholder="Buscar plantilla" fluid /></IconField>
                        <div class="grid grid-cols-2 gap-1" aria-label="Filtrar por tipo">
                            <Button v-for="tipo in typeFilters" :key="tipo.value ?? 'todos'" :label="tipo.label" size="small" :severity="typeFilter === tipo.value ? 'primary' : 'secondary'" :outlined="typeFilter !== tipo.value" :aria-pressed="typeFilter === tipo.value" @click="typeFilter = tipo.value" />
                        </div>
                    </div>
                    <div v-if="filtered.length" class="mt-3 space-y-2">
                        <button v-for="item in filtered" :key="item.id" type="button" class="template-nav" :class="selected?.id === item.id && 'template-nav-active'" @click="selectTemplate(item)">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-surface-100 text-primary dark:bg-surface-700"><i class="pi" :class="typeIcon(item.tipo)" /></span>
                            <span class="min-w-0 flex-1"><span class="block truncate font-semibold">{{ item.nombre }}</span><span class="block truncate text-xs text-surface-500">{{ typeLabel(item.tipo) }} · {{ item.versiones.length }} v.</span></span>
                            <span class="size-2 rounded-full" :class="item.versiones.some((v) => v.estado === 'activa') ? 'bg-emerald-500' : 'bg-surface-300'" />
                        </button>
                    </div>
                    <div v-else class="px-5 py-14 text-center"><i class="pi pi-file-edit text-4xl text-surface-300" /><p class="mt-3 font-semibold">Sin plantillas</p><p class="mt-1 text-sm text-surface-500">Ajusta los filtros o crea la primera.</p></div>
                </aside>

                <main v-if="selected" class="min-w-0 space-y-5">
                    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-primary-900 p-5 text-white shadow-xl shadow-slate-950/10 sm:p-6">
                        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0"><div class="flex flex-wrap gap-2"><Tag :value="typeLabel(selected.tipo)" severity="secondary" rounded /><Tag :value="selected.clave" severity="contrast" rounded /></div><h2 class="mt-4 truncate text-2xl font-bold">{{ selected.nombre }}</h2><p class="mt-2 max-w-2xl text-sm text-white/70">{{ selected.descripcion || 'Sin descripción comercial. Puedes agregarla en el próximo borrador.' }}</p></div>
                            <Button v-if="selectedVersion" label="Previsualizar" icon="pi pi-eye" severity="secondary" @click="showPreview(selectedVersion)" />
                        </div>
                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/60">Versión seleccionada</p><p class="mt-1 text-lg font-semibold">v{{ selectedVersion?.numero ?? '—' }}</p></div>
                            <div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/60">Estado</p><div class="mt-1"><DocumentVersionStatus v-if="selectedVersion" :status="selectedVersion.estado" /></div></div>
                            <div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/60">Uso de esta versión</p><p class="mt-1 text-lg font-semibold">{{ selectedVersion?.documentos_generados_count ?? 0 }} documentos</p></div>
                        </div>
                    </section>

                    <section class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_18rem]">
                        <div class="min-w-0 rounded-2xl border border-surface-200 bg-surface-0 p-4 dark:border-surface-700 dark:bg-surface-900">
                            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="font-semibold">Contenido de la versión</h3><p class="text-sm text-surface-500">El historial activo y retirado es de solo lectura.</p></div><div v-if="selectedVersion" class="flex flex-wrap gap-1"><Button v-if="selectedVersion.estado === 'borrador' && !selectedVersion.documentos_generados_count && can('update plantillas-documentos')" label="Editar" icon="pi pi-pencil" size="small" @click="openEdit(selectedVersion)" /><Button v-if="can('version plantillas-documentos')" label="Duplicar" icon="pi pi-copy" size="small" severity="secondary" @click="duplicate(selectedVersion)" /><Button v-if="selectedVersion.estado === 'borrador' && can('activate plantillas-documentos')" label="Activar" icon="pi pi-check-circle" aria-label="Activar versión" size="small" severity="success" @click="activate(selectedVersion)" /><Button v-if="selectedVersion.estado === 'activa' && can('retire plantillas-documentos')" label="Retirar" icon="pi pi-ban" aria-label="Retirar versión" size="small" severity="danger" @click="retire(selectedVersion)" /></div></div>
                            <Message v-if="selectedVersion?.estado !== 'borrador'" severity="secondary" :closable="false"><i class="pi pi-lock mr-2" />Versión histórica protegida. Duplica para proponer cambios.</Message>
                            <div v-if="selectedVersion" class="mt-4 grid gap-3 sm:grid-cols-3"><div class="content-summary"><span>Encabezado</span><strong>{{ selectedVersion.encabezado_html ? 'Configurado' : 'Vacío' }}</strong></div><div class="content-summary"><span>Contenido</span><strong>{{ Math.round(selectedVersion.contenido_html.length / 10) * 10 }} caracteres</strong></div><div class="content-summary"><span>Pie</span><strong>{{ selectedVersion.pie_html ? 'Configurado' : 'Vacío' }}</strong></div></div>
                            <Divider />
                            <div class="flex items-center gap-2 text-xs text-surface-500"><i class="pi pi-hashtag" /><span class="min-w-0 break-all font-mono">{{ selectedVersion?.contenido_hash }}</span></div>
                        </div>

                        <aside class="rounded-2xl border border-surface-200 bg-surface-0 p-4 dark:border-surface-700 dark:bg-surface-900">
                            <h3 class="font-semibold">Historial</h3><p class="mb-4 text-sm text-surface-500">Selecciona una versión.</p>
                            <ol class="space-y-1" aria-label="Versiones de la plantilla">
                                <li v-for="version in selected.versiones" :key="version.id"><button type="button" class="version-row" :class="selectedVersion?.id === version.id && 'version-row-active'" @click="selectedVersionId = version.id"><span class="version-dot" :class="version.estado === 'activa' ? 'bg-emerald-500' : version.estado === 'borrador' ? 'bg-amber-500' : 'bg-surface-400'" /><span class="min-w-0 flex-1"><span class="flex items-center justify-between gap-2"><strong>Versión {{ version.numero }}</strong><DocumentVersionStatus :status="version.estado" /><span class="text-xs text-surface-500">{{ new Date(version.created_at).toLocaleDateString('es-MX') }}</span></span><span class="mt-1 block truncate text-xs text-surface-500">{{ version.resumen_cambios || 'Sin resumen de cambios' }} · {{ version.documentos_generados_count ?? 0 }} usos</span></span></button></li>
                            </ol>
                        </aside>
                    </section>
                </main>

                <div v-else class="flex min-h-[30rem] items-center justify-center rounded-2xl border border-dashed border-surface-300"><div class="max-w-sm text-center"><span class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-primary-50 text-2xl text-primary"><i class="pi pi-file-plus" /></span><h2 class="mt-4 text-xl font-semibold">Crea una biblioteca documental</h2><p class="mt-2 text-sm text-surface-500">Empieza con el tipo de documento y agrega solo el texto jurídico aprobado.</p></div></div>
            </div>

            <Dialog id="template-editor" :visible="editorVisible" :closeOnEscape="false" modal maximizable :draggable="false" :style="{ width: 'min(1180px, 97vw)' }" @update:visible="$event ? (editorVisible = true) : closeEditor()">
                <template #header><div><div class="flex items-center gap-2"><span class="text-xl font-semibold">{{ editingVersion ? `Editar versión ${editingVersion.numero}` : 'Nueva plantilla' }}</span><Tag value="Borrador" severity="secondary" rounded /></div><p class="mt-1 text-sm text-surface-500">El contenido se sanea al guardar y se congela al activar.</p></div></template>
                <form v-if="editorVisible" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]" @submit.prevent="submit">
                    <div class="min-w-0 space-y-5">
                        <div class="grid gap-4 md:grid-cols-2"><div><label for="template-name" class="field-label">Nombre *</label><InputText id="template-name" v-model="form.nombre" :invalid="!!form.errors.nombre" :aria-invalid="!!form.errors.nombre" fluid /><Message v-if="form.errors.nombre" severity="error" size="small">{{ form.errors.nombre }}</Message></div><div><label for="template-key" class="field-label">Clave *</label><InputText id="template-key" v-model="form.clave" :disabled="!!editingVersion" :invalid="!!form.errors.clave" :aria-invalid="!!form.errors.clave" fluid /><small class="mt-1 block text-surface-500">Minúsculas, números, guiones y guiones bajos. Ejemplo: consentimiento-sic.</small><Message v-if="form.errors.clave" severity="error" size="small">{{ form.errors.clave }}</Message></div><div><span class="field-label">Tipo *</span><SelectButton v-model="form.tipo" :options="tipos" option-label="label" option-value="value" :disabled="!!editingVersion" :allow-empty="false" aria-label="Tipo de plantilla" /></div><div><label for="change-summary" class="field-label">Resumen de cambios</label><InputText id="change-summary" v-model="form.resumen_cambios" fluid /></div><div class="md:col-span-2"><label for="template-description" class="field-label">Descripción comercial</label><Textarea id="template-description" v-model="form.descripcion" rows="2" fluid /></div></div>
                        <div class="overflow-hidden rounded-2xl border border-surface-200 dark:border-surface-700"><div class="flex flex-wrap items-center justify-between gap-2 bg-surface-50 px-3 py-2 dark:bg-surface-800"><SelectButton v-model="section" :options="[{label:'Encabezado',value:'encabezado_html'},{label:'Contenido',value:'contenido_html'},{label:'Pie de página',value:'pie_html'}]" option-label="label" option-value="value" :allow-empty="false" aria-label="Sección de la plantilla" /><span class="text-xs text-surface-500">Editando: {{ sectionLabel }}</span></div><DocumentEditor v-for="field in sections" v-show="section === field.value" :key="field.value" :ref="el => { editorRefs[field.value] = el; }" v-model="form[field.value]" :section="field.value" /></div>
                        <Message v-if="form.errors[section] || form.errors.contenido_html" severity="error" :closable="false">{{ form.errors[section] || form.errors.contenido_html }}</Message>
                        <div><label for="watermark" class="field-label">Marca de agua (opcional)</label><InputText id="watermark" v-model="form.presentacion.marca_agua" maxlength="80" placeholder="Ejemplo: BORRADOR" fluid /><small class="block text-surface-500">Texto tenue en todas las páginas. Déjalo vacío para desactivarlo.</small><Message v-if="form.errors['presentacion.marca_agua']" severity="error">{{ form.errors['presentacion.marca_agua'] }}</Message></div><div class="flex flex-col-reverse gap-2 border-t border-surface-200 pt-4 sm:flex-row sm:justify-end"><Button type="button" label="Cancelar" severity="secondary" @click="closeEditor" /><Button type="button" label="Previsualizar cambios" icon="pi pi-eye" severity="secondary" @click="previewDraft" /><Button type="submit" label="Guardar borrador" icon="pi pi-save" :loading="form.processing" /></div>
                    </div>
                    <aside class="rounded-2xl border border-primary-100 bg-primary-50/70 p-4 dark:border-primary-900 dark:bg-primary-950/20"><div class="flex items-center gap-2"><span class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-contrast"><i class="pi pi-code" /></span><div><h3 class="font-semibold">Variables permitidas</h3><p class="text-xs text-surface-500">Coloca el cursor y selecciona una variable para insertarla.</p></div></div><div class="mt-4 max-h-[36rem] space-y-2 overflow-auto pr-1"><button v-for="variable in availableVariables" :key="variable.clave" type="button" class="w-full rounded-xl border border-transparent bg-surface-0 p-3 text-left transition hover:border-primary-300 hover:shadow-sm dark:bg-surface-900" @mousedown.prevent @click="insertVariable(variable)"><span class="block text-sm font-medium">{{ variable.nombre }} <span v-if="variable.requerida" class="block text-xs text-surface-500">Dato obligatorio si se utiliza</span></span><code class="mt-1 block truncate text-xs text-primary">{{ tokenLabel(variable.clave) }}</code><span class="mt-1 block truncate text-xs text-surface-500">{{ variable.origen }} · {{ variable.formato }}</span></button></div><Message class="mt-3" severity="info" size="small" :closable="false">No se admite código HTML libre en las variables ni el acceso a otros datos.</Message></aside>
                </form>
            </Dialog>

            <DocumentPdfPreview v-model:visible="previewVisible" :source="previewSource" />
        </template>
    </AppLayout>
</template>

<style scoped>
.metric-card { @apply flex items-center gap-3 rounded-2xl border border-surface-200 bg-surface-0 p-4 dark:border-surface-700 dark:bg-surface-900; }
.metric-icon { @apply flex size-11 items-center justify-center rounded-xl text-lg; }
.metric-label { @apply text-xs font-medium uppercase tracking-wide text-surface-500; }
.metric-value { @apply mt-0.5 text-2xl font-bold; }
.template-nav { @apply flex w-full items-center gap-3 rounded-xl border border-transparent bg-surface-0 p-3 text-left transition hover:border-surface-300 dark:bg-surface-800; }
.template-nav-active { @apply border-primary-300 bg-primary-50 shadow-sm dark:border-primary-700 dark:bg-primary-950/30; }
.content-summary { @apply flex flex-col rounded-xl bg-surface-50 p-3 text-xs text-surface-500 dark:bg-surface-800; }
.content-summary strong { @apply mt-1 text-sm text-surface-800 dark:text-surface-100; }
.version-row { @apply flex w-full items-start gap-3 rounded-xl p-2.5 text-left transition hover:bg-surface-50 dark:hover:bg-surface-800; }
.version-row-active { @apply bg-primary-50 text-primary-900 dark:bg-primary-950/30 dark:text-primary-100; }
.version-dot { @apply mt-1.5 size-2.5 shrink-0 rounded-full ring-4 ring-surface-100 dark:ring-surface-700; }
.field-label { @apply mb-1 block text-sm font-medium; }
@media (max-width: 640px) { :deep(#template-editor) { width: 100vw !important; height: 100dvh; max-height: 100dvh; margin: 0; border-radius: 0; } }
</style>
