import { router } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

export function useUserActions() {
    const confirm = useConfirm();
    const toast = useToast();
    const options = (message) => ({
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: "success", summary: message, life: 3000 }),
        onError: (errors) => toast.add({ severity: "error", summary: "No se pudo completar la acción", detail: Object.values(errors).flat()[0] ?? "Revisa los permisos de la cuenta.", life: 6000 }),
    });
    const resendInvitation = (user) => router.post(route("admin.users.invitation.resend", user.id), {}, options("Invitación reenviada"));
    const deactivate = (user) => confirm.require({
        header: "Desactivar cuenta", message: `¿Desactivar la cuenta de ${user.name}? Esto revoca su acceso en todos los equipos. Su historial se conserva.`,
        acceptLabel: "Desactivar cuenta", rejectLabel: "Cancelar", acceptClass: "p-button-danger",
        accept: () => router.delete(route("admin.users.destroy", user.id), options("Cuenta desactivada")),
    });
    const reactivate = (user) => confirm.require({
        header: "Reactivar cuenta", message: `¿Reactivar la cuenta de ${user.name}? Volverá a acceder con sus asignaciones vigentes.`,
        acceptLabel: "Reactivar cuenta", rejectLabel: "Cancelar",
        accept: () => router.put(route("admin.users.update", user.id), {
            name: user.name, email: user.email, status: "active", is_super_admin: user.is_super_admin,
            current_team_id: user.current_team_id, team_roles: user.team_roles,
            sucursal_ids: user.sucursal_ids, sucursal_principal_id: user.sucursal_principal_id,
        }, options("Cuenta reactivada")),
    });
    const removeFromTeam = (user, team) => confirm.require({
        header: "Quitar del equipo", message: `¿Quitar a ${user.name} del equipo ${team.name}? Solo se retirará esta membresía y sus permisos en este equipo. Su cuenta y los demás equipos se conservan. Si es su equipo actual o único, primero debes reasignarlo.`,
        acceptLabel: "Quitar del equipo", rejectLabel: "Cancelar", acceptClass: "p-button-danger",
        accept: () => router.delete(route("team-members.destroy", [team.id, user.id]), { ...options("Integrante retirado del equipo"), errorBag: "removeTeamMember" }),
    });
    return { resendInvitation, deactivate, reactivate, removeFromTeam };
}
