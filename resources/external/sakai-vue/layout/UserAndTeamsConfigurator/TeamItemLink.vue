<script setup>
const props = defineProps({
    team: {
        type: Object,
        required: true
    },
    switchToTeam: {
        type: Function,
        required: true
    }
});
</script>

<template>
    <form @submit.prevent="props.switchToTeam(props.team)" class="p-menu-item-link">
        <button type="submit">
            <div class="flex items-center">
                <svg v-if="props.team.id == $page.props.auth.user.current_team_id" class="me-2 size-5 text-green-400"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>

                <div>{{ props.team.name }}</div>
            </div>
        </button>
    </form>
</template>
