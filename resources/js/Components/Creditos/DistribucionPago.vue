<script setup>
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
defineProps({ filas: { type: Array, default: () => [] } });
</script>
<template>
    <DataTable :value="filas" scrollable paginator :rows="10" stripedRows>
        <template #empty>No hay conceptos exigibles para esta fecha.</template>
        <Column field="cuota" header="Cuota" /><Column field="fecha" header="Vencimiento" /><Column field="concepto" header="Concepto" />
        <Column header="Concepto (MXN)"><template #body="{data}">{{ money(data.importe) }}</template></Column>
        <Column header="Impuesto (MXN)"><template #body="{data}">{{ money(data.impuesto) }}</template></Column>
        <Column header="Total (MXN)"><template #body="{data}"><strong>{{ money(data.total) }}</strong></template></Column>
    </DataTable>
</template>
