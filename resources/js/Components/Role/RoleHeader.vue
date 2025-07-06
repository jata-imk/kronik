<script setup>
const props = defineProps({
    formRolePermissions: Object,
    role: Object,
    editRoleNameMode: Boolean,
    menuItems: Array,
    modelHasChanged: Boolean,
});
const emit = defineEmits(["update:editRoleNameMode"]);
</script>

<template>
    <div class="flex flex-col sm:flex-row gap-4 sm:gap-0 items-start justify-between mb-4 bg-gray-300 dark:bg-gray-800 p-4 rounded-none xl:rounded-xl rounded-tl-none rounded-tr-none absolute top-0 left-0 right-0 lg:left-[20px] lg:right-[20px]">
        <div class="flex gap-1 sm:gap-4 items-start">
            <span class="pi pi-fw pi-key !text-2xl"></span>
            <div>
                <span class="text-lg text-gray-500 font-bold">Nombre del Rol</span>
                <div v-if="props.editRoleNameMode" class="flex items-center gap-4">
                    <InputText v-model="props.formRolePermissions.name" class="w-full" />
                    <Button icon="pi pi-check" severity="success" @click="emit('update:editRoleNameMode', false)" />
                </div>
                <p v-else class="text-xl font-bold" :class="{ 'italic': !props.role }">{{ props.formRolePermissions?.name || "Sin rol seleccionado" }}</p>
            </div>
        </div>
        <div class="flex gap-4 mt-4 lg:mt-0 self-end xl:self-auto">
            <SplitButton
                severity="secondary"
                type="button"
                :model="props.menuItems"
                menu-button-icon="pi pi-ellipsis-v"
                :buttonProps="{ class: '!hidden' }"
                :menuButtonProps="{ rounded: true }"
                :disabled="!props.role" />
            <Button severity="primary" :disabled="!props.modelHasChanged" type="submit">Actualizar</Button>
        </div>
    </div>
</template>