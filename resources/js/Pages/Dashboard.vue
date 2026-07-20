<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import Welcome from "@/Components/Welcome.vue";
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Tablero de Inicio
            </h2>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <Welcome />
            </div>
        </div>
    </AppLayout>
</template>
