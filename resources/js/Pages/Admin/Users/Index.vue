<script setup>
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import { useConfirm } from "primevue/useconfirm";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import CollectionSummary from "@/Components/DataTable/CollectionSummary.vue";
import TruncatedText from "@/Components/DataTable/TruncatedText.vue";
import UserForm from "./Form.vue";

const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const users = computed(() =>
    (page.props.users ?? []).map((user) => ({
        ...user,
        user_search: `${user.name} ${user.email}`,
        team_search: user.team_search || user.team_names?.join(", ") || "—",
        sucursal_search: user.is_super_admin
            ? "Acceso global"
            : user.sucursal_search || user.sucursal_names?.join(", ") || "—",
    })),
);
const can = (permission) =>
    page.props.auth.is_super_admin ||
    page.props.auth.permissions?.[permission] === true;
const initialPrefill = page.props.prefill ?? {};
const selectedUser = ref(
    users.value.find(
        (user) => user.id === Number(initialPrefill.edit_user_id),
    ) ?? null,
);
const activePrefill = ref(initialPrefill);
const showForm = ref(Boolean(initialPrefill.invite || selectedUser.value));
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    user_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status_label: { value: null, matchMode: FilterMatchMode.EQUALS },
    team_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
    sucursal_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const statusLabels = computed(() =>
    (page.props.statusOptions ?? []).map((status) => status.label),
);

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
    router.post(
        route("admin.users.invitation.resend", user.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.add({
                    severity: "success",
                    summary: "Invitación reenviada",
                    life: 3000,
                }),
            onError: (errors) =>
                toast.add({
                    severity: "error",
                    summary: "No se pudo reenviar",
                    detail: Object.values(errors)[0],
                    life: 6000,
                }),
        },
    );
};
const deactivate = (user) => {
    confirm.require({
        header: "Desactivar usuario",
        message: `¿Desactivar la cuenta de ${user.name}? Su historial se conservará.`,
        icon: "pi pi-exclamation-triangle",
        rejectLabel: "Cancelar",
        acceptLabel: "Desactivar",
        acceptClass: "p-button-danger",
        accept: () =>
            router.delete(route("admin.users.destroy", user.id), {
                preserveScroll: true,
                onSuccess: () =>
                    toast.add({
                        severity: "success",
                        summary: "Usuario desactivado",
                        life: 3000,
                    }),
                onError: (errors) =>
                    toast.add({
                        severity: "error",
                        summary: "No se pudo desactivar",
                        detail:
                            errors.user ??
                            Object.values(errors)[0] ??
                            "Revisa los permisos de la cuenta.",
                        life: 6000,
                    }),
            }),
    });
};
</script>

<template>
    <AppLayout title="Usuarios">
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
            <div>
            <DataTable v-model:filters="filters" :value="users" :global-filter-fields="['name', 'email', 'status_label', 'team_search', 'sucursal_search']" filter-display="row" paginator :rows="10" striped-rows scrollable responsive-layout="scroll">
                <template #header><div class="flex justify-end p-2"><IconField><InputIcon class="pi pi-search" /><InputText v-model="filters.global.value" placeholder="Buscar por nombre, correo, estado, equipo o sucursal" class="w-96 max-w-full" /></IconField></div></template>
                <Column field="user_search" header="Usuario" sortable :show-filter-menu="false">
                    <template #body="{ data }">
                        <div class="max-w-80">
                            <div class="flex min-w-0 items-center gap-2">
                                <TruncatedText class="min-w-0 font-medium" :value="data.name" max-width="17rem" />
                                <span v-if="data.is_super_admin" class="inline-flex shrink-0 text-red-500" aria-label="Super Admin global" v-tooltip.top="'Super Admin global'">
                                    <i class="pi pi-shield" aria-hidden="true" />
                                </span>
                            </div>
                            <TruncatedText class="text-sm text-surface-500" :value="data.email" max-width="20rem" />
                        </div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Nombre o correo" @input="filterCallback()" /></template>
                </Column>
                <Column field="status_label" header="Estado" sortable :show-filter-menu="false">
                    <template #body="{ data }"><Tag :value="data.status_label" :severity="data.status === 'active' ? 'success' : data.status === 'pending' ? 'warn' : 'secondary'" /></template>
                    <template #filter="{ filterModel, filterCallback }"><Select v-model="filterModel.value" :options="statusLabels" placeholder="Todos" show-clear @change="filterCallback()" /></template>
                </Column>
                <Column field="team_search" header="Equipos" :show-filter-menu="false">
                    <template #body="{ data }"><CollectionSummary :items="data.team_names" max-width="20rem" /></template>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Equipo" @input="filterCallback()" /></template>
                </Column>
                <Column field="sucursal_search" header="Sucursales" :show-filter-menu="false">
                    <template #body="{ data }"><Tag v-if="data.is_super_admin" value="Acceso global" severity="contrast" /><CollectionSummary v-else :items="data.sucursal_names" max-width="20rem" /></template>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Sucursal" @input="filterCallback()" /></template>
                </Column>
                <Column header="Acciones" class="w-44">
                    <template #body="{ data }"><div class="flex gap-1"><Button v-if="can('update-users')" :aria-label="`Editar ${data.name}`" icon="pi pi-pencil" text @click="openEdit(data)" /><Button v-if="can('create-users') && data.status === 'pending'" :aria-label="`Reenviar invitación a ${data.name}`" icon="pi pi-send" text @click="resendInvitation(data)" /><Button v-if="can('delete-users') && data.status === 'active'" :aria-label="`Desactivar ${data.name}`" icon="pi pi-ban" severity="danger" text @click="deactivate(data)" /></div></template>
                </Column>
            </DataTable>
            </div>

            <UserForm v-if="showForm" :user="selectedUser" :teams="page.props.teams" :roles="page.props.roles" :sucursales="page.props.sucursales" :status-options="page.props.statusOptions" :can-manage-super-admin="page.props.canManageSuperAdmin" :prefill="activePrefill" @close="showForm = false" />
        </template>
    </AppLayout>
</template>
