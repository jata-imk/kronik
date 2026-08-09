<script setup>
import { reactive, ref, watch } from "vue";

const props = defineProps({
    filters: { type: Object, required: true },
    filterOptions: { type: Object, required: true },
});

const emit = defineEmits(["apply", "clear"]);
const localFilters = reactive({ ...props.filters });
const dateRange = ref(
    props.filters.date_from && props.filters.date_to
        ? [new Date(`${props.filters.date_from}T00:00:00`), new Date(`${props.filters.date_to}T00:00:00`)]
        : [],
);

watch(
    () => props.filters,
    (nextFilters) => Object.assign(localFilters, nextFilters),
    { deep: true },
);

const apply = () => {
    emit("apply", { ...localFilters });
};

const updateDates = () => {
    localFilters.date_from = dateRange.value?.[0]?.toISOString().slice(0, 10) ?? "";
    localFilters.date_to = dateRange.value?.[1]?.toISOString().slice(0, 10) ?? "";
};
</script>

<template>
    <Panel header="Filtros" toggleable class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
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
            <div class="flex items-end gap-2">
                <Button label="Aplicar" icon="pi pi-search" @click="apply" />
                <Button label="Limpiar" severity="secondary" outlined @click="emit('clear')" />
            </div>
        </div>
    </Panel>
</template>
