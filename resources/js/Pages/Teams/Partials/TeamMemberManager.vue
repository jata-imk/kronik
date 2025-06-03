<script setup>
import { ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import ActionSection from "@/Components/ActionSection.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import DialogModal from "@/Components/DialogModal.vue";
import FormSection from "@/Components/FormSection.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SectionBorder from "@/Components/SectionBorder.vue";

const props = defineProps({
    team: Object,
    availableRoles: Array,
    userPermissions: Object,
});

const page = usePage();

const addTeamMemberForm = useForm({
    email: "",
    role: null,
});

const updateRoleForm = useForm({
    role: null,
});

const leaveTeamForm = useForm({});
const removeTeamMemberForm = useForm({});

const currentlyManagingRole = ref(false);
const managingRoleFor = ref(null);
const confirmingLeavingTeam = ref(false);
const teamMemberBeingRemoved = ref(null);

const addTeamMember = () => {
    addTeamMemberForm.post(route("team-members.store", props.team), {
        errorBag: "addTeamMember",
        preserveScroll: true,
        onSuccess: () => addTeamMemberForm.reset(),
    });
};

const cancelTeamInvitation = (invitation) => {
    router.delete(route("team-invitations.destroy", invitation), {
        preserveScroll: true,
    });
};

const manageRole = (teamMember) => {
    managingRoleFor.value = teamMember;
    updateRoleForm.role = teamMember.membership.role;
    currentlyManagingRole.value = true;
};

const updateRole = () => {
    updateRoleForm.put(
        route("team-members.update", [props.team, managingRoleFor.value]),
        {
            preserveScroll: true,
            onSuccess: () => {
                currentlyManagingRole.value = false;
            },
        },
    );
};

const confirmLeavingTeam = () => {
    confirmingLeavingTeam.value = true;
};

const leaveTeam = () => {
    leaveTeamForm.delete(
        route("team-members.destroy", [props.team, page.props.auth.user]),
    );
};

const confirmTeamMemberRemoval = (teamMember) => {
    teamMemberBeingRemoved.value = teamMember;
};

const removeTeamMember = () => {
    removeTeamMemberForm.delete(
        route("team-members.destroy", [
            props.team,
            teamMemberBeingRemoved.value,
        ]),
        {
            errorBag: "removeTeamMember",
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                teamMemberBeingRemoved.value = null;
            },
        },
    );
};

const displayableRole = (role) => {
    return props.availableRoles.find((r) => r.key === role).name;
};
</script>

