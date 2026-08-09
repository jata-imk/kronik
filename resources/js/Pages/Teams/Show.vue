<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import UpdateTeamNameForm from "@/Pages/Teams/Partials/UpdateTeamNameForm.vue";

import { usePage } from "@inertiajs/vue3";
import { reactive } from "vue";

const page = usePage();
const auth = page.props.auth;
const team = page.props.team;
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

                <div class="mt-8 rounded-lg border border-surface-200 p-5 dark:border-surface-700">
                    <h3 class="mb-2 font-semibold">Miembros y roles</h3>
                    <p class="text-sm text-surface-600 dark:text-surface-300">
                        Las membresías, roles funcionales y sucursales se administran desde el módulo de usuarios.
                    </p>
                    <Button
                        v-if="auth.permissions['update-users'] || auth.is_super_admin"
                        class="mt-4"
                        label="Administrar usuarios"
                        icon="pi pi-users"
                        as="a"
                        :href="route('admin.users.index')"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
