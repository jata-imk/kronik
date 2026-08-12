<script setup>
import { computed, ref } from "vue";
import TruncatedText from "@/Components/DataTable/TruncatedText.vue";

const props = defineProps({
    logs: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const selectedLog = ref(null);
const detailsVisible = ref(false);
const propertiesVisible = ref(false);
const logs = computed(() => props.logs);

const formatDate = (value) =>
    new Date(value).toLocaleString("es-MX", {
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
                <Column field="id" header="#"><template #body="{ data }"><TruncatedText :value="data.id" max-width="5rem" /></template></Column>
                <Column header="Usuario">
                    <template #body="{ data }">
                        <div class="max-w-60">
                            <TruncatedText :value="data.causer.name" max-width="15rem" />
                            <TruncatedText v-if="data.causer.email" class="text-sm text-surface-500" :value="data.causer.email" max-width="15rem" />
                        </div>
                    </template>
                </Column>
                <Column header="Fecha">
                    <template #body="{ data }">
                        <div class="max-w-44">
                            <TruncatedText :value="formatDate(data.created_at)" max-width="11rem" />
                            <div class="mt-0.5 flex items-center text-xs text-surface-500">
                                <TruncatedText :value="data.ip ?? '—'" max-width="9rem" />
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Evento">
                    <template #body="{ data }">
                        <Tag
                            :value="data.event_label"
                            :severity="data.event_severity"
                            :icon="`pi ${data.event_icon}`"
                            class="max-w-40 whitespace-nowrap"
                            :pt="{ label: { class: 'block truncate' } }"
                        />
                    </template>
                </Column>
                <Column field="description" header="Descripción">
                    <template #body="{ data }">
                        <TruncatedText :value="data.subject.id ? `${data.description} · ${data.subject.type} #${data.subject.id}` : data.description" max-width="19rem" />
                    </template>
                </Column>
                <Column header="Acciones" :frozen="true" align-frozen="right" class="w-28" :pt="{ columnHeaderContent: { class: 'whitespace-nowrap' } }">
                    <template #body="{ data }">
                        <div class="flex flex-nowrap items-center gap-1 whitespace-nowrap">
                            <Button :aria-label="`Ver detalle de actividad ${data.id}`" icon="pi pi-eye" text rounded @click="openDetails(data)" />
                            <Button v-if="Object.keys(data.properties).length" :aria-label="`Ver propiedades de actividad ${data.id}`" icon="pi pi-code" text rounded @click="openProperties(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </template>
    </Card>

    <Dialog v-model:visible="detailsVisible" header="Detalle de actividad" modal :style="{ width: '42rem' }">
        <dl v-if="selectedLog" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><dt class="font-medium">Usuario</dt><dd>{{ selectedLog.causer.name }}</dd></div>
            <div><dt class="font-medium">Evento</dt><dd>{{ selectedLog.event_label }}</dd></div>
            <div><dt class="font-medium">Fecha</dt><dd>{{ formatDate(selectedLog.created_at) }}</dd></div>
            <div><dt class="font-medium">IP</dt><dd>{{ selectedLog.ip ?? "—" }}</dd></div>
            <div><dt class="font-medium">Equipo</dt><dd>{{ selectedLog.team.name }}</dd></div>
            <div><dt class="font-medium">Sucursal</dt><dd>{{ selectedLog.sucursal.clave ? `${selectedLog.sucursal.clave} · ${selectedLog.sucursal.name}` : selectedLog.sucursal.name }}</dd></div>
            <div class="md:col-span-2"><dt class="font-medium">Descripción</dt><dd>{{ selectedLog.description }}</dd></div>
            <div><dt class="font-medium">Sujeto</dt><dd>{{ selectedLog.subject.type }}</dd></div>
            <div><dt class="font-medium">ID sujeto</dt><dd>{{ selectedLog.subject.id ?? "—" }}</dd></div>
        </dl>
    </Dialog>

    <Dialog v-model:visible="propertiesVisible" header="Propiedades" modal :style="{ width: '42rem' }">
        <pre v-if="selectedLog" class="bg-surface-100 dark:bg-surface-800 p-4 rounded overflow-auto">{{ JSON.stringify(selectedLog.properties, null, 2) }}</pre>
    </Dialog>
</template>
