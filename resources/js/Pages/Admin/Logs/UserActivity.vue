<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import ActivityLogList from "@/Components/ActivityLog/ActivityLogList.vue";
defineProps({
    activityLogs: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});
</script>

<template>
    <AppLayout title="Logs de Actividades">        
        <template #card-content>
            <ActivityLogList
                :activity-logs="activityLogs"
                :filters="filters"
                :filter-options="filterOptions"
            />
        </template>
    </AppLayout>
</template>
