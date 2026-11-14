<script setup>
const model = defineModel({ type: Object, required: true });
defineProps({ sucursales: { type: Array, default: () => [] } });
</script>
<template>
    <div class="flex flex-wrap items-end gap-3">
        <div><label class="mb-2 block" for="consulta-alcance">Alcance</label><Select input-id="consulta-alcance" aria-label="Alcance" v-model="model.scope" :options="[{ label: 'Sucursal activa', value: 'current' }, { label: 'Consulta institucional', value: 'all' }]" option-label="label" option-value="value" @change="model.sucursal_id = null" /></div>
        <div v-if="model.scope === 'all'"><label class="mb-2 block" for="consulta-sucursal">Sucursal consultada</label><Select input-id="consulta-sucursal" aria-label="Sucursal consultada" v-model="model.sucursal_id" :options="sucursales" option-label="nombre" option-value="id" show-clear placeholder="Todas las sucursales" /></div>
        <Message v-if="model.requiereSucursal && model.scope === 'current'" severity="info" :closable="false">Selecciona una sucursal activa para consultar su operación.</Message>
    </div>
</template>
