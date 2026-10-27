<script setup>
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import UpdateTeamNameForm from "@/Pages/Teams/Partials/UpdateTeamNameForm.vue";
import CollectionSummary from "@/Components/DataTable/CollectionSummary.vue";
import TruncatedText from "@/Components/DataTable/TruncatedText.vue";
import TeamGeneralActions from "@/Components/Teams/TeamGeneralActions.vue";
import UserFormDialog from "@/Components/Users/UserFormDialog.vue";
import UserActions from "@/Components/Users/UserActions.vue";

const props = defineProps({
    team: Object,
    members: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
    sucursales: { type: Array, default: () => [] },
    canManageUsers: Boolean,
    teamCapabilities: { type: Object, default: () => ({}) },
    responsibleCandidates: { type: Array, default: () => [] },
    teamRoles: { type: Array, default: () => [] },
    recentActivity: { type: Array, default: () => [] },
    pendingInvitations: { type: Array, default: () => [] },
    userFormOptions: { type: Object, default: () => ({}) },
});
const page = usePage();
const showUserForm = ref(false);
const selectedUser = ref(null);
const userPrefill = ref({});
const editUser = (user) => { selectedUser.value = user; userPrefill.value = {}; showUserForm.value = true; };
const inviteUser = (roleId) => {
    selectedUser.value = null;
    userPrefill.value = { team_id: props.team.id, role_id: roleId, sucursal_id: page.props.auth.user.current_sucursal_id };
    showUserForm.value = true;
};
const auth = computed(() => page.props.auth);
const memberRoles = (member) =>
    Array.from(
        new Set([
            ...(member.is_super_admin ? ["Super Admin"] : []),
            ...member.roles.map((role) => role.name),
        ]),
    );
const tableMembers = computed(() =>
    props.members.map((member) => ({
        ...member,
        roles_names: memberRoles(member),
        roles_search: memberRoles(member).join(", ") || "—",
        sucursales_names: member.sucursales.map((branch) => branch.clave),
        sucursales_search:
            member.is_super_admin && !member.sucursales.length
                ? "Acceso global"
                : member.sucursales
                      .map((branch) => `${branch.clave} · ${branch.nombre}`)
                      .join(", ") || "—",
    })),
);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status_label: { value: null, matchMode: FilterMatchMode.EQUALS },
    roles_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
    sucursales_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const statuses = ["Activo", "Pendiente", "Inactivo"];
</script>

