<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import ActivityLogList from "@/Components/ActivityLog/ActivityLogList.vue";
defineProps({
    activityLogs: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});
</script>

<template>
    <AppLayout title="Actividad">
        <template #card-header><PageHeader eyebrow="Administración · Control" title="Actividad" description="Eventos recientes y filtros de auditoría."><template #actions><Button icon="pi pi-arrow-left" label="Volver" severity="secondary" as="a" :href="route('admin.dashboard')" /></template></PageHeader></template>
        <template #card-content>
            <ActivityLogList
                :activity-logs="activityLogs"
                :filters="filters"
                :filter-options="filterOptions"
            />
        </template>
    </AppLayout>
</template>
