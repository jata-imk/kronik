<script setup>
import ContextHelp from "@/Components/Products/FinancialFieldHelp.vue";
const props = defineProps({
    role: Object,
    selectedRoleAvatarLabel: String,
    permissionsByModule: Object,
});
</script>

<template>
    <div>
        <p class="text text-gray-500 font-bold">Detalles del Rol</p>
        <div class="flex items-center gap-4 py-4">
            <Avatar :label="selectedRoleAvatarLabel" size="large" class="mr-2 shrink-0 !h-12 !w-12 !bg-primary-100 !text-primary-900 dark:!bg-primary-900 dark:!text-primary-100" shape="circle" />
            <span class="min-w-0 truncate font-bold" :title="role.name">{{ role.name }}</span>
        </div>
        <div class="mt-12">
            <span class="text text-gray-500 font-bold">Descripción general del rol</span>
            <div class="mt-4 space-y-3">
                <div v-for="(permissions, moduleName) in permissionsByModule" :key="moduleName" class="flex items-start gap-3 bg-surface-100 dark:bg-surface-800 p-4 border border-surface-200 dark:border-surface-700 rounded-xl">
                    <span class="pi pi-fw pi-key shrink-0 text-base" aria-hidden="true"></span>
                    <div>
                        <p class="text-sm font-bold text-primary-700 dark:text-primary-300">{{ moduleName }}</p>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <span v-for="permission in permissions" :key="permission.id" class="inline-flex items-center">
                                <Tag severity="primary" :value="permission.etiqueta" rounded />
                                <ContextHelp :title="permission.etiqueta" :description="permission.descripcion" />
                            </span>
                        </div>
                    </div>
                </div>
                <div v-if="!Object.keys(permissionsByModule).length" class="flex items-start gap-3 bg-surface-100 dark:bg-surface-800 p-4 border border-surface-200 dark:border-surface-700 rounded-xl">
                    <span class="pi pi-fw pi-key shrink-0 text-base" aria-hidden="true"></span>
                    <div>
                        <p class="text-sm font-bold text-secondary-500">Sin permisos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>