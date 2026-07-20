<template>
    <Card class="activity-log-table" :pt="{
        root: '!shadow-none !border !border-gray-200 dark:!border-gray-700',
        body: '!p-0',
    }">
        <template #content>
            <DataTable
            :value="logs"
            :loading="loading"
            striped-rows
            responsive-layout="scroll"
            :paginator="false"
            class="p-datatable-lg"
            :row-hover="true"
            :scrollable="true"
            scroll-height="73vh"
            >
                <!-- ID Column -->
                <Column field="id" header="ID" :sortable="true" class="id-column">
                    <template #body="{ data }">
                    <span class="font-mono text-sm text-600">{{ data.id }}</span>
                    </template>
                </Column>

                <!-- User Column -->
                <Column header="User" class="user-column" :sortable="false">
                    <template #body="{ data }">
                    <div class="flex items-center gap-4">
                        <Avatar
                            :label="data.user.name.charAt(0)"
                            class="user-avatar"
                            :class="getUserAvatarClass(data.user.name)"
                            shape="circle"
                            size="normal"
                        />
                        <div class="flex flex-col items-start">
                            <span class="font-semibold text-900">{{ data.user.name }}</span>
                            <Tag 
                                :value="getUserRole(data.user)" 
                                severity="secondary"
                                class="mt-2 role-tag"
                            />
                        </div>
                    </div>
                    </template>
                </Column>

                <!-- IP Address Column -->
                <Column field="ip_address" header="IP Address" :sortable="true" class="ip-column">
                    <template #body="{ data }">
                    <div v-if="data.ip_address" class="flex align-items-center gap-2">
                        <i class="pi pi-globe text-600"></i>
                        <span class="font-mono text-sm">{{ data.ip_address }}</span>
                    </div>
                    <span v-else class="text-400">—</span>
                    </template>
                </Column>

                <!-- Date and Time Column -->
                <Column header="Date & Time" :sortable="true" :pt="{ bodycell: { class: '!min-w-[13rem]' } }">
                    <template #body="{ data }">
                        <span class="font-mono text-sm text-600">{{ formatDate(data.created_at) }} {{ formatTime(data.created_at) }}</span>
                    </template>
                </Column>

                <!-- Action Column -->
                <Column field="action" header="Action" :sortable="true" class="action-column">
                    <template #body="{ data }">
                    <Tag 
                        :value="data.action" 
                        :severity="getActionSeverity(data.action)"
                        :icon="`pi ${getActionIcon(data.action)}`"
                        class="action-tag"
                    />
                    </template>
                </Column>

                <!-- Description Column -->
                <Column field="description" header="Description" class="description-column">
                    <template #body="{ data }">
                    <div class="description-content">
                        <p class="text-700 line-height-3 m-0">{{ data.description }}</p>
                        <div v-if="data.subject_type" class="flex align-items-center gap-1 mt-1">
                        <i class="pi pi-tag text-400 text-xs"></i>
                        <span class="text-400 text-xs">{{ data.subject_type }}</span>
                        </div>
                    </div>
                    </template>
                </Column>

                <!-- Actions Column -->
                <Column header="" class="actions-column" :frozen="true" align-frozen="right" :pt="{ bodycell: '!p-2' }">
                    <template #body="{ data }">
                        <div class="flex justify-center gap-1">
                            <Button
                                icon="pi pi-eye"
                                size="small"
                                text
                                rounded
                                severity="secondary"
                                @click="viewDetails(data)"
                                v-tooltip.top="'View Details'"
                            />
                            <Button
                                v-if="data.properties && Object.keys(data.properties).length"
                                icon="pi pi-info-circle"
                                size="small"
                                text
                                rounded
                                severity="info"
                                @click="showProperties(data)"
                                v-tooltip.top="'View Properties'"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </template>
    </Card>

  <!-- Details Dialog -->
  <Dialog
    v-model:visible="detailsVisible"
    :header="`Activity Log #${selectedLog?.id}`"
    :modal="true"
    :style="{ width: '50rem' }"
    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
  >
    <div v-if="selectedLog" class="activity-details">
      <div class="grid">
        <div class="col-12 md:col-6">
          <div class="field">
            <label class="font-semibold text-900">User</label>
            <div class="flex align-items-center gap-2 mt-1">
              <Avatar
                :label="selectedLog.user.name.charAt(0)"
                :class="getUserAvatarClass(selectedLog.user.name)"
                shape="circle"
                size="small"
              />
              <span>{{ selectedLog.user.name }}</span>
            </div>
          </div>
        </div>
        <div class="col-12 md:col-6">
          <div class="field">
            <label class="font-semibold text-900">Action</label>
            <div class="mt-1">
              <Tag 
                :value="selectedLog.action" 
                :severity="getActionSeverity(selectedLog.action)"
                :icon="`pi ${getActionIcon(selectedLog.action)}`"
              />
            </div>
          </div>
        </div>
        <div class="col-12 md:col-6">
          <div class="field">
            <label class="font-semibold text-900">IP Address</label>
            <p class="mt-1 font-mono">{{ selectedLog.ip_address || '—' }}</p>
          </div>
        </div>
        <div class="col-12 md:col-6">
          <div class="field">
            <label class="font-semibold text-900">Date & Time</label>
            <p class="mt-1">{{ formatDateTime(selectedLog.created_at) }}</p>
          </div>
        </div>
        <div class="col-12">
          <div class="field">
            <label class="font-semibold text-900">Description</label>
            <p class="mt-1 text-700">{{ selectedLog.description }}</p>
          </div>
        </div>
        <div v-if="selectedLog.subject_type" class="col-12 md:col-6">
          <div class="field">
            <label class="font-semibold text-900">Subject Type</label>
            <p class="mt-1">{{ selectedLog.subject_type }}</p>
          </div>
        </div>
        <div v-if="selectedLog.subject_id" class="col-12 md:col-6">
          <div class="field">
            <label class="font-semibold text-900">Subject ID</label>
            <p class="mt-1 font-mono">#{{ selectedLog.subject_id }}</p>
          </div>
        </div>
      </div>
    </div>
  </Dialog>

  <!-- Properties Dialog -->
  <Dialog
    v-model:visible="propertiesVisible"
    header="Activity Properties"
    :modal="true"
    :style="{ width: '40rem' }"
    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
  >
    <div v-if="selectedLog?.properties" class="properties-content">
      <pre class="bg-gray-50 p-3 border-round text-sm overflow-auto">{{ JSON.stringify(selectedLog.properties, null, 2) }}</pre>
    </div>
  </Dialog>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
    logs: Array,
    loading: Boolean,
});

