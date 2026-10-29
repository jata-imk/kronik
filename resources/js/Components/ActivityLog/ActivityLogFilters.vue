<script setup>
import { reactive, ref, watch } from "vue";

const props = defineProps({
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

const emit = defineEmits(["apply", "clear"]);
const localFilters = reactive({ ...props.filters });
const datesFromFilters = () => props.filters.date_from
    ? [new Date(`${props.filters.date_from}T00:00:00`), props.filters.date_to ? new Date(`${props.filters.date_to}T00:00:00`) : null]
    : [];
const dateRange = ref(datesFromFilters());

watch(
    () => props.filters,
    (nextFilters) => {
        Object.assign(localFilters, nextFilters);
        dateRange.value = datesFromFilters();
    },
    { deep: true },
);

const apply = () => {
    emit("apply", { ...localFilters });
};

const updateDates = () => {
    const localDate = (date) => date
        ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`
        : "";
    localFilters.date_from = localDate(dateRange.value?.[0]);
    localFilters.date_to = localDate(dateRange.value?.[1]);
};
const changeContext = (teamChanged = false) => {
    if (teamChanged) localFilters.sucursal_id = null;
    Object.assign(localFilters, { user_id: null, event: null, subject_type: null });
    apply();
};
</script>

<template>
    <Panel header="Filtros" toggleable class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <label for="activity-team" class="block text-sm font-medium mb-2">Equipo</label>
                <Select id="activity-team" v-model="localFilters.team_id" :options="filterOptions.teams" option-label="name" option-value="id" class="w-full" filter :show-clear="filterOptions.allowAllTeams" :placeholder="filterOptions.allowAllTeams ? 'Todos los equipos' : 'Seleccionar equipo'" @change="changeContext(true)" />
            </div>
            <div>
                <label for="activity-branch" class="block text-sm font-medium mb-2">Sucursal</label>
                <Select id="activity-branch" v-model="localFilters.sucursal_id" :options="filterOptions.sucursales" option-label="name" option-value="id" class="w-full" filter show-clear placeholder="Todas las sucursales" @change="changeContext()" />
            </div>
            <div>
                <label for="activity-search" class="block text-sm font-medium mb-2">Buscar</label>
                <InputText id="activity-search" v-model="localFilters.search" class="w-full" placeholder="Evento, descripción o usuario" @keyup.enter="apply" />
            </div>
            <div>
                <label for="activity-user" class="block text-sm font-medium mb-2">Usuario</label>
                <Select id="activity-user" v-model="localFilters.user_id" :options="filterOptions.users" option-label="name" option-value="id" class="w-full" filter show-clear placeholder="Todos" />
            </div>
            <div>
                <label for="activity-event" class="block text-sm font-medium mb-2">Evento</label>
                <Select id="activity-event" v-model="localFilters.event" :options="filterOptions.events" option-label="label" option-value="value" class="w-full" show-clear placeholder="Todos" />
            </div>
            <div>
                <label for="activity-subject" class="block text-sm font-medium mb-2">Sujeto</label>
                <Select id="activity-subject" v-model="localFilters.subject_type" :options="filterOptions.subjectTypes" option-label="label" option-value="value" class="w-full" show-clear placeholder="Todos" />
            </div>
            <div>
                <label for="activity-dates" class="block text-sm font-medium mb-2">Periodo</label>
                <DatePicker id="activity-dates" v-model="dateRange" dateFormat="dd-mm-yy" selection-mode="range" show-icon class="w-full" @date-select="updateDates" />
            </div>
            <div>
                <label for="activity-per-page" class="block text-sm font-medium mb-2">Por página</label>
                <Select id="activity-per-page" v-model="localFilters.per_page" :options="[10, 20, 50, 100]" class="w-full" />
            </div>
            <div>
                <span class="mb-2 block text-sm font-medium">Acciones</span>
                <div class="grid grid-cols-2 gap-2">
                    <Button class="w-full" label="Aplicar" icon="pi pi-search" @click="apply" />
                    <Button class="w-full" label="Limpiar" severity="secondary" outlined @click="emit('clear')" />
                </div>
            </div>
        </div>
    </Panel>
</template>
