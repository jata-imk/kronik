<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import DeleteTeamForm from "@/Pages/Teams/Partials/DeleteTeamForm.vue";
import SectionBorder from "@/Components/SectionBorder.vue";
import TeamMemberManager from "@/Pages/Teams/Partials/TeamMemberManager.vue";
import UpdateTeamNameForm from "@/Pages/Teams/Partials/UpdateTeamNameForm.vue";

import { usePage } from "@inertiajs/vue3";
import { reactive } from "vue";

const page = usePage();
const auth = page.props.auth;
const team = page.props.team;
const availableRoles = auth.user.roles;
const permissions = auth.permissions;
</script>

<template>
    <AppLayout title="Configuración del equipo">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Configuración del equipo
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <UpdateTeamNameForm :team="team" :permissions="permissions" />

                <TeamMemberManager
                    class="mt-10 sm:mt-0"
                    :team="team"
                    :permissions="permissions"
                    :available-roles="availableRoles"
                />

                <template v-if="permissions['delete-teams'] && ! team.personal_team">
                    <SectionBorder />

                    <DeleteTeamForm class="mt-10 sm:mt-0" :team="team" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
