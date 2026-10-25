<script setup>
import { useForm } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import ContextHelp from "@/Components/Products/FinancialFieldHelp.vue";

const props = defineProps({ team: Object, capabilities: Object, candidates: Array });
const confirm = useConfirm();
const transfer = useForm({ responsible_id: null, confirmation: false });
const state = useForm({ name: "", activo: true });
const confirmTransfer = () => confirm.require({
    header: "Transferir responsable", message: "El nuevo responsable será informativo. Los permisos de ambos integrantes se conservarán.",
    acceptLabel: "Transferir", rejectLabel: "Cancelar", accept: () => {
        transfer.confirmation = true;
        transfer.put(route("teams.responsible.update", props.team.id), { preserveScroll: true, onSuccess: () => transfer.reset() });
    },
});
const changeState = () => confirm.require({
    header: props.team.activo ? "Desactivar equipo" : "Reactivar equipo",
    message: props.team.activo ? "Antes de desactivarlo, todos los usuarios deben tener otro equipo actual. Las membresías y el historial se conservan." : "El equipo volverá a estar disponible para sus integrantes.",
    acceptLabel: "Confirmar", rejectLabel: "Cancelar", accept: () => {
        if (props.team.activo) state.delete(route("admin.teams.destroy", props.team.id), { preserveScroll: true });
        else {
            state.name = props.team.name;
            state.put(route("admin.teams.update", props.team.id), { preserveScroll: true });
        }
    },
});
</script>

<template>
    <Card class="mt-6">
        <template #title>Responsable y estado</template>
        <template #content>
            <div class="flex items-center gap-2">
                <p>El responsable identifica a la persona de contacto del equipo.</p>
                <ContextHelp title="Responsable del equipo" description="Ser responsable no concede permisos. Las facultades dependen de los roles del equipo. Antes de quitar al responsable del equipo debes transferir esta responsabilidad." />
            </div>
            <div v-if="capabilities['update-teams']" class="mt-4 flex flex-wrap items-end gap-3">
                <div><label for="responsible-id" class="mb-2 block">Nuevo responsable</label><Select input-id="responsible-id" aria-label="Nuevo responsable" v-model="transfer.responsible_id" :options="candidates" option-label="name" option-value="id" placeholder="Integrante activo" class="w-64 max-w-full" /></div>
                <Button label="Transferir responsable" :disabled="!transfer.responsible_id || transfer.processing" @click="confirmTransfer" />
            </div>
            <Message v-for="(error, key) in transfer.errors" :key="key" severity="error" class="mt-3">{{ error }}</Message>
            <Button v-if="team.activo ? capabilities['delete-teams'] : capabilities['update-teams']" :label="team.activo ? 'Desactivar equipo' : 'Reactivar equipo'" :severity="team.activo ? 'danger' : 'secondary'" :disabled="state.processing" class="mt-4" @click="changeState" />
            <Message v-for="(error, key) in state.errors" :key="key" severity="error" class="mt-3">{{ error }}</Message>
        </template>
    </Card>
</template>
