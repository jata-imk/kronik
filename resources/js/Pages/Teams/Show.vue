<script setup>
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import UpdateTeamNameForm from "@/Pages/Teams/Partials/UpdateTeamNameForm.vue";

const props = defineProps({
    team: Object,
    members: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
    sucursales: { type: Array, default: () => [] },
    canManageUsers: Boolean,
});
const page = usePage();
const auth = computed(() => page.props.auth);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status_label: { value: null, matchMode: FilterMatchMode.EQUALS },
    "roles.name": { value: null, matchMode: FilterMatchMode.CONTAINS },
    "sucursales.nombre": { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const statuses = ["Activo", "Pendiente", "Inactivo"];
const userUrl = (params = {}) => route("admin.users.index", params);
const inviteParams = (extra = {}) => ({
    invite: 1,
    team_id: props.team.id,
    sucursal_id: auth.value.user.current_sucursal_id ?? undefined,
    ...extra,
});
</script>

<template>
    <AppLayout title="Configuración del equipo">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold">Configuración del equipo</h2>
                    <p class="text-sm text-surface-500">Permisos, integrantes y contexto organizativo.</p>
                </div>
                <div class="flex gap-2">
                    <Tag :value="team.activo ? 'Activo' : 'Inactivo'" :severity="team.activo ? 'success' : 'secondary'" />
                    <Tag :value="team.personal_team ? 'Personal' : 'Institucional'" severity="info" icon="pi pi-building" />
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl py-8 sm:px-6 lg:px-8">
            <UpdateTeamNameForm :team="team" :permissions="auth.permissions" />

            <Card class="mt-8">
                <template #title>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span>Miembros</span>
                            <Badge :value="team.members_count" />
                        </div>
                        <Button
                            v-if="canManageUsers && (auth.permissions['create-users'] || auth.is_super_admin)"
                            label="Invitar usuario"
                            icon="pi pi-user-plus"
                            as="a"
                            :href="userUrl(inviteParams())"
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
                        :value="members"
                        :global-filter-fields="['name', 'email', 'status_label', 'roles.name', 'sucursales.nombre']"
                        paginator
                        :rows="10"
                        striped-rows
                        responsive-layout="scroll"
                    >
                        <template #header>
                            <div class="flex flex-wrap justify-between gap-3">
                                <span class="text-sm text-surface-500">Los cambios se guardan desde el gobierno central de usuarios.</span>
                                <IconField>
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="filters.global.value" placeholder="Buscar miembros" />
                                </IconField>
                            </div>
                        </template>
                        <Column field="name" header="Usuario" sortable>
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <Avatar :image="data.profile_photo_url" shape="circle" />
                                    <div>
                                        <div class="font-medium">{{ data.name }}</div>
                                        <small class="text-surface-500">{{ data.email }}</small>
                                    </div>
                                    <Tag v-if="data.is_owner" value="Responsable" severity="info" />
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
                        <Column field="roles.name" header="Roles">
                            <template #body="{ data }">
                                <div class="flex flex-wrap gap-1">
                                    <Tag v-if="data.is_super_admin" value="Super Admin" severity="danger" />
                                    <Tag v-for="role in data.roles" :key="role.id" :value="role.name" severity="contrast" />
                                    <span v-if="!data.is_super_admin && !data.roles.length">—</span>
                                </div>
                            </template>
                            <template #filter="{ filterModel, filterCallback }">
                                <InputText v-model="filterModel.value" placeholder="Rol" @input="filterCallback()" />
                            </template>
                        </Column>
                        <Column field="sucursales.nombre" header="Sucursales">
                            <template #body="{ data }">
                                <div class="flex max-w-80 flex-wrap gap-1">
                                    <Chip v-for="branch in data.sucursales" :key="branch.id" :label="branch.clave" v-tooltip.top="branch.nombre" />
                                    <span v-if="!data.sucursales.length && data.is_super_admin" class="text-sm text-surface-500">Acceso global</span>
                                </div>
                            </template>
                            <template #filter="{ filterModel, filterCallback }">
                                <InputText v-model="filterModel.value" placeholder="Sucursal" @input="filterCallback()" />
                            </template>
                        </Column>
                        <Column header="Acciones" frozen align-frozen="right">
                            <template #body="{ data }">
                                <Button
                                    v-if="auth.permissions['update-users'] || auth.is_super_admin"
                                    icon="pi pi-pencil"
                                    text
                                    v-tooltip.top="'Editar asignaciones'"
                                    as="a"
                                    :href="userUrl({ edit_user_id: data.id })"
                                />
                            </template>
                        </Column>
                    </DataTable>

                    <div v-if="canManageUsers && roles.length" class="mt-6 border-t border-surface-200 pt-4 dark:border-surface-700">
                        <div class="mb-2 text-sm font-medium">Invitar con rol preseleccionado</div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="role in roles"
                                :key="role.id"
                                :label="role.name"
                                icon="pi pi-plus"
                                severity="secondary"
                                size="small"
                                as="a"
                                :href="userUrl(inviteParams({ role_id: role.id }))"
                            />
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
