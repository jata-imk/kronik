<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import ConsultaSucursalFilter from "@/Components/ConsultaSucursalFilter.vue";
import TableroFinanciero from "@/Components/Tablero/TableroFinanciero.vue";
import { useTableroConsulta } from "@/Composables/useTableroConsulta";
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    filters: Object,
    sucursales: { type: Array, default: () => [] },
    resumen: { type: Object, required: true },
    pendientes: { type: Array, default: () => [] },
    accesos: { type: Object, required: true },
    finanzas: { type: Object, default: null },
    detalleFinanciero: { type: Object, default: null },
    etapas: { type: Array, default: () => [] },
});
const page = usePage();
const { consulta, consultar, cargando, errores } = useTableroConsulta(
    () => props.filters,
    () => route("dashboard"),
);
const listUrl = (name) =>
    route(
        name,
        [
            "clientes.index",
            "solicitudes.index",
            "evaluacion-solicitudes.index",
            "creditos.index",
            "solicitudes.trabajo",
        ].includes(name)
            ? {
                  scope: props.filters?.scope,
                  sucursal_id:
                      props.filters?.scope === "all"
                          ? props.filters.sucursal_id
                          : undefined,
              }
            : {},
    );
const nombre = computed(
    () => page.props.auth?.user?.name?.split(" ")[0] ?? "equipo",
);
const indicadores = computed(() =>
    [
        {
            label: "Clientes",
            value: props.resumen.clientes,
            icon: "pi pi-users",
            to: "clientes.index",
        },
        {
            label: "Solicitudes",
            value: props.resumen.solicitudes,
            icon: "pi pi-file",
            to: "solicitudes.index",
        },
        {
            label: "En revisión",
            value: props.resumen.enRevision,
            icon: "pi pi-clock",
            to: "evaluacion-solicitudes.index",
        },
        {
            label: "Por formalizar",
            value: props.resumen.porFormalizar,
            icon: "pi pi-check-circle",
            to: "solicitudes.index",
        },
        {
            label: "Créditos",
            value: props.resumen.creditos,
            icon: "pi pi-wallet",
            to: "creditos.index",
        },
    ].filter((item) => !props.filters?.requiereSucursal && item.value !== null),
);
const accesos = computed(() =>
    [
        {
            label: "Clientes",
            description: "Alta y expedientes",
            icon: "pi pi-users",
            to: "clientes.index",
            enabled: props.accesos.clientes,
        },
        {
            label: "Solicitudes",
            description: "Originación y seguimiento",
            icon: "pi pi-file-edit",
            to: "solicitudes.index",
            enabled: props.accesos.solicitudes,
        },
        {
            label: "Créditos y pagos",
            description: "Cartera activa",
            icon: "pi pi-wallet",
            to: "creditos.index",
            enabled: props.accesos.creditos,
        },
        {
            label: "Productos crediticios",
            description: "Condiciones y políticas",
            icon: "pi pi-sliders-h",
            to: "productos-crediticios.index",
            enabled: props.accesos.productos,
        },
        {
            label: "Documentos y plantillas",
            description: "Formatos del crédito",
            icon: "pi pi-file-edit",
            to: "plantillas-documentos.index",
            enabled: props.accesos.plantillas,
        },
    ].filter((item) => item.enabled),
);
</script>

