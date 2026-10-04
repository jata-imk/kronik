<script setup>
defineProps({
    role: { type: Object, required: true },
    members: { type: Array, default: () => [] },
});
</script>

<template>
    <section class="min-w-0 p-4" :aria-label="`Miembros del rol ${role.name}`">
        <h3 class="text-lg font-semibold">Miembros de {{ role.name }}</h3>
        <p class="mt-1 text-sm text-surface-500">Usuarios con este rol en el equipo actual.</p>
        <p v-if="!members.length" class="mt-5 rounded-xl border border-dashed border-surface-300 p-5 text-sm text-surface-600 dark:border-surface-700 dark:text-surface-300">
            Aún no hay usuarios asignados a este rol.
        </p>
        <ul v-else class="mt-5 divide-y divide-surface-200 dark:divide-surface-700">
            <li v-for="member in members" :key="member.id" class="flex min-w-0 items-center gap-3 py-3">
                <Avatar :label="member.name.slice(0, 2).toUpperCase()" shape="circle" class="!bg-primary-100 !text-primary-800" />
                <p class="min-w-0 truncate font-medium">{{ member.name }}</p>
            </li>
        </ul>
    </section>
</template>
