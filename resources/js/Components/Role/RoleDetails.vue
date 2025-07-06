<script setup>
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
            <Avatar :label="selectedRoleAvatarLabel" size="large" class="mr-2" style="background-color: #dee9fc; color: #1a2551" shape="circle" />
            <span class="font-bold">{{ role.name }}</span>
        </div>
        <div class="mt-12">
            <span class="text text-gray-500 font-bold">Descripción general del rol</span>
            <div class="mt-4">
                <div v-for="(permissions, moduleName) in permissionsByModule" :key="moduleName" class="flex items-center gap-4 bg-gray-100 dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-200 rounded-xl rounded-bl-none rounded-br-none">
                    <span class="pi pi-fw pi-key !text-2xl"></span>
                    <div>
                        <p class="text-sm font-bold text-primary-500">{{ moduleName.split("-").map((s) => s.charAt(0).toUpperCase() + s.substring(1)).join(" ") }}</p>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <Tag v-for="permission in permissions" :key="permission.id" severity="primary" :value="permission.name.replace(moduleName, '')" rounded />
                        </div>
                    </div>
                </div>
                <div v-if="!Object.keys(permissionsByModule).length" class="flex items-center gap-4 bg-gray-100 dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-200 rounded-xl rounded-bl-none rounded-br-none">
                    <span class="pi pi-fw pi-key !text-2xl"></span>
                    <div>
                        <p class="text-sm font-bold text-secondary-500">Sin permisos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>