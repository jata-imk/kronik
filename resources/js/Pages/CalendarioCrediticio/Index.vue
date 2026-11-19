<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import CalendarioMes from "./CalendarioMes.vue";
import EditorCalendario from "./EditorCalendario.vue";
import EjemplosCalendario from "./EjemplosCalendario.vue";

const props = defineProps({ version: Object, vigenteId: Number, versiones: Object, puedeEditar: Boolean, puedePublicar: Boolean, plantilla: Object, hoy: String });
const tab = ref("calendario");
const editor = ref(null);
const confirmacion = ref(false);
const avisoPublicacion = ref("");
const publicacion = useForm({ revision: null });
const editorReadonly = computed(() => editor.value === "actual" && (!props.puedeEditar || Boolean(props.version?.publicada_en)));
const estado = computed(() => {
    if (!props.version?.publicada_en) return "Borrador";
    return props.version.id === props.vigenteId ? "Vigente" : "Publicación histórica";
});

watch(() => props.version?.id, () => {
    editor.value = null;
    confirmacion.value = false;
    avisoPublicacion.value = "";
    publicacion.clearErrors();
    tab.value = "calendario";
});

function confirmarPublicacion() {
    if (!props.puedePublicar || !props.version || props.version.publicada_en) return;
    avisoPublicacion.value = "";
    publicacion.revision = props.version.revision;
    confirmacion.value = true;
}

function publicar() {
    if (!props.puedePublicar || !props.version || props.version.publicada_en || publicacion.processing) return;
    if (publicacion.revision !== props.version.revision) {
        confirmacion.value = false;
        avisoPublicacion.value = "El borrador cambió. Revisa la configuración antes de volver a confirmar.";
        return;
    }
    publicacion.post(route("productos-crediticios.calendario.publicar", props.version.id), {
        onSuccess: () => { confirmacion.value = false; },
        onError: (errores) => {
            confirmacion.value = false;
            avisoPublicacion.value = errores.revision ?? "No se pudo publicar el calendario.";
        },
    });
}
</script>

<template>
    <AppLayout title="Calendario crediticio">
        <main class="mx-auto max-w-6xl space-y-5 p-3 sm:p-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <h1 class="m-0 text-3xl font-semibold tracking-tight">Calendario crediticio</h1>
                    <p class="m-0 max-w-xl text-surface-600 dark:text-surface-300">Días hábiles, feriados y excepciones para todas las sucursales. Explora su efecto sobre fechas de pago en Ejemplos.</p>
                </div>
                <Button v-if="puedeEditar" label="Nuevo borrador" icon="pi pi-plus" @click="editor = 'nuevo'" />
            </header>

            <section v-if="version" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-surface-200 p-4 dark:border-surface-700">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2"><strong>Versión {{ version.id }}</strong><Tag :value="estado" :severity="version.id === vigenteId ? 'success' : (version.publicada_en ? 'secondary' : 'warn')" /></div>
                    <p class="mb-0">Cobertura revisada · {{ version.configuracion.desde }} a {{ version.configuracion.hasta }}</p>
                    <p class="mb-0 break-words text-sm text-surface-600 dark:text-surface-300">{{ version.configuracion.fuente }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button :label="puedeEditar && !version.publicada_en ? 'Editar borrador' : 'Ver configuración'" icon="pi pi-list" outlined severity="secondary" @click="editor = 'actual'" />
                    <Button v-if="puedePublicar && !version.publicada_en" label="Publicar" icon="pi pi-check" @click="confirmarPublicacion" />
                </div>
            </section>
            <Message v-else severity="info">Aún no hay un calendario publicado. La vigencia comienza cuando un Super Admin publica un borrador.</Message>
            <Message v-if="avisoPublicacion" severity="warn">{{ avisoPublicacion }}</Message>

            <Tabs v-model:value="tab">
                <TabList>
                    <Tab value="calendario">Calendario</Tab>
                    <Tab value="versiones">Versiones</Tab>
                    <Tab value="ejemplos">Ejemplos</Tab>
                </TabList>
                <TabPanels>
                    <TabPanel value="calendario">
                        <CalendarioMes v-if="version" :version="version" :hoy="hoy" />
                        <p v-else class="mb-0">Cuando haya una versión disponible podrás explorar sus fechas aquí.</p>
                    </TabPanel>
                    <TabPanel value="versiones">
                        <ul v-if="versiones.data.length" class="divide-y divide-surface-200 dark:divide-surface-700">
                            <li v-for="item in versiones.data" :key="item.id" class="flex flex-wrap items-center justify-between gap-2 py-3">
                                <Link :href="route('productos-crediticios.calendario.show', item.id)" class="font-medium text-primary hover:underline">Versión {{ item.id }}</Link>
                                <Tag :value="item.id === vigenteId ? 'Vigente' : (item.publicada_en ? 'Histórica' : 'Borrador')" :severity="item.id === vigenteId ? 'success' : (item.publicada_en ? 'secondary' : 'warn')" />
                            </li>
                        </ul>
                        <p v-else class="mb-0">Todavía no hay versiones disponibles.</p>
                        <nav v-if="versiones.prev_page_url || versiones.next_page_url" class="flex gap-4 pt-3" aria-label="Páginas del historial">
                            <Link v-if="versiones.prev_page_url" :href="versiones.prev_page_url" preserve-state class="text-primary underline">Anterior</Link>
                            <Link v-if="versiones.next_page_url" :href="versiones.next_page_url" preserve-state class="text-primary underline">Siguiente</Link>
                        </nav>
                    </TabPanel>
                    <TabPanel value="ejemplos">
                        <EjemplosCalendario v-if="version" :key="version.id + '-' + version.revision" :version="version" />
                        <p v-else class="mb-0">Selecciona una versión para comparar su efecto en un crédito.</p>
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <Dialog :visible="editor !== null" :header="editor === 'nuevo' ? 'Nuevo borrador' : 'Configuración de la versión'" modal class="w-[min(94vw,48rem)]" @update:visible="editor = null">
                <EditorCalendario v-if="editor" :key="`${editor}-${editorReadonly}`" :version="editor === 'actual' ? version : null" :hoy="hoy" :plantilla="plantilla"
                    :readonly="editorReadonly" @guardado="editor = null" />
            </Dialog>
            <Dialog v-model:visible="confirmacion" header="Publicar calendario" modal class="w-[min(94vw,32rem)]">
                <p>La versión pasará a ser la referencia institucional del catálogo. Podrás comparar su efecto en Ejemplos. La cobertura que estás publicando va del {{ version?.configuracion.desde }} al {{ version?.configuracion.hasta }}.</p>
                <template #footer>
                    <Button label="Cancelar" severity="secondary" text @click="confirmacion = false" />
                    <Button label="Publicar versión" :loading="publicacion.processing" @click="publicar" />
                </template>
            </Dialog>
        </main>
    </AppLayout>
</template>
