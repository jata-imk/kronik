<script setup>
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import { useConfirm } from "primevue/useconfirm";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import UserForm from "./Form.vue";

const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const users = computed(() => page.props.users ?? []);
const can = (permission) => page.props.auth.is_super_admin || page.props.auth.permissions?.[permission] === true;
const initialPrefill = page.props.prefill ?? {};
const selectedUser = ref(users.value.find((user) => user.id === Number(initialPrefill.edit_user_id)) ?? null);
const activePrefill = ref(initialPrefill);
const showForm = ref(Boolean(initialPrefill.invite || selectedUser.value));
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status_label: { value: null, matchMode: FilterMatchMode.EQUALS },
    team_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
    sucursal_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const statusLabels = computed(() => (page.props.statusOptions ?? []).map((status) => status.label));

const openCreate = () => {
    selectedUser.value = null;
    activePrefill.value = {};
    showForm.value = true;
};
const openEdit = (user) => {
    selectedUser.value = user;
    activePrefill.value = {};
    showForm.value = true;
};
const resendInvitation = (user) => {
    router.post(route("admin.users.invitation.resend", user.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: "success", summary: "Invitación reenviada", life: 3000 }),
        onError: (errors) => toast.add({ severity: "error", summary: "No se pudo reenviar", detail: Object.values(errors)[0], life: 6000 }),
    });
};
const deactivate = (user) => {
    confirm.require({
        header: "Desactivar usuario",
        message: `¿Desactivar la cuenta de ${user.name}? Su historial se conservará.`,
        icon: "pi pi-exclamation-triangle",
        rejectLabel: "Cancelar",
        acceptLabel: "Desactivar",
        acceptClass: "p-button-danger",
        accept: () => router.delete(route("admin.users.destroy", user.id), {
            preserveScroll: true,
            onSuccess: () => toast.add({ severity: "success", summary: "Usuario desactivado", life: 3000 }),
            onError: (errors) => toast.add({
                severity: "error",
                summary: "No se pudo desactivar",
                detail: errors.user ?? Object.values(errors)[0] ?? "Revisa los permisos de la cuenta.",
                life: 6000,
            }),
        }),
    });
};
</script>

<template>
    <AppLayout title="Usuarios" :pt="{ 'card-content-body': '!p-0' }">
        <template #card-header>
            <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                <div class="flex items-center">
                    <Button icon="pi pi-arrow-left" as="a" :href="route('admin.dashboard')" />
                    <div class="ml-4"><h2 class="text-2xl font-bold">Gestión de usuarios</h2><p class="text-sm text-surface-500">Equipos, roles y sucursales asignadas.</p></div>
                </div>
                <Button v-if="can('create-users')" label="Invitar usuario" icon="pi pi-user-plus" @click="openCreate" />
            </div>
        </template>

        <template #card-content>
            <ConfirmDialog />
            <DataTable v-model:filters="filters" :value="users" :global-filter-fields="['name', 'email', 'status_label', 'team_search', 'sucursal_search']" paginator :rows="10" striped-rows responsive-layout="scroll">
                <template #header><div class="flex justify-end p-2"><IconField><InputIcon class="pi pi-search" /><InputText v-model="filters.global.value" placeholder="Buscar por nombre, correo, estado, equipo o sucursal" class="w-96 max-w-full" /></IconField></div></template>
                <Column field="name" header="Nombre" sortable>
                    <template #body="{ data }"><div class="font-medium">{{ data.name }}</div></template>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Nombre" @input="filterCallback()" /></template>
                </Column>
                <Column field="email" header="Correo" sortable>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Correo" @input="filterCallback()" /></template>
                </Column>
                <Column field="status_label" header="Estado" sortable>
                    <template #body="{ data }"><Tag :value="data.status_label" :severity="data.status === 'active' ? 'success' : data.status === 'pending' ? 'warn' : 'secondary'" /></template>
                    <template #filter="{ filterModel, filterCallback }"><Select v-model="filterModel.value" :options="statusLabels" placeholder="Todos" show-clear @change="filterCallback()" /></template>
                </Column>
                <Column field="team_search" header="Equipos">
                    <template #body="{ data }"><div class="flex max-w-72 flex-wrap gap-1"><Chip v-for="name in data.team_names" :key="name" :label="name" /></div></template>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Equipo" @input="filterCallback()" /></template>
                </Column>
                <Column field="sucursal_search" header="Sucursales">
                    <template #body="{ data }"><div class="flex max-w-72 flex-wrap gap-1"><Chip v-for="name in data.sucursal_names" :key="name" :label="name" /><span v-if="data.is_super_admin" class="text-sm text-surface-500">Acceso global</span></div></template>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Sucursal" @input="filterCallback()" /></template>
                </Column>
                <Column header="Acceso global"><template #body="{ data }"><Tag v-if="data.is_super_admin" value="Super Admin" severity="danger" /><span v-else>—</span></template></Column>
                <Column header="Acciones" frozen align-frozen="right" class="w-44">
                    <template #body="{ data }"><div class="flex gap-1"><Button v-if="can('update-users')" :aria-label="`Editar ${data.name}`" icon="pi pi-pencil" text v-tooltip.top="'Editar'" @click="openEdit(data)" /><Button v-if="can('create-users') && data.status === 'pending'" :aria-label="`Reenviar invitación a ${data.name}`" icon="pi pi-send" text v-tooltip.top="'Reenviar invitación'" @click="resendInvitation(data)" /><Button v-if="can('delete-users') && data.status === 'active'" :aria-label="`Desactivar ${data.name}`" icon="pi pi-ban" severity="danger" text v-tooltip.top="'Desactivar'" @click="deactivate(data)" /></div></template>
                </Column>
            </DataTable>

            <UserForm v-if="showForm" :user="selectedUser" :teams="page.props.teams" :roles="page.props.roles" :sucursales="page.props.sucursales" :status-options="page.props.statusOptions" :can-manage-super-admin="page.props.canManageSuperAdmin" :prefill="activePrefill" @close="showForm = false" />
        </template>
    </AppLayout>
</template>
