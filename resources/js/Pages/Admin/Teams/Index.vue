<script setup>
import { computed, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";
import { useConfirm } from "primevue/useconfirm";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import TruncatedText from "@/Components/DataTable/TruncatedText.vue";

const props = defineProps({ teams: { type: Array, default: () => [] } });
const page = usePage();
const can = (permission) =>
    page.props.auth.is_super_admin ||
    page.props.auth.permissions?.[permission] === true;
const toast = useToast();
const confirm = useConfirm();
const visible = ref(false);
const form = useForm({ name: "" });
const teams = computed(() =>
    props.teams.map((team) => ({
        ...team,
        owner_display: `${team.owner.name} · ${team.owner.email}`,
        owner_initials: team.owner.name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0])
            .join("")
            .toUpperCase(),
        summary_search: `${team.members_count} miembros ${team.roles_count} roles ${team.current_users_count} actuales`,
    })),
);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    owner_display: { value: null, matchMode: FilterMatchMode.CONTAINS },
    summary_search: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status_label: { value: "Activo", matchMode: FilterMatchMode.EQUALS },
});

const openCreate = () => {
    form.defaults({ name: "" });
    form.reset();
    form.clearErrors();
    visible.value = true;
};
const submit = () =>
    form.post(route("admin.teams.store"), {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
            toast.add({
                severity: "success",
                summary: "Equipo creado",
                life: 3000,
            });
        },
        onError: (errors) =>
            toast.add({
                severity: "error",
                summary: "No se pudo crear",
                detail: Object.values(errors)[0],
                life: 5000,
            }),
    });
const deactivate = (team) =>
    confirm.require({
        header: "Desactivar equipo",
        message: `¿Desactivar ${team.name}? Sus datos y membresías se conservarán.`,
        icon: "pi pi-exclamation-triangle",
        rejectLabel: "Cancelar",
        acceptLabel: "Desactivar",
        acceptClass: "p-button-danger",
        accept: () =>
            router.delete(route("admin.teams.destroy", team.id), {
                preserveScroll: true,
                onSuccess: () =>
                    toast.add({
                        severity: "success",
                        summary: "Equipo desactivado",
                        life: 3000,
                    }),
                onError: (errors) =>
                    toast.add({
                        severity: "error",
                        summary: "No se puede desactivar",
                        detail: errors.team ?? Object.values(errors)[0],
                        life: 6000,
                    }),
            }),
    });
const reactivate = (team) =>
    router.put(
        route("admin.teams.update", team.id),
        { name: team.name, activo: true },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.add({
                    severity: "success",
                    summary: "Equipo reactivado",
                    life: 3000,
                }),
        },
    );
</script>

<template>
    <AppLayout title="Equipos">
        <template #card-header><div class="flex flex-wrap items-center justify-between gap-3 p-4"><div class="flex items-center"><Button icon="pi pi-arrow-left" as="a" :href="route('admin.dashboard')" /><div class="ml-4"><h2 class="text-2xl font-bold">Equipos y departamentos</h2><p class="text-sm text-surface-500">Contextos organizativos y de permisos.</p></div></div><Button v-if="can('create-teams')" label="Crear equipo" icon="pi pi-plus" @click="openCreate" /></div></template>
        <template #card-content>
            <ConfirmDialog />
            <div>
            <DataTable v-model:filters="filters" :value="teams" :global-filter-fields="['name', 'owner_display', 'status_label', 'type_label', 'summary_search']" filter-display="row" paginator :rows="10" striped-rows scrollable responsive-layout="scroll" :table-style="{ tableLayout: 'fixed', minWidth: '64rem' }">
                <template #header><div class="flex flex-wrap justify-end gap-2"><Select v-model="filters.status_label.value" aria-label="Filtrar por estado" :options="['Activo', 'Inactivo']" placeholder="Todos los estados" show-clear class="w-44" /><IconField><InputIcon class="pi pi-search" /><InputText v-model="filters.global.value" placeholder="Buscar equipos" /></IconField></div></template>
                <Column field="name" header="Equipo" sortable :show-filter-menu="false" style="width: 29%">
                    <template #body="{ data }">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="min-w-0">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span
                                        class="size-2.5 shrink-0 rounded-full"
                                        :class="data.activo ? 'bg-green-500' : 'bg-surface-400'"
                                        :aria-label="`Equipo ${data.status_label.toLowerCase()}`"
                                    />
                                    <TruncatedText class="font-semibold" :value="data.name" max-width="14rem" />
                                </div>
                                <div class="mt-1 flex flex-nowrap"><Tag :value="data.type_label" severity="info" /></div>
                            </div>
                        </div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" class="w-full" placeholder="Nombre" @input="filterCallback()" /></template>
                </Column>
                <Column field="owner_display" header="Responsable" sortable :show-filter-menu="false" style="width: 23%"><template #body="{ data }"><div class="flex min-w-0 items-center gap-2"><Avatar :image="data.owner.profile_photo_path ? data.owner.profile_photo_url : undefined" :label="data.owner.profile_photo_path ? undefined : data.owner_initials" shape="circle" class="shrink-0" /><div class="min-w-0 max-w-48"><TruncatedText :value="data.owner.name" max-width="12rem" /><TruncatedText class="text-sm text-surface-500" :value="data.owner.email" max-width="12rem" /></div></div></template><template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" class="w-full" placeholder="Responsable" @input="filterCallback()" /></template></Column>
                <Column field="summary_search" header="Resumen" :show-filter-menu="false" style="width: 38%">
                    <template #body="{ data }"><div class="flex flex-nowrap gap-2 whitespace-nowrap"><Chip :label="`${data.members_count} miembros`" icon="pi pi-users" /><Chip :label="`${data.roles_count} roles`" icon="pi pi-key" /><Chip :label="`${data.current_users_count} actuales`" icon="pi pi-check-circle" /></div></template>
                    <template #filter="{ filterModel, filterCallback }"><InputText v-model="filterModel.value" class="w-full" placeholder="Resumen" @input="filterCallback()" /></template>
                </Column>
                <Column header="Acciones" :frozen="true" align-frozen="right" style="width: 10%" :pt="{ columnHeaderContent: { class: 'whitespace-nowrap' } }"><template #body="{ data }"><div class="flex flex-nowrap items-center gap-1 whitespace-nowrap"><Button :aria-label="`Abrir configuración de ${data.name}`" icon="pi pi-arrow-right" text as="a" :href="route('teams.show', data.id)" /><Button v-if="can('delete-teams') && data.activo" :aria-label="`Desactivar ${data.name}`" icon="pi pi-ban" severity="danger" text @click="deactivate(data)" /><Button v-if="can('update-teams') && !data.activo" :aria-label="`Reactivar ${data.name}`" icon="pi pi-refresh" severity="success" text @click="reactivate(data)" /></div></template></Column>
            </DataTable>
            </div>
            <Dialog v-model:visible="visible" header="Crear equipo institucional" modal :style="{ width: '460px' }"><form class="flex flex-col gap-4" @submit.prevent="submit"><Message severity="info" :closable="false">Los equipos creados aquí son institucionales. Después podrás administrar sus miembros y roles desde su configuración.</Message><div><label class="mb-1 block text-sm font-medium">Nombre *</label><InputText v-model="form.name" fluid :invalid="!!form.errors.name" /><small class="text-red-500">{{ form.errors.name }}</small></div><div class="flex justify-end gap-2"><Button label="Cancelar" severity="secondary" @click.prevent="visible = false" /><Button label="Crear" type="submit" :loading="form.processing" /></div></form></Dialog>
        </template>
    </AppLayout>
</template>
