<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import CreateTeamForm from "@/Pages/Teams/Partials/CreateTeamForm.vue";
</script>

<template>
    <AppLayout title="Crear Equipo">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Crear Equipo
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <CreateTeamForm />
            </div>
        </div>
    </AppLayout>
</template>
