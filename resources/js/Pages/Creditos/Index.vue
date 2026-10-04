<script setup>
import { Link, router } from "@inertiajs/vue3";
import { reactive, ref } from "vue";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
const props = defineProps({ creditos: Object, filters: Object, sucursales: Array });
const filters = reactive({ buscar: "", sucursal_id: null, orden: "recientes", por_pagina: 25, ...props.filters });
const loading = ref(false);
function buscar(page = 1) { router.get(route("creditos.index"), { ...filters, page }, { preserveState: true, onStart: () => { loading.value = true; }, onFinish: () => { loading.value = false; } }); }
</script>
<template>
    <AppLayout title="Créditos">
        <template #card-header>
            <PageHeader eyebrow="Operación · Cartera" title="Créditos y pagos" description="Consulta la cartera, el calendario y los movimientos de cada crédito." />
        </template>
        <template #card-content>
            <div class="space-y-6 p-4 md:p-6">
                <section class="k-feature flex flex-wrap items-center justify-between gap-4 p-6"><div><Tag severity="warn" value="Solo pruebas QA" /><p class="mt-3 text-3xl font-bold k-financial">{{ creditos.total }}</p><p>Créditos con estos filtros</p></div><Link :href="route('solicitudes.index')" class="rounded-lg bg-white px-4 py-3 font-semibold text-surface-900"><i class="pi pi-folder-open mr-2" />Ir a solicitudes</Link></section>
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="buscar()">
                    <div><label for="credito-buscar" class="mb-2 block">Cliente o número de crédito</label><InputText id="credito-buscar" v-model="filters.buscar" maxlength="100" /></div>
                    <div><label for="credito-sucursal" class="mb-2 block">Sucursal</label><Select inputId="credito-sucursal" aria-label="Sucursal" v-model="filters.sucursal_id" :options="sucursales" optionLabel="nombre" optionValue="id" showClear placeholder="Todas" /></div>
                    <div><label for="credito-orden" class="mb-2 block">Orden</label><Select inputId="credito-orden" aria-label="Orden" v-model="filters.orden" :options="[{label:'Más recientes',value:'recientes'},{label:'Más antiguos',value:'antiguos'}]" optionLabel="label" optionValue="value" /></div>
                    <Button type="submit" label="Buscar" icon="pi pi-search" :loading="loading" />
                </form>
                <div class="k-mobile-records" aria-label="Créditos">
                    <div v-if="!creditos.data.length" class="k-empty k-surface"><i class="pi pi-wallet" aria-hidden="true" /><strong>Sin créditos con estos filtros</strong><span>Completa la firma y registra el desembolso desde una solicitud.</span></div>
                    <article v-for="credito in creditos.data" :key="credito.id" class="k-surface k-record-card"><div class="flex justify-between items-start gap-2"><div><p class="k-eyebrow">CR-{{ credito.id }}</p><h2 class="font-semibold">{{ credito.cliente?.primer_nombre }} {{ credito.cliente?.apellido_paterno }}</h2></div><Tag severity="success" value="Activo · QA" /></div><p class="text-sm text-surface-500">{{ credito.sucursal?.nombre ?? 'Sin sucursal' }}</p><p class="k-financial font-semibold">Capital inicial: {{ money(credito.capital_inicial) }}</p><Link :href="route('creditos.show', credito.id)" class="k-action-link">Ver crédito <i class="pi pi-arrow-right" aria-hidden="true" /></Link></article>
                </div>
                <DataTable class="k-desktop-records" :value="creditos.data" :loading="loading" stripedRows scrollable dataKey="id">
                    <template #empty>No hay créditos con estos filtros. Para crear uno, completa la firma y registra el desembolso desde una solicitud.</template>
                    <Column header="Crédito"><template #body="{data}"><Link :href="route('creditos.show', data.id)" class="font-semibold text-primary underline">CR-{{ data.id }}</Link></template></Column>
                    <Column header="Cliente"><template #body="{data}">{{ data.cliente.primer_nombre }} {{ data.cliente.apellido_paterno }}</template></Column>
                    <Column field="sucursal.nombre" header="Sucursal" /><Column field="responsable.name" header="Responsable" />
                    <Column header="Estado"><template #body><Tag severity="success" value="Activo · QA" /></template></Column>
                    <Column header="Capital inicial"><template #body="{data}">{{ money(data.capital_inicial) }}</template></Column>
                </DataTable>
                <p class="text-sm text-surface-500">El capital inicial no representa el saldo actualizado. Abre el crédito para consultar su situación y registrar pagos de prueba.</p>
                <Paginator :first="(creditos.current_page - 1) * creditos.per_page" :rows="creditos.per_page" :totalRecords="creditos.total" :rowsPerPageOptions="[10,25,50]" @page="event => { filters.por_pagina = event.rows; buscar(event.page + 1); }" />
            </div>
        </template>
    </AppLayout>
</template>
