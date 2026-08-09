<script setup>
import { computed, ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    user: { type: Object, default: null },
    teams: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
    sucursales: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    canManageSuperAdmin: Boolean,
});
const emit = defineEmits(["close"]);
const toast = useToast();
const visible = ref(true);

const initialTeamRoles = props.user?.team_roles?.length
    ? props.user.team_roles.map((item) => ({ ...item, role_ids: [...item.role_ids] }))
    : props.teams[0]
      ? [{ team_id: props.teams[0].id, role_ids: [] }]
      : [];
const selectedTeamIds = ref(initialTeamRoles.map((item) => item.team_id));
const form = useForm({
    name: props.user?.name ?? "",
    email: props.user?.email ?? "",
    status: props.user?.status ?? "pending",
    is_super_admin: props.user?.is_super_admin ?? false,
    current_team_id: props.user?.current_team_id ?? initialTeamRoles[0]?.team_id ?? null,
    team_roles: initialTeamRoles,
    sucursal_ids: props.user?.sucursal_ids?.length
        ? [...props.user.sucursal_ids]
        : props.sucursales[0]
          ? [props.sucursales[0].id]
          : [],
    sucursal_principal_id:
        props.user?.sucursal_principal_id ?? props.sucursales[0]?.id ?? null,
});

watch(
    selectedTeamIds,
    (ids) => {
        const existing = new Map(form.team_roles.map((item) => [item.team_id, item]));
        form.team_roles = ids.map(
            (teamId) => existing.get(teamId) ?? { team_id: teamId, role_ids: [] },
        );
        if (!ids.includes(form.current_team_id)) {
            form.current_team_id = ids[0] ?? null;
        }
    },
    { deep: true },
);

watch(
    () => form.sucursal_ids,
    (ids) => {
        if (!ids.includes(form.sucursal_principal_id)) {
            form.sucursal_principal_id = ids[0] ?? null;
        }
    },
    { deep: true },
);

const selectedTeams = computed(() =>
    form.team_roles.map((assignment) => ({
        assignment,
        team: props.teams.find((team) => team.id === assignment.team_id),
        roles: props.roles.filter((role) => role.team_id === assignment.team_id),
    })),
);
const availableCurrentTeams = computed(() =>
    props.teams.filter((team) => selectedTeamIds.value.includes(team.id)),
);
const availablePrincipalBranches = computed(() =>
    props.sucursales.filter((branch) => form.sucursal_ids.includes(branch.id)),
);

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: props.user ? "Usuario actualizado" : "Invitación enviada",
                life: 3000,
            });
            emit("close");
        },
    };

    if (props.user) {
        form.put(route("admin.users.update", props.user.id), options);
    } else {
        form.post(route("admin.users.store"), options);
    }
};
</script>

<template>
    <Dialog
        v-model:visible="visible"
        :header="user ? 'Editar usuario' : 'Invitar usuario'"
        modal
        :style="{ width: '760px' }"
        @hide="emit('close')"
    >
        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <Message v-if="Object.keys(form.errors).length" severity="error">
                Revisa los campos marcados antes de guardar.
            </Message>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Nombre *</label>
                    <InputText v-model="form.name" fluid />
                    <small class="text-red-500">{{ form.errors.name }}</small>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Correo electrónico *</label>
                    <InputText v-model="form.email" type="email" fluid />
                    <small class="text-red-500">{{ form.errors.email }}</small>
                </div>
                <div v-if="user">
                    <label class="mb-1 block text-sm font-medium">Estado</label>
                    <Select
                        v-model="form.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                    <small class="text-red-500">{{ form.errors.status }}</small>
                </div>
                <div v-if="canManageSuperAdmin" class="flex items-center gap-2 pt-6">
                    <Checkbox v-model="form.is_super_admin" input-id="super-admin" binary />
                    <label for="super-admin">Super Admin global</label>
                </div>
            </div>

            <Divider />
            <section class="flex flex-col gap-4">
                <h3 class="font-semibold">Equipos y roles</h3>
                <div>
                    <label class="mb-1 block text-sm font-medium">Equipos asignados *</label>
                    <MultiSelect
                        v-model="selectedTeamIds"
                        :options="teams"
                        option-label="name"
                        option-value="id"
                        display="chip"
                        filter
                        fluid
                    />
                    <small class="text-red-500">{{ form.errors.team_roles }}</small>
                </div>
                <div v-for="item in selectedTeams" :key="item.assignment.team_id" class="rounded border p-3">
                    <label class="mb-2 block text-sm font-medium">Roles en {{ item.team?.name }}</label>
                    <MultiSelect
                        v-model="item.assignment.role_ids"
                        :options="item.roles"
                        option-label="name"
                        option-value="id"
                        display="chip"
                        placeholder="Sin roles funcionales"
                        fluid
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Equipo actual inicial *</label>
                    <Select
                        v-model="form.current_team_id"
                        :options="availableCurrentTeams"
                        option-label="name"
                        option-value="id"
                        fluid
                    />
                    <small class="text-red-500">{{ form.errors.current_team_id }}</small>
                </div>
            </section>

            <Divider />
            <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Sucursales asignadas *</label>
                    <MultiSelect
                        v-model="form.sucursal_ids"
                        :options="sucursales"
                        option-label="nombre"
                        option-value="id"
                        display="chip"
                        filter
                        fluid
                    />
                    <small class="text-red-500">{{ form.errors.sucursal_ids }}</small>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Sucursal principal *</label>
                    <Select
                        v-model="form.sucursal_principal_id"
                        :options="availablePrincipalBranches"
                        option-label="nombre"
                        option-value="id"
                        fluid
                    />
                    <small class="text-red-500">{{ form.errors.sucursal_principal_id }}</small>
                </div>
            </section>

            <div class="flex justify-end gap-2">
                <Button label="Cancelar" severity="secondary" @click.prevent="emit('close')" />
                <Button label="Guardar" icon="pi pi-check" type="submit" :loading="form.processing" />
            </div>
        </form>
    </Dialog>
</template>
