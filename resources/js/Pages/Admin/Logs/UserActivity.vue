<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import ActivityLogList from "@/Components/ActivityLog/ActivityLogList.vue";
</script>

<template>
    <AppLayout title="Logs de Actividades">        
        <template #card-content>
            <ActivityLogList />
        </template>
    </AppLayout>
</template>