<template>
    <AppLayout title="Tablero">
        <template #card-header>
            <PageHeader
                eyebrow="Tu espacio de trabajo"
                :title="`Buen día, ${nombre}`"
                description="Una vista clara de la operación de tu sucursal."
            >
                <template #actions
                    ><Link
                        v-if="
                            accesos.some(
                                (item) => item.to === 'solicitudes.index',
                            )
                        "
                        :href="listUrl('solicitudes.trabajo')"
                        class="k-action-link"
                        >Ir a Mi trabajo
                        <i class="pi pi-arrow-right" aria-hidden="true" /></Link
                ></template>
            </PageHeader>
        </template>
        <template #card-content>
            <div class="k-tablero-layout">
                <form
                    class="k-surface k-panel flex flex-wrap items-end gap-3 mb-5"
                    @submit.prevent="consultar()"
                >
                    <ConsultaSucursalFilter
                        v-model="consulta"
                        :sucursales="sucursales"
                    />
                    <template v-if="props.accesos.creditos">
                        <label class="flex flex-col gap-2 text-sm font-semibold"
                            >Corte de saldos<input
                                v-model="consulta.corte"
                                type="date"
                                class="k-tablero-fecha"
                            /><span class="text-xs font-normal text-muted-color"
                                >Hoy o cierre mensual</span
                            ></label
                        >
                        <label class="flex flex-col gap-2 text-sm font-semibold"
                            >Movimientos desde<input
                                v-model="consulta.desde"
                                type="date"
                                class="k-tablero-fecha"
                        /></label>
                        <label class="flex flex-col gap-2 text-sm font-semibold"
                            >Movimientos hasta<input
                                v-model="consulta.hasta"
                                type="date"
                                class="k-tablero-fecha"
                        /></label>
                    </template>
                    <Button
                        type="submit"
                        label="Consultar"
                        :loading="cargando"
                    />
                </form>
                <Message
                    v-for="(error, campo) in errores"
                    :key="campo"
                    severity="error"
                    :closable="false"
                    class="mb-3"
                    >{{ error }}</Message
                >
                <Message
                    v-if="cargando"
                    severity="info"
                    :closable="false"
                    role="status"
                    >Consultando el alcance y las fechas seleccionadas…</Message
                >
                <TableroFinanciero
                    v-else-if="finanzas && !Object.keys(errores).length"
                    class="mb-8"
                    :datos="finanzas"
                    :filtros="filters"
                    :detalle="detalleFinanciero"
                    @detalle="consultar"
                />
                <section
                    v-if="
                        !cargando &&
                        !Object.keys(errores).length &&
                        etapas.length
                    "
                    class="k-surface k-panel mb-6 space-y-3"
                    aria-labelledby="etapas-title"
                >
                    <div>
                        <p class="k-eyebrow">Originación · situación actual</p>
                        <h2 id="etapas-title" class="text-lg font-semibold">
                            Solicitudes por etapa
                        </h2>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            v-for="etapa in etapas"
                            :key="etapa.estado"
                            class="k-action-link"
                            :href="
                                route('solicitudes.index', {
                                    scope: filters.scope,
                                    sucursal_id:
                                        filters.scope === 'all'
                                            ? filters.sucursal_id
                                            : undefined,
                                    estado: etapa.estado,
                                })
                            "
                            >{{ etapa.etiqueta }}
                            <strong class="k-financial">{{
                                etapa.total
                            }}</strong></Link
                        >
                    </div>
                    <p class="text-sm text-muted-color">
                        Etapa actual de las solicitudes del alcance; no es una
                        tasa de conversión histórica.
                    </p>
                </section>
                <div
                    v-if="!cargando && !Object.keys(errores).length"
                    class="k-dashboard"
                >
                    <div v-if="indicadores.length" class="k-metrics">
                        <Link
                            v-for="item in indicadores"
                            :key="item.label"
                            :href="listUrl(item.to)"
                            class="k-metric k-surface"
                        >
                            <span class="k-metric-icon"
                                ><i :class="item.icon" aria-hidden="true"
                            /></span>
                            <span class="k-metric-value k-financial">{{
                                item.value.toLocaleString("es-MX")
                            }}</span>
                            <span class="k-metric-label">{{ item.label }}</span>
                        </Link>
                    </div>

                    <div class="k-dashboard-grid">
                        <section
                            class="k-surface k-panel"
                            aria-labelledby="pendientes-title"
                        >
                            <div class="k-panel-heading">
                                <div>
                                    <p class="k-eyebrow">Seguimiento</p>
                                    <h2 id="pendientes-title">
                                        Mis solicitudes pendientes
                                    </h2>
                                </div>
                                <Link
                                    v-if="
                                        accesos.some(
                                            (item) =>
                                                item.to === 'solicitudes.index',
                                        )
                                    "
                                    :href="listUrl('solicitudes.trabajo')"
                                    >Ver todas
                                    <i
                                        class="pi pi-arrow-right"
                                        aria-hidden="true"
                                /></Link>
                            </div>
                            <ul v-if="pendientes.length" class="k-work-list">
                                <li
                                    v-for="solicitud in pendientes"
                                    :key="solicitud.id"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'solicitudes.show',
                                                solicitud.id,
                                            )
                                        "
                                        ><span class="k-work-id">{{
                                            solicitud.folio ??
                                            `#${solicitud.id}`
                                        }}</span
                                        ><span class="k-work-name">{{
                                            solicitud.cliente || "Cliente"
                                        }}</span
                                        ><span class="k-work-state">{{
                                            solicitud.estado
                                        }}</span
                                        ><i
                                            class="pi pi-chevron-right"
                                            aria-hidden="true"
                                    /></Link>
                                </li>
                            </ul>
                            <div v-else class="k-empty">
                                <i
                                    class="pi pi-check-circle"
                                    aria-hidden="true"
                                /><strong>Sin pendientes asignados</strong
                                ><span
                                    >Cuando tengas solicitudes por atender,
                                    aparecerán aquí.</span
                                >
                            </div>
                        </section>

                        <section
                            class="k-surface k-panel"
                            aria-labelledby="accesos-title"
                        >
                            <div class="k-panel-heading">
                                <div>
                                    <p class="k-eyebrow">Navegación</p>
                                    <h2 id="accesos-title">Accesos rápidos</h2>
                                </div>
                            </div>
                            <div class="k-shortcuts">
                                <Link
                                    v-for="item in accesos"
                                    :key="item.to"
                                    :href="listUrl(item.to)"
                                    ><span class="k-shortcut-icon"
                                        ><i
                                            :class="item.icon"
                                            aria-hidden="true" /></span
                                    ><span
                                        ><strong>{{ item.label }}</strong
                                        ><small>{{
                                            item.description
                                        }}</small></span
                                    ><i
                                        class="pi pi-arrow-up-right"
                                        aria-hidden="true"
                                /></Link>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
