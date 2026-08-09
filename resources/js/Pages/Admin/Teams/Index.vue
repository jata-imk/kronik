<script setup>
import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";

const props = defineProps({ teams: { type: Array, default: () => [] } });
const toast = useToast();
const visible = ref(false);
const selected = ref(null);
const form = useForm({ name: "" });

const openCreate = () => {
    selected.value = null;
    form.defaults({ name: "" });
    form.reset();
    visible.value = true;
};
const openEdit = (team) => {
    selected.value = team;
    form.defaults({ name: team.name });
    form.reset();
    visible.value = true;
};
const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
            toast.add({ severity: "success", summary: selected.value ? "Equipo actualizado" : "Equipo creado", life: 3000 });
        },
    };
    selected.value
        ? form.put(route("admin.teams.update", selected.value.id), options)
        : form.post(route("admin.teams.store"), options);
};
const deactivate = (team) => {
    if (!confirm(`¿Desactivar el equipo ${team.name}?`)) return;
    router.delete(route("admin.teams.destroy", team.id), {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: "success", summary: "Equipo desactivado", life: 3000 }),
        onError: (errors) => toast.add({
            severity: "error",
            summary: "No se puede desactivar",
            detail: errors.team ?? "Cambia primero el equipo actual de sus usuarios.",
            life: 6000,
        }),
    });
};
const reactivate = (team) => {
    router.put(route("admin.teams.update", team.id), { name: team.name, activo: true }, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: "success", summary: "Equipo reactivado", life: 3000 }),
    });
};
</script>

<template>
    <AppLayout title="Equipos">
        <template #card-header>
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center">
                    <Button icon="pi pi-arrow-left" as="a" :href="route('admin.dashboard')" />
                    <h2 class="ml-4 text-2xl font-bold">Equipos y departamentos</h2>
                </div>
                <Button label="Crear equipo" icon="pi pi-plus" @click="openCreate" />
            </div>
        </template>
        <template #card-content>
            <DataTable :value="props.teams" paginator :rows="10">
                <Column field="name" header="Nombre" sortable />
                <Column field="owner.name" header="Responsable" />
                <Column field="members_count" header="Miembros" />
                <Column field="roles_count" header="Roles" />
                <Column field="current_users_count" header="Usuarios actuales" />
                <Column header="Estado">
                    <template #body="{ data }">
                        <Tag :value="data.activo ? 'Activo' : 'Inactivo'" :severity="data.activo ? 'success' : 'secondary'" />
                    </template>
                </Column>
                <Column header="Acciones">
                    <template #body="{ data }">
                        <Button v-if="data.activo" icon="pi pi-pencil" text @click="openEdit(data)" />
                        <Button v-if="data.activo" icon="pi pi-ban" severity="danger" text @click="deactivate(data)" />
                        <Button v-else icon="pi pi-refresh" severity="success" text v-tooltip.top="'Reactivar'" @click="reactivate(data)" />
                    </template>
                </Column>
            </DataTable>

            <Dialog v-model:visible="visible" :header="selected ? 'Editar equipo' : 'Crear equipo'" modal :style="{ width: '460px' }">
                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Nombre *</label>
                        <InputText v-model="form.name" fluid />
                        <small class="text-red-500">{{ form.errors.name }}</small>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button label="Cancelar" severity="secondary" @click.prevent="visible = false" />
                        <Button label="Guardar" type="submit" :loading="form.processing" />
                    </div>
                </form>
            </Dialog>
        </template>
    </AppLayout>
</template>
