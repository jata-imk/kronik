<script setup>
defineProps({
    sucursal: { type: Object, required: true },
    switchToSucursal: { type: Function, required: true },
});
</script>

<template>
    <form class="p-menu-item-link" @submit.prevent="switchToSucursal(sucursal)">
        <button type="submit" class="flex w-full items-center gap-2">
            <i
                v-if="sucursal.id == $page.props.auth.user.current_sucursal_id"
                class="pi pi-check-circle text-green-500"
            />
            <span>{{ sucursal.nombre }}</span>
            <small class="ml-auto text-surface-500">{{ sucursal.clave }}</small>
        </button>
    </form>
</template>
