<script setup>
import { Link, router } from "@inertiajs/vue3";
import { reactive, ref } from "vue";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
const props = defineProps({ creditos: Object, filters: Object, sucursales: Array });
const filters = reactive({ buscar: "", sucursal_id: null, orden: "recientes", por_pagina: 25, ...props.filters });
const loading = ref(false);
function buscar(page = 1) { router.get(route("creditos.index"), { ...filters, page }, { preserveState: true, onStart: () => { loading.value = true; }, onFinish: () => { loading.value = false; } }); }
</script>
<template>
    <AppLayout title="Créditos">
        <template #card-content>
            <div class="space-y-6 p-4 md:p-6">
                <header><p class="text-xs font-semibold uppercase tracking-widest text-primary">Cartera · registro operativo QA</p><h1 class="mt-2 text-2xl font-bold">Créditos</h1><p class="mt-2 text-surface-500">Desde la solicitud formalizada hasta el desembolso registrado. Cada crédito conserva sus condiciones y calendario.</p></header>
                <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-gradient-to-br from-slate-950 to-violet-700 p-6 text-white"><div><Tag severity="warn" value="Solo pruebas QA" /><p class="mt-3 text-3xl font-bold">{{ creditos.total }}</p><p>Créditos con estos filtros</p></div><Link :href="route('solicitudes.index')" class="rounded-lg bg-white px-4 py-3 font-semibold text-violet-700"><i class="pi pi-folder-open mr-2" />Ir a solicitudes</Link></section>
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="buscar()">
                    <div><label for="credito-buscar" class="mb-2 block">Cliente o número de crédito</label><InputText id="credito-buscar" v-model="filters.buscar" maxlength="100" /></div>
                    <div><label for="credito-sucursal" class="mb-2 block">Sucursal</label><Select inputId="credito-sucursal" aria-label="Sucursal" v-model="filters.sucursal_id" :options="sucursales" optionLabel="nombre" optionValue="id" showClear placeholder="Todas" /></div>
                    <div><label for="credito-orden" class="mb-2 block">Orden</label><Select inputId="credito-orden" aria-label="Orden" v-model="filters.orden" :options="[{label:'Más recientes',value:'recientes'},{label:'Más antiguos',value:'antiguos'}]" optionLabel="label" optionValue="value" /></div>
                    <Button type="submit" label="Buscar" icon="pi pi-search" :loading="loading" />
                </form>
                <DataTable :value="creditos.data" :loading="loading" stripedRows scrollable dataKey="id">
                    <template #empty>No hay créditos con estos filtros. Para crear uno, completa la firma y registra el desembolso desde una solicitud.</template>
                    <Column header="Crédito"><template #body="{data}"><Link :href="route('creditos.show', data.id)" class="font-semibold text-primary underline">CR-{{ data.id }}</Link></template></Column>
                    <Column header="Cliente"><template #body="{data}">{{ data.cliente.primer_nombre }} {{ data.cliente.apellido_paterno }}</template></Column>
                    <Column field="sucursal.nombre" header="Sucursal" /><Column field="responsable.name" header="Responsable" />
                    <Column header="Estado"><template #body><Tag severity="success" value="Activo · QA" /></template></Column>
                    <Column header="Capital inicial"><template #body="{data}">{{ money(data.capital_inicial) }}</template></Column>
                </DataTable>
                <p class="text-sm text-surface-500">Capital inicial no es saldo total a pagar ni saldo actualizado por devengo. Pagos y seguimiento se incorporan en el siguiente incremento.</p>
                <Paginator :first="(creditos.current_page - 1) * creditos.per_page" :rows="creditos.per_page" :totalRecords="creditos.total" :rowsPerPageOptions="[10,25,50]" @page="event => { filters.por_pagina = event.rows; buscar(event.page + 1); }" />
            </div>
        </template>
    </AppLayout>
</template>
