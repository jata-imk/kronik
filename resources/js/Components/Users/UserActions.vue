<script setup>
import { usePage } from "@inertiajs/vue3";
import { useUserActions } from "@/Composables/useUserActions";

defineProps({ user: Object, team: Object, canRemove: Boolean });
defineEmits(["edit"]);
const page = usePage();
const can = (permission) => page.props.auth.is_super_admin || page.props.auth.permissions?.[permission];
const { resendInvitation, deactivate, reactivate, removeFromTeam } = useUserActions();
</script>

<template>
    <div class="flex flex-wrap gap-1">
        <Button v-if="can('update-users')" :aria-label="`Editar ${user.name}`" icon="pi pi-pencil" text @click="$emit('edit')" />
        <Button v-if="can('create-users') && user.status === 'pending'" :aria-label="`Reenviar invitación a ${user.name}`" icon="pi pi-send" text @click="resendInvitation(user)" />
        <Button v-if="can('delete-users') && user.status === 'active' && user.id !== page.props.auth.user.id" :aria-label="`Desactivar cuenta de ${user.name}`" icon="pi pi-ban" severity="danger" text @click="deactivate(user)" />
        <Button v-if="can('update-users') && user.status === 'inactive'" :aria-label="`Reactivar cuenta de ${user.name}`" icon="pi pi-refresh" severity="success" text @click="reactivate(user)" />
        <Button v-if="team && canRemove && !user.is_owner && user.id !== page.props.auth.user.id" :aria-label="`Quitar a ${user.name} del equipo`" icon="pi pi-user-minus" severity="warn" text @click="removeFromTeam(user, team)" />
    </div>
</template>
