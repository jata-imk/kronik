<script setup>
import { onMounted } from "vue";
import ActivityLogTable from "./ActivityLogTable.vue";
import ActivityLogFilters from "./ActivityLogFilters.vue";
import { useActivityLogs } from "@/Composables/useActivityLogs";

const props = defineProps({
    paginatedActivityLogs: Object,
});

const {
    logs,
    loading,
    pagination,
    filters,
    fetchLogs,
    applyFilters,
    clearFilters,
    exportLogs,
} = useActivityLogs();

const updateFilters = (newFilters) => {
    Object.assign(filters, newFilters);
};

const onPageChange = (event) => {
    filters.page = event.page + 1;
    filters.per_page = event.rows;
    fetchLogs();
};

onMounted(() => {
    fetchLogs();
});
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-start">
        <Button icon="pi pi-arrow-left"  as="a" :href="route('admin.dashboard')"></Button>

        <div class="w-full flex align-items-center justify-between mb-4 ml-4">
          <div>
            <h2 class="text-2xl font-bold text-900 m-0 mb-2">Activity Logs</h2>
            <p class="text-600 mt-1 mb-0">
              Showing {{ pagination.from }}-{{ pagination.to }} of {{ pagination.total }} activities
            </p>
          </div>
          <div class="flex items-center gap-2">
            <Button
              label="Refresh"
              icon="pi pi-refresh"
              :loading="loading"
              outlined
              size="large"
              @click="fetchLogs"
            />
            <Button
              label="Export"
              icon="pi pi-download"
              size="large"
              @click="exportLogs"
            />
          </div>
        </div>
    </div>

    <!-- Filters -->
    <ActivityLogFilters
      :filters="filters"
      @update:filters="updateFilters"
      @apply="applyFilters"
      @clear="clearFilters"
    />

    <!-- Empty State -->
    <Card v-if="!loading && !logs.length" class="text-center p-6" :pt="{
      root: '!shadow-none !border !border-gray-200 dark:!border-gray-700',
    }">
      <template #content>
        <div class="empty-state">
          <i class="pi pi-inbox text-6xl text-400 mb-4"></i>
          <h3 class="text-2xl text-900 mb-3">No Activity Logs Found</h3>
          <p class="text-600 mb-4 text-lg">
            No activities match your current filters. Try adjusting your search criteria.
          </p>
          <Button
            label="Clear Filters"
            icon="pi pi-times"
            outlined
            size="large"
            @click="clearFilters"
          />
        </div>
      </template>
    </Card>

    <!-- Activity Log Table -->
    <ActivityLogTable
      v-else
      :logs="logs"
      :loading="loading"
    />

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="flex justify-center mt-6">
      <Paginator
        :rows="pagination.per_page"
        :total-records="pagination.total"
        :current-page="pagination.current_page - 1"
        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rows-per-page-options="[10, 20, 50, 100]"
        current-page-report-template="Showing {first} to {last} of {totalRecords} entries"
        class="custom-paginator"
        @page="onPageChange"
      />
    </div>
  </div>
</template>

<style scoped>

.empty-state {
  padding: 3rem 2rem;
}

.custom-paginator :deep(.p-paginator) {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.custom-paginator :deep(.p-paginator .p-paginator-pages .p-paginator-page) {
  min-width: 2.5rem;
  height: 2.5rem;
  border-radius: 8px;
  margin: 0 0.25rem;
  transition: all 0.2s ease;
}

.custom-paginator :deep(.p-paginator .p-paginator-pages .p-paginator-page:hover) {
  background: #f8f9fa;
  transform: translateY(-1px);
}

.custom-paginator :deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
  .activity-log-list {
    padding: 1rem;
  }
  
  .empty-state {
    padding: 2rem 1rem;
  }
}
</style>