<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";

import RoleSidebar from "@components/Role/RoleSidebar.vue";
import RoleForm from "@components/Role/RoleForm.vue";

const props = defineProps({
    roles: Array,
    selectedRole: Object,
    teamId: Number,
    roleMembers: Object,
    modules: Array,
    permissions: Array,
    formRolePermissions: Object,
    capabilities: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["add-role", "save-role"]);

const roles = ref(props.roles);
const modules = ref(props.modules);
const permissions = ref(props.permissions);

const selectedRole = ref(props.selectedRole ?? null);
watch(() => props.selectedRole, value => {
    if (JSON.stringify(value) !== JSON.stringify(selectedRole.value)) selectedRole.value = value;
});
function selectRole(role) {
    if (!role || role.id === selectedRole.value?.id) return;
    selectedRole.value = role;
    const url = new URL(window.location.href);
    router.get(url.pathname, { ...Object.fromEntries(url.searchParams.entries()), selected_role_id: role.id, integrantes_page: 1 }, { preserveState: true, preserveScroll: true, replace: true });
}
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
    { immediate: true },
);

const modelHasChanged = computed({
    get() {
        const setUno = new Set(props.formRolePermissions.permissions);
        const setDos = new Set(
            selectedRole.value?.permissions.map((p) => p.id),
        );
        return (
            [...setUno].some((id) => !setDos.has(id)) ||
            [...setDos].some((id) => !setUno.has(id)) ||
            (selectedRole.value &&
                !editRoleNameMode.value &&
                props.formRolePermissions.name !== selectedRole.value?.name)
        );
    },
    set(value) {
        props.formRolePermissions.permissions = value;
        selectedRole.value = props.selectedRole ?? roles.value.find(
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
                const moduleLabel = permission.module?.etiqueta || "Otros permisos";
                if (!objModules[moduleLabel]) {
                    objModules[moduleLabel] = [];
                }
                objModules[moduleLabel].push(permission);
            }
        }

        permissionsByModule.value = objModules;
    },
);
</script>

<template>
    <div class="grid min-w-0 grid-cols-1 gap-4 xl:min-h-screen xl:grid-cols-12 xl:gap-0">
        <RoleSidebar
            :roles="roles"
            :can-create="capabilities.create"
            :selectedRole="selectedRole"
            @update:selectedRole="selectRole($event)"
            @add-role="emit('add-role')"
        />

        <RoleForm
            :formRolePermissions="props.formRolePermissions"
            :capabilities="capabilities"
            :team-id="teamId"
            :role="selectedRole"
            :members="selectedRole ? (roleMembers?.[selectedRole.id] ?? []) : []"
            :editRoleNameMode="editRoleNameMode"
            :modelHasChanged="modelHasChanged"
            :modules="modules"
            :permissions="permissions"
            :permissionsByModule="permissionsByModule"
            :selectedRoleAvatarLabel="selectedRoleAvatarLabel"
            @update:editRoleNameMode="editRoleNameMode = $event"
            @submit="handleSaveRole"
            @deleted="selectedRole = null"
        />
    </div>
</template>
