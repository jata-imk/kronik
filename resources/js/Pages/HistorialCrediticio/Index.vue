<script setup>
import { router, Link } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { reactive, ref } from "vue";

const props = defineProps({
    cliente: { type: Object, default: null },
    consultas: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    puedeConsultar: Boolean,
});
const filters = reactive({
    buscar: props.filters.buscar ?? "",
    estado: props.filters.estado ?? null,
    por_pagina: Number(props.filters.por_pagina ?? 25),
});
const loading = ref(false);
const estados = [
    { label: "Todas", value: null },
    { label: "Registrada como exitosa", value: "success" },
    { label: "Error", value: "error" },
    { label: "Pendiente", value: "pending" },
];
const nombre = (cliente) => [cliente?.primer_nombre, cliente?.apellido_paterno, cliente?.apellido_materno].filter(Boolean).join(" ");
const estado = (value) => estados.find((item) => item.value === value)?.label ?? "Sin clasificar";
const fecha = (value) => value ? new Intl.DateTimeFormat("es-MX", { dateStyle: "medium", timeStyle: "short" }).format(new Date(value)) : "Sin fecha";
function buscar(page = 1) {
    router.get(props.cliente ? route("clientes.historial-crediticio.show", props.cliente.id) : route("clientes.historial-crediticio.index"), {
        ...filters, page,
    }, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
    });
}
</script>

<template>
    <AppLayout title="Consultas SIC">
        <template #card-header>
            <div class="flex flex-wrap items-center justify-between gap-3 p-6">
                <div>
                    <h1 class="text-2xl font-semibold">Consultas SIC</h1>
                    <p v-if="cliente" class="mt-1">{{ nombre(cliente) }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link v-if="cliente" :href="route('clientes.expediente.show', cliente.id)" class="text-primary underline">Volver al expediente</Link>
                    <Link v-if="cliente" :href="route('clientes.historial-crediticio.index')" class="text-primary underline">Todas las consultas</Link>
                    <Link v-if="puedeConsultar" :href="route('circulo-credito.create', cliente ? { cliente: cliente.id } : {})" class="text-primary underline">Disponibilidad de consultas</Link>
                </div>
            </div>
        </template>
        <template #card-content>
            <div class="space-y-5 p-6">
                <Message severity="warn" :closable="false">Histórico heredado: su origen (pruebas o producción) no está verificado. Un registro exitoso no acredita vigencia ni un score válido para aprobar crédito. Las nuevas consultas están deshabilitadas mientras se valida la integración.</Message>
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="buscar()">
                    <div class="flex flex-col gap-1">
                        <label for="sic-buscar">Buscar cliente</label>
                        <InputText id="sic-buscar" v-model="filters.buscar" maxlength="100" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="sic-estado">Estado registrado</label>
                        <Select input-id="sic-estado" aria-label="Estado registrado" v-model="filters.estado" :options="estados" option-label="label" option-value="value" />
                    </div>
                    <Button type="submit" label="Buscar" :loading="loading" />
                </form>
                <DataTable :value="consultas.data" :loading="loading" data-key="id" striped-rows scrollable>
                    <template #empty>No hay consultas con estos filtros. Revisa el expediente del cliente para preparar su autorización.</template>
                    <Column field="id" header="Consulta" />
                    <Column header="Cliente">
                        <template #body="{ data }"><Link :href="route('clientes.historial-crediticio.show', data.cliente_id)" class="text-primary underline">{{ nombre(data.cliente) }}</Link></template>
                    </Column>
                    <Column header="Proveedor"><template #body="{ data }">{{ data.sic?.nombre ?? "Sin proveedor" }}</template></Column>
                    <Column header="Servicio"><template #body="{ data }">{{ data.api?.nombre ?? "Sin servicio" }}</template></Column>
                    <Column header="Fecha"><template #body="{ data }">{{ fecha(data.fecha_consulta) }}</template></Column>
                    <Column header="Estado registrado"><template #body="{ data }">{{ estado(data.status) }}</template></Column>
                </DataTable>
                <Paginator :first="(consultas.current_page - 1) * consultas.per_page" :rows="consultas.per_page" :total-records="consultas.total" :rows-per-page-options="[10, 25, 50]" @page="(event) => { filters.por_pagina = event.rows; buscar(event.page + 1); }" />
            </div>
        </template>
    </AppLayout>
</template>
