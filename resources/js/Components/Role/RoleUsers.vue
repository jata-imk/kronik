<script setup>
const props = defineProps({
    role: Object,
});
</script>

<template>
    <div class="p-4">
        <div class="flex items-center gap-4 mb-4">
            <span class="pi pi-fw pi-users !text-2xl"></span>
            <div>
                <p class="font-bold">Miembros con este rol</p>
                <p class="text-sm text-gray-500">{{ role?.users?.length || 0 }} usuario(s)</p>
            </div>
        </div>

        <DataTable v-if="role?.users?.length" :value="role.users" size="small">
            <Column field="name" header="Nombre" />
            <Column field="email" header="Correo" />
        </DataTable>

        <Message v-else severity="secondary">
            No hay usuarios asignados a este rol.
        </Message>
    </div>
</template>