<template>
    <div>
        <div v-if="userPermissions.canAddTeamMembers">
            <SectionBorder />

            <!-- Add Team Member -->
            <FormSection @submitted="addTeamMember">
                <template #title>
                    Agregar miembro del equipo
                </template>

                <template #description>
                    Agrega un nuevo miembro a tu equipo, permitiéndole colaborar contigo.
                </template>

                <template #form>
                    <div class="col-span-6">
                        <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
                            Proporcione la dirección de correo electrónico de la persona que desea agregar a este equipo.
                        </div>
                    </div>

                    <!-- Member Email -->
                    <div class="col-span-6 sm:col-span-4">
                        <InputLabel for="email" value="Email" />
                        <InputText
                            id="email"
                            v-model="addTeamMemberForm.email"
                            type="email"
                            class="mt-1 block w-full"
                        />
                        <Message v-if="addTeamMemberForm.errors.email" severity="error" size="small" class="mt-2" > {{ addTeamMemberForm.errors.email }} </Message>
                    </div>

                    <!-- Role -->
                    <div v-if="availableRoles.length > 0" class="col-span-6 lg:col-span-4">
                        <InputLabel for="roles" value="Rol" />
                        <Message v-if="addTeamMemberForm.errors.role" severity="error" size="small" class="mt-2" > {{ addTeamMemberForm.errors.role }} </Message>

                        <div class="relative z-0 mt-1 border border-gray-200 rounded-lg cursor-pointer">
                            <button
                                v-for="(role, i) in availableRoles"
                                :key="role.key"
                                type="button"
                                class="relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                :class="{'border-t border-gray-200 focus:border-none rounded-t-none': i > 0, 'rounded-b-none': i != Object.keys(availableRoles).length - 1}"
                                @click="addTeamMemberForm.role = role.key"
                            >
                                <div :class="{'opacity-50': addTeamMemberForm.role && addTeamMemberForm.role != role.key}">
                                    <!-- Role Name -->
                                    <div class="flex items-center">
                                        <div class="text-sm text-gray-600 dark:text-gray-400" :class="{'font-semibold': addTeamMemberForm.role == role.key}">
                                            {{ role.name }}
                                        </div>

                                        <svg v-if="addTeamMemberForm.role == role.key" class="ms-2 size-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>

                                    <!-- Role Description -->
                                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 text-start">
                                        {{ role.description }}
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </template>

                <template #actions>
                    <ActionMessage :on="addTeamMemberForm.recentlySuccessful" class="me-3">
                        Agregado.
                    </ActionMessage>

                    <Button severity="contrast" class="uppercase font-semibold !text-xs" :class="{ 'opacity-25': addTeamMemberForm.processing }" :disabled="addTeamMemberForm.processing">
                        Agregar
                    </Button>
                </template>
            </FormSection>
        </div>

        <div v-if="team.team_invitations.length > 0 && userPermissions.canAddTeamMembers">
            <SectionBorder />

            <!-- Team Member Invitations -->
            <ActionSection class="mt-10 sm:mt-0">
                <template #title>
                    Invitaciones de equipo pendientes
                </template>

                <template #description>
                    Estas personas han sido invitadas a tu equipo y recibieron un correo electrónico de invitación. Pueden unirse al equipo si aceptan la invitación.
                </template>

                <!-- Pending Team Member Invitation List -->
                <template #content>
                    <div class="space-y-6">
                        <div v-for="invitation in team.team_invitations" :key="invitation.id" class="flex items-center justify-between">
                            <div class="text-gray-600 dark:text-gray-400">
                                {{ invitation.email }}
                            </div>

                            <div class="flex items-center">
                                <!-- Cancel Team Invitation -->
                                <button
                                    v-if="userPermissions.canRemoveTeamMembers"
                                    class="cursor-pointer ms-6 text-sm text-red-500 focus:outline-none"
                                    @click="cancelTeamInvitation(invitation)"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </ActionSection>
        </div>

        <div v-if="team.users.length > 0">
            <SectionBorder />

            <!-- Manage Team Members -->
            <ActionSection class="mt-10 sm:mt-0">
                <template #title>
                    Miembros del equipo
                </template>

                <template #description>
                    Todas las personas que forman parte de este equipo.
                </template>

                <!-- Team Member List -->
                <template #content>
                    <div class="space-y-6">
                        <div v-for="user in team.users" :key="user.id" class="flex items-center justify-between">
                            <div class="flex items-center">
                                <img class="size-8 rounded-full object-cover" :src="user.profile_photo_url" :alt="user.name">
                                <div class="ms-4">
                                    {{ user.name }}
                                </div>
                            </div>

                            <div class="flex items-center">
                                <!-- Manage Team Member Role -->
                                <button
                                    v-if="userPermissions.canUpdateTeamMembers && availableRoles.length"
                                    class="ms-2 text-sm text-gray-400 dark:text-gray-600 underline"
                                    @click="manageRole(user)"
                                >
                                    {{ displayableRole(user.membership.role) }}
                                </button>

                                <div v-else-if="availableRoles.length" class="ms-2 text-sm text-gray-400 dark:text-gray-600">
                                    {{ displayableRole(user.membership.role) }}
                                </div>

                                <!-- Leave Team -->
                                <button
                                    v-if="$page.props.auth.user.id === user.id"
                                    class="cursor-pointer ms-6 text-sm text-red-500"
                                    @click="confirmLeavingTeam"
                                >
                                    Dejar
                                </button>

                                <!-- Remove Team Member -->
                                <button
                                    v-else-if="userPermissions.canRemoveTeamMembers"
                                    class="cursor-pointer ms-6 text-sm text-red-500"
                                    @click="confirmTeamMemberRemoval(user)"
                                >
                                    Borrar
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </ActionSection>
        </div>

        <!-- Role Management Modal -->
        <DialogModal :show="currentlyManagingRole" @close="currentlyManagingRole = false">
            <template #title>
                Administrar rol
            </template>

            <template #content>
                <div v-if="managingRoleFor">
                    <div class="relative z-0 mt-1 border border-gray-200 rounded-lg cursor-pointer">
                        <button
                            v-for="(role, i) in availableRoles"
                            :key="role.key"
                            type="button"
                            class="relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                            :class="{'border-t border-gray-200 focus:border-none rounded-t-none': i > 0, 'rounded-b-none': i !== Object.keys(availableRoles).length - 1}"
                            @click="updateRoleForm.role = role.key"
                        >
                            <div :class="{'opacity-50': updateRoleForm.role && updateRoleForm.role !== role.key}">
                                <!-- Role Name -->
                                <div class="flex items-center">
                                    <div class="text-sm text-gray-600 dark:text-gray-400" :class="{'font-semibold': updateRoleForm.role === role.key}">
                                        {{ role.name }}
                                    </div>

                                    <svg v-if="updateRoleForm.role == role.key" class="ms-2 size-5 text-green-400 dark:text-green-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>

                                <!-- Role Description -->
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                    {{ role.description }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </template>

            <template #footer>
                <Button severity="secondary" raised class="uppercase font-semibold !text-xs" @click="currentlyManagingRole = false">
                    Cancelar
                </Button>

                <Button
                    severity="contrast"
                    class="ms-3 uppercase font-semibold !text-xs"
                    :class="{ 'opacity-25': updateRoleForm.processing }"
                    :disabled="updateRoleForm.processing"
                    @click="updateRole"
                >
                    Guardar
                </Button>
            </template>
        </DialogModal>

        <!-- Leave Team Confirmation Modal -->
        <ConfirmationModal :show="confirmingLeavingTeam" @close="confirmingLeavingTeam = false">
            <template #title>
                Dejar equipo
            </template>

            <template #content>
                ¿Estás seguro de que deseas abandonar este equipo?
            </template>

            <template #footer>
                <Button severity="secondary" raised class="uppercase font-semibold !text-xs" @click="confirmingLeavingTeam = false">
                    Cancelar
                </Button>

                <Button
                    severity="danger" raised
                    class="ms-3 uppercase font-semibold !text-xs"
                    :class="{ 'opacity-25': leaveTeamForm.processing }"
                    :disabled="leaveTeamForm.processing"
                    @click="leaveTeam"
                >
                    Dejar
                </Button>
            </template>
        </ConfirmationModal>

        <!-- Remove Team Member Confirmation Modal -->
        <ConfirmationModal :show="teamMemberBeingRemoved" @close="teamMemberBeingRemoved = null">
            <template #title>
                Eliminar miembro del equipo
            </template>

            <template #content>
                ¿Estás seguro de que deseas eliminar a esta persona del equipo?
            </template>

            <template #footer>
                <Button severity="secondary" raised class="uppercase font-semibold !text-xs" @click="teamMemberBeingRemoved = null">
                    Cancelar
                </Button>

                <Button
                    severity="danger" raised
                    class="ms-3 uppercase font-semibold !text-xs"
                    :class="{ 'opacity-25': removeTeamMemberForm.processing }"
                    :disabled="removeTeamMemberForm.processing"
                    @click="removeTeamMember"
                >
                    Borrar
                </Button>
            </template>
        </ConfirmationModal>
    </div>
</template>
