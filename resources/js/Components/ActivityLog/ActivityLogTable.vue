<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    logs: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const selectedLog = ref(null);
const detailsVisible = ref(false);
const propertiesVisible = ref(false);
const logs = computed(() => props.logs);

const eventLabels = {
    login: "Inicio de sesión",
    "empresa.updated": "Empresa actualizada",
    "sucursal.created": "Sucursal creada",
    "sucursal.updated": "Sucursal actualizada",
    "sucursal.deactivated": "Sucursal desactivada",
    sin_clasificar: "Sin clasificar",
};

const eventSeverity = {
    login: "secondary",
    "empresa.updated": "info",
    "sucursal.created": "success",
    "sucursal.updated": "info",
    "sucursal.deactivated": "danger",
};

const eventIcon = {
    login: "pi-sign-in",
    "empresa.updated": "pi-building",
    "sucursal.created": "pi-plus",
    "sucursal.updated": "pi-pencil",
    "sucursal.deactivated": "pi-ban",
};

const eventLabel = (event) => eventLabels[event] ?? event;
const formatDate = (value) => new Date(value).toLocaleString("es-MX", {
    dateStyle: "medium",
    timeStyle: "short",
});
const openDetails = (log) => {
    selectedLog.value = log;
    detailsVisible.value = true;
};
const openProperties = (log) => {
    selectedLog.value = log;
    propertiesVisible.value = true;
};
</script>

<template>
    <Card>
        <template #content>
            <DataTable :value="logs" :loading="loading" striped-rows scrollable responsive-layout="scroll">
                <Column field="id" header="#" />
                <Column header="Usuario">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <Avatar :label="data.causer.name.charAt(0)" shape="circle" />
                            <div>
                                <div class="font-medium">{{ data.causer.name }}</div>
                                <small v-if="data.causer.email" class="text-surface-500">{{ data.causer.email }}</small>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Fecha">
                    <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                </Column>
                <Column header="Evento">
                    <template #body="{ data }">
                        <Tag :value="eventLabel(data.event)" :severity="eventSeverity[data.event] ?? 'secondary'" :icon="`pi ${eventIcon[data.event] ?? 'pi-circle'}`" />
                    </template>
                </Column>
                <Column field="description" header="Descripción">
                    <template #body="{ data }">
                        <div>{{ data.description }}</div>
                        <small v-if="data.subject.id" class="text-surface-500">{{ data.subject.type }} #{{ data.subject.id }}</small>
                    </template>
                </Column>
                <Column header="IP">
                    <template #body="{ data }">{{ data.ip ?? "—" }}</template>
                </Column>
                <Column header="">
                    <template #body="{ data }">
                        <Button icon="pi pi-eye" text rounded v-tooltip.top="'Ver detalle'" @click="openDetails(data)" />
                        <Button v-if="Object.keys(data.properties).length" icon="pi pi-code" text rounded v-tooltip.top="'Ver propiedades'" @click="openProperties(data)" />
                    </template>
                </Column>
            </DataTable>
        </template>
    </Card>

    <Dialog v-model:visible="detailsVisible" header="Detalle de actividad" modal :style="{ width: '42rem' }">
        <dl v-if="selectedLog" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><dt class="font-medium">Usuario</dt><dd>{{ selectedLog.causer.name }}</dd></div>
            <div><dt class="font-medium">Evento</dt><dd>{{ eventLabel(selectedLog.event) }}</dd></div>
            <div><dt class="font-medium">Fecha</dt><dd>{{ formatDate(selectedLog.created_at) }}</dd></div>
            <div><dt class="font-medium">IP</dt><dd>{{ selectedLog.ip ?? "—" }}</dd></div>
            <div class="md:col-span-2"><dt class="font-medium">Descripción</dt><dd>{{ selectedLog.description }}</dd></div>
            <div><dt class="font-medium">Sujeto</dt><dd>{{ selectedLog.subject.type }}</dd></div>
            <div><dt class="font-medium">ID sujeto</dt><dd>{{ selectedLog.subject.id ?? "—" }}</dd></div>
        </dl>
    </Dialog>

    <Dialog v-model:visible="propertiesVisible" header="Propiedades" modal :style="{ width: '42rem' }">
        <pre v-if="selectedLog" class="bg-surface-100 dark:bg-surface-800 p-4 rounded overflow-auto">{{ JSON.stringify(selectedLog.properties, null, 2) }}</pre>
    </Dialog>
</template>