const logs = props.logs || [];
const loading = props.loading || false;

const detailsVisible = ref(false);
const propertiesVisible = ref(false);
const selectedLog = ref(null);

const getUserAvatarClass = (name) => {
    const colors = [
        "bg-blue-500",
        "bg-green-500",
        "bg-purple-500",
        "bg-orange-500",
        "bg-pink-500",
        "bg-cyan-500",
        "bg-indigo-500",
        "bg-red-500",
    ];
    const index = name.charCodeAt(0) % colors.length;
    return colors[index];
};

const getUserRole = (user) => {
    // In a real app, this would come from user data
    const roles = ["Admin", "User", "Manager", "Editor"];
    return roles[user.id % roles.length];
};

const getActionSeverity = (action) => {
    const severityMap = {
        created: "success",
        updated: "info",
        deleted: "danger",
        login: "secondary",
        logout: "warning",
        viewed: "info",
        exported: "info",
        imported: "success",
    };
    return severityMap[action] || "info";
};

const getActionIcon = (action) => {
    const iconMap = {
        created: "pi-plus",
        updated: "pi-pencil",
        deleted: "pi-trash",
        login: "pi-sign-in",
        logout: "pi-sign-out",
        viewed: "pi-eye",
        exported: "pi-download",
        imported: "pi-upload",
    };
    return iconMap[action] || "pi-circle";
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        month: "short",
        day: "2-digit",
        year: "numeric",
    });
};

const formatTime = (dateString) => {
    const time = new Date(dateString);
    return time.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });
};

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString("en-US", {
        month: "short",
        day: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const viewDetails = (log) => {
    selectedLog.value = log;
    detailsVisible.value = true;
};

const showProperties = (log) => {
    selectedLog.value = log;
    propertiesVisible.value = true;
};
</script>

<style scoped>
.activity-log-table :deep(.p-datatable) {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.activity-log-table :deep(.p-datatable-header) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 1rem;
}

.activity-log-table :deep(.p-datatable-thead > tr > th) {
  background: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
  font-weight: 600;
  color: #495057;
  padding: 1rem 0.75rem;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.activity-log-table :deep(.p-datatable-tbody > tr) {
  transition: all 0.2s ease;
}

.activity-log-table :deep(.p-datatable-tbody > tr:hover) {
  background-color: #f8f9fa !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.activity-log-table :deep(.p-datatable-tbody > tr > td) {
  padding: 1rem 0.75rem;
  border-bottom: 1px solid #f1f3f4;
  vertical-align: middle;
}

.user-avatar {
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
  aspect-ratio: 1;
}

.role-tag {
  font-size: 0.625rem;
  padding: 0.25rem 0.5rem;
  font-weight: 500;
}

.action-tag {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
}

.description-content {
  max-width: 300px;
}

.id-column {
  width: 80px;
  min-width: 80px;
}

.user-column {
  width: 200px;
  min-width: 200px;
}

.ip-column {
  width: 140px;
  min-width: 140px;
}

.date-time-column {
  width: fit-content;
  min-width: fit-content;
}

.action-column {
  width: 120px;
  min-width: 120px;
}

.actions-column {
  width: 100px;
  min-width: 100px;
}

.activity-details .field {
  margin-bottom: 1.5rem;
}

.activity-details .field label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.properties-content pre {
  max-height: 400px;
  font-family: 'Fira Code', 'Monaco', 'Consolas', monospace;
  font-size: 0.8rem;
  line-height: 1.4;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .activity-log-table :deep(.p-datatable-tbody > tr > td) {
    padding: 0.75rem 0.5rem;
  }
  
  .description-content {
    max-width: 200px;
  }
}
</style>