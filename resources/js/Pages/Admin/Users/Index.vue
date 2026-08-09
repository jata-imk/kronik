<script setup>
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import { useToast } from "primevue/usetoast";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import UserForm from "./Form.vue";

const page = usePage();
const toast = useToast();
const users = computed(() => page.props.users ?? []);
const selectedUser = ref(null);
const showForm = ref(false);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const openCreate = () => {
    selectedUser.value = null;
    showForm.value = true;
};
const openEdit = (user) => {
    selectedUser.value = user;
    showForm.value = true;
};
const resendInvitation = (user) => {
    router.post(route("admin.users.invitation.resend", user.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: "success", summary: "Invitación reenviada", life: 3000 }),
    });
};
const deactivate = (user) => {
    if (!confirm(`¿Desactivar la cuenta de ${user.name}?`)) return;
    router.delete(route("admin.users.destroy", user.id), {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: "success", summary: "Usuario desactivado", life: 3000 }),
    });
};
</script>

<template>
    <AppLayout title="Usuarios" :pt="{ 'card-content-body': '!p-0' }">
        <template #card-header>
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center">
                    <Button icon="pi pi-arrow-left" as="a" :href="route('admin.dashboard')" />
                    <h2 class="ml-4 text-2xl font-bold">Gestión de usuarios</h2>
                </div>
                <Button label="Invitar usuario" icon="pi pi-user-plus" @click="openCreate" />
            </div>
        </template>

        <template #card-content>
            <DataTable
                v-model:filters="filters"
                :value="users"
                :global-filter-fields="['name', 'email', 'status_label']"
                paginator
                :rows="10"
            >
                <template #header>
                    <div class="flex justify-end p-2">
                        <InputText v-model="filters.global.value" placeholder="Buscar usuarios" />
                    </div>
                </template>
                <Column field="name" header="Nombre" sortable />
                <Column field="email" header="Correo" sortable />
                <Column header="Estado">
                    <template #body="{ data }">
                        <Tag
                            :value="data.status_label"
                            :severity="data.status === 'active' ? 'success' : data.status === 'pending' ? 'warn' : 'secondary'"
                        />
                    </template>
                </Column>
                <Column header="Acceso global">
                    <template #body="{ data }">
                        <Tag v-if="data.is_super_admin" value="Super Admin" severity="danger" />
                        <span v-else>—</span>
                    </template>
                </Column>
                <Column header="Acciones" class="w-44">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" text @click="openEdit(data)" />
                            <Button
                                v-if="data.status === 'pending'"
                                icon="pi pi-send"
                                text
                                v-tooltip.top="'Reenviar invitación'"
                                @click="resendInvitation(data)"
                            />
                            <Button
                                v-if="data.status === 'active'"
                                icon="pi pi-ban"
                                severity="danger"
                                text
                                @click="deactivate(data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <UserForm
                v-if="showForm"
                :user="selectedUser"
                :teams="page.props.teams"
                :roles="page.props.roles"
                :sucursales="page.props.sucursales"
                :status-options="page.props.statusOptions"
                :can-manage-super-admin="page.props.canManageSuperAdmin"
                @close="showForm = false"
            />
        </template>
    </AppLayout>
</template>
