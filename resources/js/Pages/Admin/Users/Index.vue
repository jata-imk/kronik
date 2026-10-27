<script setup>
import PageHeader from "@/Components/PageHeader.vue";
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import ConfirmDialog from "primevue/confirmdialog";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import CollectionSummary from "@/Components/DataTable/CollectionSummary.vue";
import TruncatedText from "@/Components/DataTable/TruncatedText.vue";
import UserForm from "@/Components/Users/UserFormDialog.vue";
import UserActions from "@/Components/Users/UserActions.vue";

const page = usePage();
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
</script>

<template>
    <AppLayout title="Usuarios">
        <template #card-header>
            <PageHeader eyebrow="Administración · Accesos" title="Gestión de usuarios" description="Equipos, roles y sucursales asignadas.">
                <template #actions><Button icon="pi pi-arrow-left" label="Volver" severity="secondary" as="a" :href="route('admin.dashboard')" /><Button v-if="can('create-users')" label="Invitar usuario" icon="pi pi-user-plus" @click="openCreate" /></template>
            </PageHeader>
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
                <Column header="Invitación">
                    <template #body="{ data }">
                        <div v-if="data.status === 'pending'" class="text-sm">
                            <p>{{ data.invited_at ? `Enviada: ${new Date(data.invited_at).toLocaleString('es-MX')}` : 'Sin envío registrado' }}</p>
                            <p v-if="data.invitation_expires_at">Vence: {{ new Date(data.invitation_expires_at).toLocaleString('es-MX') }}</p>
                            <Tag v-if="data.invitation_expired" value="Sin enlace vigente" severity="warn" />
                        </div>
                        <span v-else>—</span>
                    </template>
                </Column>
                <Column field="sucursal_search" header="Sucursales" :show-filter-menu="false">
                    <template #body="{ data }"><Tag v-if="data.is_super_admin" value="Acceso global" severity="contrast" /><CollectionSummary v-else :items="data.sucursal_names" max-width="20rem" /></template>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Sucursal" @input="filterCallback()" /></template>
                </Column>
                <Column header="Acciones" class="w-44">
                    <template #body="{ data }"><UserActions :user="data" @edit="openEdit(data)" /></template>
                </Column>
            </DataTable>
            </div>

            <UserForm v-if="showForm" :user="selectedUser" :teams="page.props.teams" :roles="page.props.roles" :sucursales="page.props.sucursales" :status-options="page.props.statusOptions" :can-manage-super-admin="page.props.canManageSuperAdmin" :prefill="activePrefill" @close="showForm = false" />
        </template>
    </AppLayout>
</template>
