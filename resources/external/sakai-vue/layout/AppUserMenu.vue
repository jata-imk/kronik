<script setup>
import { markRaw, ref } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import TeamItemLink from "./UserAndTeamsConfigurator/TeamItemLink.vue";

const toggleTeamsMenu = (event) => {
    teamMenu.value.toggle(event);
};
const toggleUserMenu = (event) => {
    userMenu.value.toggle(event);
};

const switchToTeam = (team) => {
    router.put(
        route("current-team.update"),
        {
            team_id: team.id,
        },
        {
            preserveState: false,
        },
    );
};

const page = usePage();
const teamMenu = ref();
const teamMenuItems = ref([
    {
        label: "Manejo de equipos",
        items: [
            {
                label: "Configurar equipo",
                icon: "pi pi-cog",
                shortcut: "⌘+T",
                route: route("teams.show", page.props.auth.user.current_team),
            },
        ],
    },
]);

if (page.props.auth.permissions["create-teams"] ?? false) {
    teamMenuItems.value[0].items.push({
        label: "Crear nuevo equipo",
        icon: "pi pi-plus",
        route: route("teams.create"),
    });
}

if (page.props.auth.user.all_teams.length > 1) {
    teamMenuItems.value.push({
        separator: true,
    });

    if (page.props.auth.user.all_teams.length > 1) {
        teamMenuItems.value.push({
            label: "Cambiar de equipo",
            items: page.props.auth.user.all_teams.map((team) => {
                return {
                    label: team.name,
                    customElements: [
                        {
                            component: markRaw(TeamItemLink),
                            props: {
                                team: team,
                                switchToTeam: switchToTeam,
                            },
                        },
                    ],
                };
            }),
        });
    }
}

const userMenu = ref();
const userMenuItems = ref([
    {
        label: "Manejo de cuenta",
        items: [
            {
                label: "Ver perfil",
                icon: "pi pi-user",
                shortcut: "⌘+P",
                route: route("profile.show"),
            },
            page.props.jetstream.hasApiFeatures && {
                label: "API Tokens",
                icon: "pi pi-key",
                route: route("api-tokens.index"),
            },
        ],
    },
    {
        separator: true,
    },
    {
        items: [
            {
                label: "Cerrar sesión",
                icon: "pi pi-sign-out",
                shortcut: "⌘+O",
                route: route("logout"),
                as: "button",
                method: "post",
            },
        ],
    },
]);
</script>


<template>
    <!-- Teams Dropdown -->
    <div v-if="$page.props.jetstream.hasTeamFeatures" class="layout-menu-desktop-prime-vue">
        <button type="button"
            class="inline-flex items-center px-2 py-3 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-300 bg-surface-card hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-600 active:bg-gray-50 dark:active:bg-gray-600 transition ease-in-out duration-150"
            @click="toggleTeamsMenu">
            {{ $page.props.auth.user.current_team.name }}

            <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
            </svg>
        </button>

        <Menu ref="teamMenu" id="team_menu" :popup="true" :model="teamMenuItems" class="w-full md:w-60">
            <template #submenulabel="{ item }">
                <span class="text-primary font-bold">{{ item.label }}</span>
            </template>
            <template #item="{ item, props }">
                <component v-if="item.customElements" :is="item.customElements[0].component" v-bind="item.customElements[0].props" />
                
                <Link v-else :href="item.route" :as="item.as" :method="item.method" v-ripple class="flex items-center w-full" v-bind="props.action">
                <span :class="item.icon" />
                <span>{{ item.label }}</span>
                <Badge v-if="item.badge" class="ml-auto" :value="item.badge" />
                <span v-if="item.shortcut"
                    class="ml-auto border border-surface rounded bg-emphasis text-muted-color text-xs p-1">{{
                    item.shortcut }}</span>
                </Link>
            </template>                    
        </Menu>
    </div>

    <div class="layout-menu-desktop-prime-vue">
        <button type="button"
            class="inline-flex items-center px-2 py-3 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-300 bg-surface-card hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-600 active:bg-gray-50 dark:active:bg-gray-600 transition ease-in-out duration-150"
            @click="toggleUserMenu" aria-haspopup="true" aria-controls="overlay_menu">
            {{ $page.props.auth.user.name }}
            <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <Menu ref="userMenu" id="overlay_menu" :popup="true" :model="userMenuItems" class="w-full md:w-60">
            <template #submenulabel="{ item }">
                <span class="text-primary font-bold">{{ item.label }}</span>
            </template>
            <template #item="{ item, props }">
                <Link :href="item.route" :as="item.as" :method="item.method" v-ripple class="flex items-center w-full" v-bind="props.action">
                <span :class="item.icon" />
                <span>{{ item.label }}</span>
                <Badge v-if="item.badge" class="ml-auto" :value="item.badge" />
                <span v-if="item.shortcut"
                    class="ml-auto border border-surface rounded bg-emphasis text-muted-color text-xs p-1">{{
                    item.shortcut }}</span>
                </Link>
            </template>
        </Menu>
    </div>

    <div class="layout-menu-prime-vue">
        <Menu :model="teamMenuItems" style="border: none; border-radius: 0;">
        <template #submenulabel="{ item }">
            <span class="text-primary font-bold">{{ item.label }}</span>
        </template>
        <template #item="{ item, props }">
            <component v-if="item.customElements" :is="item.customElements[0].component" v-bind="item.customElements[0].props" />
            
            <Link v-else :href="item.route" :as="item.as" :method="item.method" v-ripple class="flex items-center w-full" v-bind="props.action">
            <span :class="item.icon" />
            <span>{{ item.label }}</span>
            <Badge v-if="item.badge" class="ml-auto" :value="item.badge" />
            <span v-if="item.shortcut"
                class="ml-auto border border-surface rounded bg-emphasis text-muted-color text-xs p-1">{{
                item.shortcut }}</span>
            </Link>
        </template>                    
    </Menu>

    <Divider />

    <Menu :model="userMenuItems" style="border: none; border-radius: 0;">
        <template #submenulabel="{ item }">
            <span class="text-primary font-bold">{{ item.label }}</span>
        </template>
        <template #item="{ item, props }">
            <Link :href="item.route" :as="item.as" :method="item.method" v-ripple class="flex items-center w-full" v-bind="props.action">
                <span :class="item.icon" />
                <span>{{ item.label }}</span>
                <Badge v-if="item.badge" class="ml-auto" :value="item.badge" />
                <span v-if="item.shortcut"
                    class="ml-auto border border-surface rounded bg-emphasis text-muted-color text-xs p-1">{{
                    item.shortcut }}</span>
            </Link>
        </template>
    </Menu>
    </div>
</template>
