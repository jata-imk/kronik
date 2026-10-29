<script setup>
import { Link } from "@inertiajs/vue3";
import CollectionSummary from "@/Components/DataTable/CollectionSummary.vue";

defineProps({ teamId: { type: Number, required: true }, roles: { type: Array, default: () => [] } });
</script>

<template>
    <ul class="team-role-grid grid gap-4" aria-label="Roles del equipo">
        <li class="aspect-square min-w-0">
            <Link
                :href="route('admin.roles.index', { team: teamId })"
                class="team-role-manage k-module-header h-full w-full transition hover:brightness-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4"
                aria-label="Administrar roles"
                :aria-describedby="`team-roles-description-${teamId}`"
            >
                <i class="pi pi-cog relative z-10 text-2xl" aria-hidden="true" />
                <h3 class="relative z-10 text-lg font-semibold">Administrar roles</h3>
                <p :id="`team-roles-description-${teamId}`" class="relative z-10 text-sm">Consulta la configuración completa de permisos y todos los roles de este equipo.</p>
                <i class="pi pi-arrow-right relative z-10 mt-auto" aria-hidden="true" />
            </Link>
        </li>
        <li v-for="role in roles" :key="role.id" class="aspect-square min-w-0 rounded-2xl border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900">
            <div class="flex h-full min-w-0 flex-col gap-3">
                <i class="pi pi-key text-2xl text-primary" aria-hidden="true" />
                <h3 class="line-clamp-3 break-words text-lg font-semibold" :title="role.name">{{ role.name }}</h3>
                <p class="text-sm text-surface-500">{{ role.members_count }} {{ role.members_count === 1 ? 'persona activa' : 'personas activas' }}</p>
                <CollectionSummary class="mt-auto" :items="role.modules" max-width="100%" empty-label="Sin módulos asignados" />
            </div>
        </li>
    </ul>
    <p v-if="!roles.length" class="mt-4 text-sm text-surface-500">No hay roles con integrantes activos en este equipo.</p>
</template>

<style scoped>
.team-role-grid {
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 15rem), 1fr));
}

.team-role-grid .team-role-manage {
    flex-direction: column;
    flex-wrap: nowrap;
    align-items: flex-start;
    justify-content: flex-start;
    gap: 0.75rem;
    padding: 1.25rem;
    border: 1px solid var(--p-primary-color);
    border-radius: 1rem;
    background: var(--p-primary-color);
    color: var(--p-primary-contrast-color);
}
</style>
