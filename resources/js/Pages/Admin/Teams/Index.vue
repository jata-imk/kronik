<script setup>
import { ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import { useConfirm } from "primevue/useconfirm";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";

const props = defineProps({ teams: { type: Array, default: () => [] } });
const page = usePage();
const can = (permission) => page.props.auth.is_super_admin || page.props.auth.permissions?.[permission] === true;
const toast = useToast();
const confirm = useConfirm();
const visible = ref(false);
const form = useForm({ name: "" });
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    "owner.name": { value: null, matchMode: FilterMatchMode.CONTAINS },
    status_label: { value: "Activo", matchMode: FilterMatchMode.EQUALS },
});

const openCreate = () => { form.defaults({ name: "" }); form.reset(); form.clearErrors(); visible.value = true; };
const submit = () => form.post(route("admin.teams.store"), {
    preserveScroll: true,
    onSuccess: () => { visible.value = false; toast.add({ severity: "success", summary: "Equipo creado", life: 3000 }); },
    onError: (errors) => toast.add({ severity: "error", summary: "No se pudo crear", detail: Object.values(errors)[0], life: 5000 }),
});
const deactivate = (team) => confirm.require({
    header: "Desactivar equipo",
    message: `¿Desactivar ${team.name}? Sus datos y membresías se conservarán.`,
    icon: "pi pi-exclamation-triangle",
    rejectLabel: "Cancelar",
    acceptLabel: "Desactivar",
    acceptClass: "p-button-danger",
    accept: () => router.delete(route("admin.teams.destroy", team.id), {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: "success", summary: "Equipo desactivado", life: 3000 }),
        onError: (errors) => toast.add({ severity: "error", summary: "No se puede desactivar", detail: errors.team ?? Object.values(errors)[0], life: 6000 }),
    }),
});
const reactivate = (team) => router.put(route("admin.teams.update", team.id), { name: team.name, activo: true }, {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: "success", summary: "Equipo reactivado", life: 3000 }),
});
</script>

<template>
    <AppLayout title="Equipos">
        <template #card-header><div class="flex flex-wrap items-center justify-between gap-3 p-4"><div class="flex items-center"><Button icon="pi pi-arrow-left" as="a" :href="route('admin.dashboard')" /><div class="ml-4"><h2 class="text-2xl font-bold">Equipos y departamentos</h2><p class="text-sm text-surface-500">Contextos organizativos y de permisos.</p></div></div><Button v-if="can('create-teams')" label="Crear equipo" icon="pi pi-plus" @click="openCreate" /></div></template>
        <template #card-content>
            <ConfirmDialog />
            <DataTable v-model:filters="filters" :value="props.teams" :global-filter-fields="['name', 'owner.name', 'owner.email', 'status_label', 'type_label']" paginator :rows="10" striped-rows responsive-layout="scroll">
                <template #header><div class="flex justify-end"><IconField><InputIcon class="pi pi-search" /><InputText v-model="filters.global.value" placeholder="Buscar equipos" /></IconField></div></template>
                <Column field="name" header="Equipo" sortable>
                    <template #body="{ data }"><div class="flex items-center gap-3"><Avatar icon="pi pi-sitemap" shape="circle" /><div><div class="font-semibold">{{ data.name }}</div><div class="mt-1 flex gap-1"><Tag :value="data.status_label" :severity="data.activo ? 'success' : 'secondary'" /><Tag :value="data.type_label" severity="info" /></div></div></div></template>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Nombre" @input="filterCallback()" /></template>
                </Column>
                <Column field="owner.name" header="Responsable" sortable><template #body="{ data }"><div><div>{{ data.owner.name }}</div><small class="text-surface-500">{{ data.owner.email }}</small></div></template><template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" placeholder="Responsable" @input="filterCallback()" /></template></Column>
                <Column header="Resumen"><template #body="{ data }"><div class="flex flex-wrap gap-2"><Chip :label="`${data.members_count} miembros`" icon="pi pi-users" /><Chip :label="`${data.roles_count} roles`" icon="pi pi-key" /><Chip :label="`${data.current_users_count} actuales`" icon="pi pi-check-circle" /></div></template></Column>
                <Column field="status_label" header="Estado"><template #filter="{ filterModel, filterCallback }"><Select v-model="filterModel.value" :options="['Activo', 'Inactivo']" placeholder="Todos" show-clear @change="filterCallback()" /></template></Column>
                <Column header="Acciones" frozen align-frozen="right"><template #body="{ data }"><Button :aria-label="`Abrir configuración de ${data.name}`" icon="pi pi-arrow-right" text v-tooltip.top="'Abrir configuración'" as="a" :href="route('teams.show', data.id)" /><Button v-if="can('delete-teams') && data.activo" :aria-label="`Desactivar ${data.name}`" icon="pi pi-ban" severity="danger" text v-tooltip.top="'Desactivar'" @click="deactivate(data)" /><Button v-if="can('update-teams') && !data.activo" :aria-label="`Reactivar ${data.name}`" icon="pi pi-refresh" severity="success" text v-tooltip.top="'Reactivar'" @click="reactivate(data)" /></template></Column>
            </DataTable>
            <Dialog v-model:visible="visible" header="Crear equipo institucional" modal :style="{ width: '460px' }"><form class="flex flex-col gap-4" @submit.prevent="submit"><Message severity="info" :closable="false">Los equipos creados aquí son institucionales. Después podrás administrar sus miembros y roles desde su configuración.</Message><div><label class="mb-1 block text-sm font-medium">Nombre *</label><InputText v-model="form.name" fluid :invalid="!!form.errors.name" /><small class="text-red-500">{{ form.errors.name }}</small></div><div class="flex justify-end gap-2"><Button label="Cancelar" severity="secondary" @click.prevent="visible = false" /><Button label="Crear" type="submit" :loading="form.processing" /></div></form></Dialog>
        </template>
    </AppLayout>
</template>
