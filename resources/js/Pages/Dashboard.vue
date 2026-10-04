<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    resumen: { type: Object, required: true },
    pendientes: { type: Array, default: () => [] },
    accesos: { type: Object, required: true },
});
const page = usePage();
const nombre = computed(() => page.props.auth?.user?.name?.split(" ")[0] ?? "equipo");
const indicadores = computed(() => [
    { label: "Clientes", value: props.resumen.clientes, icon: "pi pi-users", to: "clientes.index" },
    { label: "Solicitudes", value: props.resumen.solicitudes, icon: "pi pi-file", to: "solicitudes.index" },
    { label: "En revisión", value: props.resumen.enRevision, icon: "pi pi-clock", to: "evaluacion-solicitudes.index" },
    { label: "Por formalizar", value: props.resumen.porFormalizar, icon: "pi pi-check-circle", to: "solicitudes.index" },
    { label: "Créditos", value: props.resumen.creditos, icon: "pi pi-wallet", to: "creditos.index" },
].filter((item) => item.value !== null));
const accesos = computed(() => [
    { label: "Clientes", description: "Alta y expedientes", icon: "pi pi-users", to: "clientes.index", enabled: props.accesos.clientes },
    { label: "Solicitudes", description: "Originación y seguimiento", icon: "pi pi-file-edit", to: "solicitudes.index", enabled: props.accesos.solicitudes },
    { label: "Créditos y pagos", description: "Cartera activa", icon: "pi pi-wallet", to: "creditos.index", enabled: props.accesos.creditos },
    { label: "Productos crediticios", description: "Condiciones y políticas", icon: "pi pi-sliders-h", to: "productos-crediticios.index", enabled: props.accesos.productos },
    { label: "Documentos y plantillas", description: "Formatos del crédito", icon: "pi pi-file-edit", to: "plantillas-documentos.index", enabled: props.accesos.plantillas },
].filter((item) => item.enabled));
</script>

<template>
    <AppLayout title="Tablero">
        <template #card-header>
            <PageHeader eyebrow="Tu espacio de trabajo" :title="`Buen día, ${nombre}`" description="Una vista clara de la operación de tu sucursal.">
                <template #actions><Link v-if="accesos.some((item) => item.to === 'solicitudes.index')" :href="route('solicitudes.trabajo')" class="k-action-link">Ir a Mi trabajo <i class="pi pi-arrow-right" aria-hidden="true" /></Link></template>
            </PageHeader>
        </template>
        <template #card-content>
            <div class="k-dashboard">

                <div v-if="indicadores.length" class="k-metrics">
                    <Link v-for="item in indicadores" :key="item.label" :href="route(item.to)" class="k-metric k-surface">
                        <span class="k-metric-icon"><i :class="item.icon" aria-hidden="true" /></span>
                        <span class="k-metric-value k-financial">{{ item.value.toLocaleString('es-MX') }}</span>
                        <span class="k-metric-label">{{ item.label }}</span>
                    </Link>
                </div>

                <div class="k-dashboard-grid">
                    <section class="k-surface k-panel" aria-labelledby="pendientes-title">
                        <div class="k-panel-heading"><div><p class="k-eyebrow">Seguimiento</p><h2 id="pendientes-title">Mis solicitudes pendientes</h2></div><Link v-if="accesos.some((item) => item.to === 'solicitudes.index')" :href="route('solicitudes.trabajo')">Ver todas <i class="pi pi-arrow-right" aria-hidden="true" /></Link></div>
                        <ul v-if="pendientes.length" class="k-work-list">
                            <li v-for="solicitud in pendientes" :key="solicitud.id"><Link :href="route('solicitudes.show', solicitud.id)"><span class="k-work-id">#{{ solicitud.id }}</span><span class="k-work-name">{{ solicitud.cliente || 'Cliente' }}</span><span class="k-work-state">{{ solicitud.estado }}</span><i class="pi pi-chevron-right" aria-hidden="true" /></Link></li>
                        </ul>
                        <div v-else class="k-empty"><i class="pi pi-check-circle" aria-hidden="true" /><strong>Sin pendientes asignados</strong><span>Cuando tengas solicitudes por atender, aparecerán aquí.</span></div>
                    </section>

                    <section class="k-surface k-panel" aria-labelledby="accesos-title">
                        <div class="k-panel-heading"><div><p class="k-eyebrow">Navegación</p><h2 id="accesos-title">Accesos rápidos</h2></div></div>
                        <div class="k-shortcuts"><Link v-for="item in accesos" :key="item.to" :href="route(item.to)"><span class="k-shortcut-icon"><i :class="item.icon" aria-hidden="true" /></span><span><strong>{{ item.label }}</strong><small>{{ item.description }}</small></span><i class="pi pi-arrow-up-right" aria-hidden="true" /></Link></div>
                    </section>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
