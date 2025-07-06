<script setup>
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
            <div class="flex items-center p-4">
                <Button icon="pi pi-arrow-left" class="min-w-fit"  as="a" :href="route('admin.dashboard')"></Button>
                <h2 class="text-2xl font-bold ml-4">Configuraciones de roles y permisos</h2>
            </div>
        </template>

        <template #card-content>
            <RolePanel
                :roles="page.props.roles"
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