<template>
    <AppLayout title="Configuración del equipo">
        <template #header>
            <PageHeader :eyebrow="team.id === auth.user.current_team_id ? 'Cuenta · Equipo actual' : 'Cuenta · Equipo seleccionado'" :title="`Configuración del equipo: ${team.name}`" description="Permisos, integrantes y contexto organizativo.">
                <template #actions>
                    <Button v-if="auth.is_super_admin || auth.permissions['read-teams']" label="Volver a equipos" severity="secondary" as="a" :href="route('admin.teams.index')" />
                    <Tag :value="team.activo ? 'Activo' : 'Inactivo'" :severity="team.activo ? 'success' : 'secondary'" />
                    <Tag :value="team.personal_team ? 'Personal' : 'Institucional'" severity="info" />
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto max-w-7xl py-8 sm:px-6 lg:px-8">
            <UpdateTeamNameForm :team="team" :permissions="teamCapabilities" />
            <TeamGeneralActions :team="team" :capabilities="teamCapabilities" :candidates="responsibleCandidates" />

            <Card class="mt-8">
                <template #title>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2"><span>Miembros</span><Badge :value="team.members_count" /></div>
                        <Button
                            v-if="team.activo && canManageUsers && (auth.permissions['create-users'] || auth.is_super_admin)"
                            label="Invitar usuario"
                            icon="pi pi-user-plus"
                            @click="inviteUser()"
                        />
                    </div>
                </template>
                <template #content>
                    <Message v-if="!canManageUsers" severity="info" :closable="false">
                        Puedes consultar los datos básicos del equipo. La información de usuarios requiere permiso para administrar usuarios.
                    </Message>

                    <DataTable
                        v-else
                        v-model:filters="filters"
                        :value="tableMembers"
                        :global-filter-fields="['name', 'email', 'status_label', 'roles_search', 'sucursales_search']"
                        filter-display="row"
                        paginator
                        :rows="10"
                        striped-rows
                        responsive-layout="scroll"
                    >
                        <template #header>
                            <div class="flex flex-wrap justify-between gap-3">
                                <span class="text-sm text-surface-500">Las acciones de cuenta afectan todos los equipos; quitar una membresía solo afecta este equipo.</span>
                                <IconField>
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="filters.global.value" placeholder="Buscar miembros" />
                                </IconField>
                            </div>
                        </template>
                        <Column field="name" header="Usuario" sortable>
                            <template #body="{ data }">
                                <div class="max-w-80">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <TruncatedText :value="data.name" max-width="18rem" />
                                        <span
                                            v-if="data.is_owner"
                                            class="inline-flex shrink-0 text-amber-500"
                                            aria-label="Responsable del equipo"
                                            v-tooltip.top="'Responsable del equipo'"
                                        >
                                            <i class="pi pi-star-fill" aria-hidden="true" />
                                        </span>
                                    </div>
                                    <TruncatedText class="text-sm text-surface-500" :value="data.email" max-width="20rem" />
                                </div>
                            </template>
                            <template #filter="{ filterModel, filterCallback }">
                                <InputText v-model="filterModel.value" placeholder="Nombre" @input="filterCallback()" />
                            </template>
                        </Column>
                        <Column field="status_label" header="Estado" sortable show-filter-menu>
                            <template #body="{ data }">
                                <Tag :value="data.status_label" :severity="data.status === 'active' ? 'success' : data.status === 'pending' ? 'warn' : 'secondary'" />
                            </template>
                            <template #filter="{ filterModel, filterCallback }">
                                <Select v-model="filterModel.value" :options="statuses" placeholder="Todos" show-clear @change="filterCallback()" />
                            </template>
                        </Column>
                        <Column field="roles_search" header="Roles">
                            <template #body="{ data }">
                                <CollectionSummary :items="data.roles_names" appearance="contrast" max-width="18rem" />
                            </template>
                            <template #filter="{ filterModel, filterCallback }">
                                <InputText v-model="filterModel.value" placeholder="Rol" @input="filterCallback()" />
                            </template>
                        </Column>
                        <Column field="sucursales_search" header="Sucursales">
                            <template #body="{ data }">
                                <Tag v-if="data.is_super_admin && !data.sucursales.length" value="Acceso global" severity="contrast" />
                                <CollectionSummary v-else class="min-w-32" :items="data.sucursales_names" max-width="20rem" />
                            </template>
                            <template #filter="{ filterModel, filterCallback }">
                                <InputText v-model="filterModel.value" placeholder="Sucursal" @input="filterCallback()" />
                            </template>
                        </Column>
                        <Column header="Acciones">
                            <template #body="{ data }">
                                <UserActions :user="data" :team="team" :can-remove="teamCapabilities['remove-members-teams']" @edit="editUser(data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <div v-if="team.activo && canManageUsers && roles.length && (auth.is_super_admin || auth.permissions['create-users'])" class="mt-6 border-t border-surface-200 pt-4 dark:border-surface-700">
                        <div class="mb-2 text-sm font-medium">Invitar con rol preseleccionado</div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="role in roles"
                                :key="role.id"
                                :label="role.name"
                                icon="pi pi-plus"
                                severity="secondary"
                                size="small"
                                @click="inviteUser(role.id)"
                            />
                        </div>
                    </div>
                </template>
            </Card>
            <Card v-if="canManageUsers" class="mt-8">
                <template #title>Invitaciones pendientes</template>
                <template #content>
                    <p v-if="!pendingInvitations.length">No hay invitaciones pendientes.</p>
                    <div v-for="invitation in pendingInvitations" :key="invitation.id" class="mb-4 rounded-lg border border-surface-200 p-4 dark:border-surface-700">
                        <p class="font-medium">{{ invitation.name }}</p><p>{{ invitation.email }}</p>
                        <p>{{ invitation.invited_at ? `Enviada: ${new Date(invitation.invited_at).toLocaleString('es-MX')}` : 'Sin envío registrado' }}</p>
                        <p v-if="invitation.invitation_expires_at">Vence: {{ new Date(invitation.invitation_expires_at).toLocaleString('es-MX') }}</p>
                        <Tag v-if="invitation.invitation_expired" value="Sin enlace vigente" severity="warn" />
                        <UserActions :user="invitation" :team="team" :can-remove="teamCapabilities['remove-members-teams']" @edit="editUser(invitation)" />
                    </div>
                </template>
            </Card>
            <Card v-if="teamCapabilities['read-roles']" class="mt-8">
                <template #title>Roles y permisos</template>
                <template #content>
                    <Button label="Administrar roles" as="a" :href="route('admin.roles.index', { team: team.id })" />
                    <p v-if="!teamRoles.length" class="mt-4">Este equipo aún no tiene roles.</p>
                    <div v-for="role in teamRoles" :key="role.id" class="mt-4 rounded-lg border border-surface-200 p-4 dark:border-surface-700">
                        <p class="font-medium">{{ role.name }}</p><p>{{ role.members_count }} personas asignadas</p>
                        <CollectionSummary :items="role.modules" max-width="24rem" />
                    </div>
                </template>
            </Card>
            <Card v-if="teamCapabilities['read-activity-log']" class="mt-8">
                <template #title>Actividad reciente</template>
                <template #content>
                    <Button label="Ver bitácora del equipo" as="a" :href="route('admin.users.activity', { team_id: team.id })" />
                    <p v-if="!recentActivity.length" class="mt-4">No hay actividad reciente.</p>
                    <p v-for="activity in recentActivity" :key="activity.id" class="mt-3">{{ activity.description }} · {{ new Date(activity.created_at).toLocaleString('es-MX') }}</p>
                </template>
            </Card>
        </div>
        <ConfirmDialog />
        <UserFormDialog v-if="showUserForm" :user="selectedUser" v-bind="userFormOptions" :prefill="userPrefill" @close="showUserForm = false" />
    </AppLayout>
</template>
