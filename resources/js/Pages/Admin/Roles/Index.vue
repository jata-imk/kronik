<script setup>
import PageHeader from "@/Components/PageHeader.vue";
import { ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import CreateRoleModal from "./CreateRoleModal.vue";
import RolePanel from "@components/Role/RolePanel.vue";

const toast = useToast();
const page = usePage();

const formRolePermissions = useForm({
    name: "",
    permissions: [],
});

const createRoleModalIsOpen = ref(false);

const closeCreateRoleModal = () => {
    createRoleModalIsOpen.value = false;
};

const submit = (selectedRole, modelHasChangedCallback) => {
    formRolePermissions.put(route("admin.roles.update", selectedRole.id), {
        only: ["roles"],
        onSuccess: () => {
            createRoleModalIsOpen.value = false;
            modelHasChangedCallback(formRolePermissions.permissions);
            toast.add({
                severity: "success",
                summary: "Actualizado",
                detail: "El rol se ha actualizado correctamente",
                life: 5000,
            });
        },
    });
};
</script>

<template>
    <AppLayout title="Roles y permisos" :pt="{ 'card-content-body': '!p-0' }">
        <template #card-header>
            <PageHeader eyebrow="Administración · Accesos" title="Roles y permisos" description="Configura los permisos de cada rol.">
                <template #actions><Button icon="pi pi-arrow-left" label="Volver" severity="secondary" as="a" :href="route('admin.dashboard')" /></template>
            </PageHeader>
        </template>

        <template #card-content>
            <RolePanel
                :roles="page.props.roles"
                :role-members="page.props.roleMembers"
                :modules="page.props.modules"
                :permissions="page.props.permissions"
                :form-role-permissions="formRolePermissions"
                @add-role="createRoleModalIsOpen = true"
                @save-role="(selectedRole, modelHasChanged, modelHasChangedCallback) => {
                    modelHasChanged && submit(selectedRole, modelHasChangedCallback);
                }" />

            <CreateRoleModal v-model:visible="createRoleModalIsOpen" @close="closeCreateRoleModal" />
            <ConfirmDialog></ConfirmDialog>
        </template>
    </AppLayout>
</template>
