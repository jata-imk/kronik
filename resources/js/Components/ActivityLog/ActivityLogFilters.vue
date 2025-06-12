<template>
  <Panel class="mb-4" toggleable>
    <template #header>
      <div class="flex align-items-center justify-content-between">
        <span class="font-bold text-lg">Filters</span>
        <Button
          class="ml-2"
          label="Clear All"
          icon="pi pi-times"
          size="small"
          text
          @click="clearFilters"
        />
      </div>
    </template>

    <div class="grid grid-cols-12 gap-4">
        <!-- Search -->
        <div class="col-span-12 md:col-span-6 lg:col-span-3">
          <label for="search" class="block text-sm font-medium mb-2">Search</label>
          <InputText
            id="search"
            v-model="localFilters.search"
            placeholder="Search activities..."
            class="w-full"
            @keyup.enter="applyFilters"
          />
        </div>

        <!-- Action Filter -->
        <div class="col-span-12 md:col-span-6 lg:col-span-3">
          <label for="action" class="block text-sm font-medium mb-2">Action</label>
          <Select
            id="action"
            v-model="localFilters.action"
            :options="actionOptions"
            option-label="label"
            option-value="value"
            placeholder="All Actions"
            class="w-full"
            show-clear
          />
        </div>

        <!-- Subject Type Filter -->
        <div class="col-span-12 md:col-span-6 lg:col-span-3">
          <label for="subjectType" class="block text-sm font-medium mb-2">Subject Type</label>
          <Select
            id="subjectType"
            v-model="localFilters.subject_type"
            :options="subjectTypeOptions"
            option-label="label"
            option-value="value"
            placeholder="All Types"
            class="w-full"
            show-clear
          />
        </div>

        <!-- Date Range -->
        <div class="col-span-12 md:col-span-6 lg:col-span-3">
          <label for="dateRange" class="block text-sm font-medium mb-2">Date Range</label>
          <DatePicker
            id="dateRange"
            v-model="dateRange"
            selection-mode="range"
            :manual-input="false"
            date-format="yy-mm-dd"
            placeholder="Select date range"
            class="w-full"
            show-icon
            @date-select="updateDateFilters"
          />
        </div>

        <!-- Per Page -->
        <div class="col-span-12 md:col-span-6 lg:col-span-3">
          <label for="perPage" class="block text-sm font-medium mb-2">Items per page</label>
          <Select
            id="perPage"
            v-model="localFilters.per_page"
            :options="perPageOptions"
            option-label="label"
            option-value="value"
            class="w-full"
          />
        </div>

        <!-- Apply Filters Button -->
        <div class="col-span-12">
          <Button
            label="Apply Filters"
            icon="pi pi-search"
            class="w-full"
            @click="applyFilters"
          />
        </div>
    </div>
  </Panel>
</template>

<script setup>
import { ref, reactive, watch } from "vue";

const props = defineProps();
const emit = defineEmits();

const localFilters = reactive({ ...props.filters });
const dateRange = ref([]);

const actionOptions = [
    { label: "Created", value: "created" },
    { label: "Updated", value: "updated" },
    { label: "Deleted", value: "deleted" },
    { label: "Login", value: "login" },
    { label: "Logout", value: "logout" },
    { label: "Viewed", value: "viewed" },
    { label: "Exported", value: "exported" },
    { label: "Imported", value: "imported" },
];

const subjectTypeOptions = [
    { label: "User", value: "User" },
    { label: "Project", value: "Project" },
    { label: "Document", value: "Document" },
    { label: "Task", value: "Task" },
    { label: "Comment", value: "Comment" },
];

const perPageOptions = [
    { label: "10", value: 10 },
    { label: "20", value: 20 },
    { label: "50", value: 50 },
    { label: "100", value: 100 },
];

const updateDateFilters = () => {
    if (dateRange.value && dateRange.value.length === 2) {
        localFilters.date_from = dateRange.value[0].toISOString().split("T")[0];
        localFilters.date_to = dateRange.value[1].toISOString().split("T")[0];
    } else {
        localFilters.date_from = "";
        localFilters.date_to = "";
    }
};

const applyFilters = () => {
    emit("update:filters", { ...localFilters });
    emit("apply");
};

const clearFilters = () => {
    for (const key of Object.keys(localFilters)) {
        if (key === "page" || key === "per_page") continue;
        localFilters[key] =
            key === "search" || key.includes("date") ? "" : undefined;
    }
    dateRange.value = [];
    emit("clear");
};

// Watch for external filter changes
watch(
    () => props.filters,
    (newFilters) => {
        Object.assign(localFilters, newFilters);
    },
    { deep: true },
);
</script>