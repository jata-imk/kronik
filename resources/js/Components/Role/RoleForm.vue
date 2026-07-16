<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

import RoleHeader from "./RoleHeader.vue";
import RoleModules from "./RoleModules.vue";
import RoleDetails from "./RoleDetails.vue";
import RoleUsers from "./RoleUsers.vue";

const props = defineProps({
    formRolePermissions: Object,
    role: Object,
    editRoleNameMode: Boolean,
    modelHasChanged: Boolean,
    modules: Array,
    permissions: Array,
    permissionsByModule: Object,
    selectedRoleAvatarLabel: String,
});

const confirm = useConfirm();
const toast = useToast();
const showUsers = ref(false);

const emit = defineEmits(["update:editRoleNameMode", "submit"]);

const isGlobalSuperAdmin = computed(
    () => props.role?.name === "Super Admin" && props.role?.team_id === null,
);

const menuItems = computed(() => [
    {
        label: "Cambiar nombre",
        icon: "pi pi-fw pi-pencil",
        disabled: isGlobalSuperAdmin.value,
        command: () => {
            emit("update:editRoleNameMode", true);
        },
    },
    {
        label: "Eliminar",
        icon: "pi pi-fw pi-trash",
        disabled: isGlobalSuperAdmin.value,
        command: () => {
            confirm.require({
                message: "Realmente desea eliminar el rol?",
                header: "Confirmar",
                icon: "pi pi-info-circle",
                rejectLabel: "Cancelar",
                rejectProps: {
                    label: "Cancelar",
                    severity: "secondary",
                    outlined: true,
                },
                acceptProps: {
                    label: "Eliminar Rol",
                    severity: "danger",
                },
                accept: () => {
                    router.delete(route("admin.roles.destroy", props.role.id), {
                        only: ["roles", "errors"],
                        onSuccess: () => {
                            toast.add({
                                severity: "info",
                                summary: "Confirmado",
                                detail: "Rol eliminado",
                                life: 5000,
                            });
                        },
                        onError: (errors) => {
                            toast.add({
                                severity: "error",
                                summary: "Error",
                                detail:
                                    errors.message ||
                                    "No se pudo eliminar el rol",
                                life: 5000,
                            });
                        },
                    });
                },
            });
        },
    },
    {
        label: showUsers.value ? "Ver permisos" : "Ver miembros",
        icon: showUsers.value ? "pi pi-fw pi-lock" : "pi pi-fw pi-users",
        command: () => {
            showUsers.value = !showUsers.value;
        },
    },
]);

const handleSubmit = () => {
    emit("submit");
};
</script>

<template>
    <form @submit.prevent="handleSubmit" class="xl:col-span-9 p-2 pt-24 min-h-screen xl:min-h-full relative grid grid-cols-1 lg:grid-cols-12 gap-4 xl:gap-0">
        <RoleHeader
            :formRolePermissions="formRolePermissions"
            :role="role"
            :editRoleNameMode="editRoleNameMode"
            :menuItems="menuItems"
            :modelHasChanged="modelHasChanged"
            @update:editRoleNameMode="emit('update:editRoleNameMode', $event)"
        />

        <RoleUsers
            v-if="showUsers"
            :role="role"
            class="lg:col-span-8 col-span-1"
        />

        <template v-else>
            <RoleModules
                v-if="role"
                :modules="modules"
                :permissions="permissions"
                v-model:selected-permissions="formRolePermissions.permissions"
                class="lg:col-span-8 col-span-1"
            />
        </template>

        <RoleDetails
            v-if="role"
            :role="role"
            :selectedRoleAvatarLabel="selectedRoleAvatarLabel"
            :permissionsByModule="permissionsByModule"
            class="lg:col-span-4 col-span-1 p-4 border-t lg:border-t-0 lg:border-l border-gray-200 order-[-1] xl:order-none mt-14 sm:mt-0"
        />
    </form>
</template>
