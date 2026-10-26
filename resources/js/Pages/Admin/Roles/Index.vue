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
    formRolePermissions.put(route("admin.roles.update", { role: selectedRole.id, team: page.props.roleTeam.id }), {
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
            <PageHeader eyebrow="Administración · Accesos" title="Roles y permisos" :description="`Configura los permisos de ${page.props.roleTeam.name}. El equipo actual no cambia.`">
                <template #actions><Button icon="pi pi-arrow-left" label="Volver" severity="secondary" as="a" :href="route('teams.show', page.props.roleTeam.id)" /></template>
            </PageHeader>
        </template>

        <template #card-content>
            <Message v-for="(error, field) in formRolePermissions.errors" :key="field" severity="error">{{ error }}</Message>
            <RolePanel
                :roles="page.props.roles"
                :role-members="page.props.roleMembers"
                :modules="page.props.modules"
                :permissions="page.props.permissions"
                :form-role-permissions="formRolePermissions"
                :capabilities="page.props.roleCapabilities"
                :team-id="page.props.roleTeam.id"
                @add-role="createRoleModalIsOpen = true"
                @save-role="(selectedRole, modelHasChanged, modelHasChangedCallback) => {
                    modelHasChanged && submit(selectedRole, modelHasChangedCallback);
                }" />

            <CreateRoleModal :team-id="page.props.roleTeam.id" v-model:visible="createRoleModalIsOpen" @close="closeCreateRoleModal" />
            <ConfirmDialog></ConfirmDialog>
        </template>
    </AppLayout>
</template>
