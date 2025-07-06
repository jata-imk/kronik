<script setup>
import { ref, computed, watch } from "vue";

import RoleSidebar from "@components/Role/RoleSidebar.vue";
import RoleForm from "@components/Role/RoleForm.vue";

const props = defineProps({
    roles: Array,
    modules: Array,
    permissions: Array,
    formRolePermissions: Object,
});

const emit = defineEmits(["add-role", "save-role"]);

const roles = ref(props.roles);
const modules = ref(props.modules);
const permissions = ref(props.permissions);

const selectedRole = ref(null);
const editRoleNameMode = ref(false);

watch(
    () => props.roles,
    () => {
        roles.value = props.roles;
    },
);

watch(
    () => [selectedRole.value],
    () => {
        props.formRolePermissions.name = selectedRole.value?.name || "";
        props.formRolePermissions.permissions =
            selectedRole.value?.permissions?.map((p) => p.id) || [];
    },
);

const modelHasChanged = computed({
    get() {
        const setUno = new Set(props.formRolePermissions.permissions);
        const setDos = new Set(
            selectedRole.value?.permissions.map((p) => p.id),
        );
        return (
            setUno.difference(setDos).size > 0 ||
            setDos.difference(setUno).size > 0 ||
            (selectedRole.value &&
                !editRoleNameMode.value &&
                props.formRolePermissions.name !== selectedRole.value?.name)
        );
    },
    set(value) {
        props.formRolePermissions.permissions = value;
        selectedRole.value = roles.value.find(
            (role) => role.id === selectedRole.value.id,
        );
    },
});

const handleSaveRole = () => {
    emit(
        "save-role",
        selectedRole.value,
        modelHasChanged.value,
        (newPermissions) => {
            modelHasChanged.value = newPermissions;
        },
    );
};

const selectedRoleAvatarLabel = computed(
    () => selectedRole.value?.name?.slice(0, 2).toUpperCase() || "",
);

const permissionsByModule = ref({});
watch(
    () => props.formRolePermissions.permissions,
    () => {
        const objModules = {};
        for (const permissionId of props.formRolePermissions.permissions) {
            const permission = permissions.value.find(
                (p) => p.id === permissionId,
            );
            if (permission) {
                if (!objModules[permission.module.name]) {
                    objModules[permission.module.name] = [];
                }
                objModules[permission.module.name].push(permission);
            }
        }

        permissionsByModule.value = objModules;
    },
);
</script>

<template>
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 xl:gap-0 min-h-screen">
        <RoleSidebar
            :roles="roles"
            :selectedRole="selectedRole"
            @update:selectedRole="selectedRole = $event"
            @add-role="emit('add-role')"
        />

        <RoleForm
            :formRolePermissions="props.formRolePermissions"
            :role="selectedRole"
            :editRoleNameMode="editRoleNameMode"
            :modelHasChanged="modelHasChanged"
            :modules="modules"
            :permissions="permissions"
            :permissionsByModule="permissionsByModule"
            :selectedRoleAvatarLabel="selectedRoleAvatarLabel"
            @update:editRoleNameMode="editRoleNameMode = $event"
            @submit="handleSaveRole"
        />
    </div>
</template>