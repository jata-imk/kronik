<script setup>
import { reactive, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

import ActivityLogFilters from "./ActivityLogFilters.vue";
import ActivityLogTable from "./ActivityLogTable.vue";

const props = defineProps({
    activityLogs: { type: Object, required: true },
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

const loading = ref(false);
const filters = reactive({ ...props.filters });

watch(
    () => props.filters,
    (nextFilters) => Object.assign(filters, nextFilters),
    { deep: true },
);

const visit = (overrides = {}) => {
    Object.assign(filters, overrides);
    loading.value = true;

    router.get(route("admin.users.activity"), filters, {
        preserveScroll: true,
        preserveState: true,
        only: ["activityLogs", "filters", "filterOptions"],
        onFinish: () => {
            loading.value = false;
        },
    });
};

const applyFilters = (nextFilters) => {
    Object.assign(filters, nextFilters);
    visit({ page: 1 });
};

const clearFilters = () => {
    Object.assign(filters, {
        search: "",
        user_id: null,
        event: null,
        subject_type: null,
        date_from: "",
        date_to: "",
        page: 1,
    });
    visit();
};

const exportLogs = () => {
    window.location.assign(route("admin.users.activity.export", filters));
};

const onPageChange = ({ first, rows }) => {
    visit({
        page: Math.floor(first / rows) + 1,
        per_page: rows,
    });
};
</script>

<template>
    <div class="p-4 md:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-surface-500">Mostrando {{ activityLogs.from ?? 0 }}–{{ activityLogs.to ?? 0 }} de {{ activityLogs.total }} eventos</p>
            <div class="flex gap-2">
                <Button label="Actualizar" icon="pi pi-refresh" outlined :loading="loading" @click="visit()" />
                <Button label="Exportar CSV" icon="pi pi-download" @click="exportLogs" />
            </div>
        </div>

        <ActivityLogFilters
            :filters="filters"
            :filter-options="filterOptions"
            @apply="applyFilters"
            @clear="clearFilters"
        />

        <Card v-if="!loading && !activityLogs.data.length" class="text-center">
            <template #content>
                <i class="pi pi-inbox text-5xl text-surface-400" />
                <h3 class="text-xl mt-4">Sin eventos</h3>
                <p class="text-surface-500">No hay actividades que coincidan con los filtros actuales.</p>
                <Button label="Limpiar filtros" outlined @click="clearFilters" />
            </template>
        </Card>

        <ActivityLogTable v-else :logs="activityLogs.data" :loading="loading" />

        <Paginator
            v-if="activityLogs.last_page > 1"
            class="mt-5"
            :first="(activityLogs.current_page - 1) * activityLogs.per_page"
            :rows="activityLogs.per_page"
            :total-records="activityLogs.total"
            :rows-per-page-options="[10, 20, 50, 100]"
            @page="onPageChange"
        />
    </div>
</template>
