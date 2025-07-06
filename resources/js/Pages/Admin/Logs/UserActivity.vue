<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import ActivityLogList from "@/Components/ActivityLog/ActivityLogList.vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const paginatedActivityLogs = page.props.paginatedActivityLogs;
</script>

<template>
    <AppLayout title="Logs de Actividades">        
        <template #card-content>
            <ActivityLogList :paginatedActivityLogs="paginatedActivityLogs" />
        </template>
    </AppLayout>
</template>
