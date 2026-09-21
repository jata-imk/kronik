<script setup>
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { reactive, ref } from "vue";
import { solicitudEstados } from "@/Utils/solicitudEstados";

const props = defineProps({ solicitudes: Object, filters: Object, miTrabajo: Boolean, puedeCrear: Boolean, bandeja: String, rutaBandeja: String });
const filters = reactive({ buscar: props.filters.buscar ?? "", estado: props.filters.estado ?? null, orden: props.filters.orden ?? "recientes", por_pagina: Number(props.filters.por_pagina ?? 25) });
const loading = ref(false);
const estados = [{ label: "Todos", value: null }, ...Object.entries(solicitudEstados).map(([value, { label }]) => ({ label, value }))];
function buscar(page = 1) {
    router.get(route(props.rutaBandeja ?? (props.miTrabajo ? "solicitudes.trabajo" : "solicitudes.index")), { ...filters, page }, {
        preserveState: true, preserveScroll: true,
        onStart: () => { loading.value = true; }, onFinish: () => { loading.value = false; },
    });
}
</script>

<template>
    <AppLayout :title="bandeja ?? (miTrabajo ? 'Mi trabajo' : 'Solicitudes')">
        <template #card-header>
            <div class="flex flex-wrap items-center justify-between gap-3 p-6">
                <h1 class="text-2xl font-semibold">{{ bandeja ?? (miTrabajo ? "Mi trabajo" : "Solicitudes") }}</h1>
                <div class="flex gap-4">
                    <Link :href="route(miTrabajo ? 'solicitudes.index' : 'solicitudes.trabajo')" class="text-primary underline">{{ miTrabajo ? "Todas las solicitudes" : "Mis pendientes" }}</Link>
                    <Link v-if="puedeCrear" :href="route('solicitudes.create')" class="text-primary underline">Nueva solicitud</Link>
                </div>
            </div>
        </template>
        <template #card-content>
            <div class="space-y-5 p-6">
                <Message v-if="bandeja" severity="info" :closable="false">Abre la solicitud para registrar tu dictamen sobre la revisión actual. Esta bandeja no sustituye la política de aprobación ni acredita cumplimiento legal por sí sola.</Message>
                <p>{{ miTrabajo ? "Pendientes bajo tu responsabilidad. Las solicitudes cerradas se consultan con el filtro de estado." : "Captura, revisión, devoluciones y cierres. Aprobación y desembolso aún no están habilitados." }}</p>
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="buscar()">
                    <div class="flex flex-col gap-1"><label for="buscar">Cliente</label><InputText id="buscar" v-model="filters.buscar" maxlength="100" /></div>
                    <div class="flex flex-col gap-1"><label for="estado">Estado</label><Select input-id="estado" aria-label="Estado" v-model="filters.estado" :options="estados" option-label="label" option-value="value" /></div>
                    <div class="flex flex-col gap-1"><label for="orden">Orden</label><Select input-id="orden" aria-label="Orden" v-model="filters.orden" :options="[{ label: 'Más recientes', value: 'recientes' }, { label: 'Más antiguas', value: 'antiguas' }]" option-label="label" option-value="value" /></div>
                    <Button type="submit" label="Buscar" :loading="loading" />
                </form>
                <DataTable :value="solicitudes.data" :loading="loading" striped-rows scrollable data-key="id">
                    <template #empty>No hay solicitudes con estos filtros. Puedes crear una o consultar todas las solicitudes.</template>
                    <Column header="Solicitud"><template #body="{ data }"><Link :href="route('solicitudes.show', data.id)" class="text-primary underline">SOL-{{ data.id }}</Link></template></Column>
                    <Column header="Cliente"><template #body="{ data }">{{ data.cliente.primer_nombre }} {{ data.cliente.apellido_paterno }}</template></Column>
                    <Column header="Estado"><template #body="{ data }">{{ solicitudEstados[data.estado]?.label }}</template></Column>
                    <Column field="responsable.name" header="Responsable" />
                    <Column field="sucursal.nombre" header="Sucursal" />
                    <Column header="Siguiente paso"><template #body="{ data }">{{ solicitudEstados[data.estado]?.siguiente }}</template></Column>
                </DataTable>
                <Paginator :first="(solicitudes.current_page - 1) * solicitudes.per_page" :rows="solicitudes.per_page" :total-records="solicitudes.total" :rows-per-page-options="[10, 25, 50]" @page="(event) => { filters.por_pagina = event.rows; buscar(event.page + 1); }" />
            </div>
        </template>
    </AppLayout>
</template>
