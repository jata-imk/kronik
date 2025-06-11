<script setup>
import { ref, computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import CreateRoleModal from "./CreateRoleModal.vue";

const page = usePage();

const formRolePermissions = useForm({
    name: "",
    permissions: [],
});

const roles = ref(page.props.roles);
const modules = ref(page.props.modules);
const permissions = ref(page.props.permissions);

const selectedRole = ref(null);

watch(
    () => page.props.roles,
    () => {
        roles.value = page.props.roles;
    },
);

watch(
    () => [selectedRole.value],
    () => {
        formRolePermissions.name = selectedRole.value?.name;
        formRolePermissions.permissions = selectedRole.value?.permissions.map(
            (p) => p.id,
        );
    },
);

const permissionsHaveChanged = computed({
    get() {
        const setUno = new Set(formRolePermissions.permissions);
        const setDos = new Set(
            selectedRole.value?.permissions.map((p) => p.id),
        );
        return (
            setUno.difference(setDos).size > 0 ||
            setDos.difference(setUno).size > 0
        );
    },
    set(value) {
        formRolePermissions.permissions = value;
        selectedRole.value = roles.value.find(
            (role) => role.id === selectedRole.value.id,
        );
    },
});

const selectedRoleAvatarLabel = computed(
    () => selectedRole.value?.name?.slice(0, 2).toUpperCase() || "",
);

const createRoleModalIsOpen = ref(false);

const closeCreateRoleModal = () => {
    createRoleModalIsOpen.value = false;
};

const permissionsByModule = ref({});
watch(
    () => formRolePermissions.permissions,
    () => {
        const objModules = {};
        for (const permissionId of formRolePermissions.permissions) {
            const permission = permissions.value.find(
                (p) => p.id === permissionId,
            );
            if (permission) {
                if (!objModules[permission.module.name]) {
                    objModules[permission.module.name] = [];
                }
                objModules[permission.module.name].push(permission);
            }
        }

        permissionsByModule.value = objModules;
    },
);

const submit = () => {
    formRolePermissions.put(
        route("admin.roles.update", selectedRole.value.id),
        {
            only: ["roles"],
            onSuccess: () => {
                createRoleModalIsOpen.value = false;
                permissionsHaveChanged.value = formRolePermissions.permissions;
            },
        },
    );
};
</script>

<template>
    <AppLayout title="Roles y permisos" :pt="{ 'card-content-body': '!p-0' }">
        <template #card-header>
            <div class="flex items-center p-4">
                <Button icon="pi pi-arrow-left"  as="a" :href="route('admin.dashboard')"></Button>
                <h2 class="text-2xl font-bold ml-4">Configuraciones de roles y permisos</h2>
            </div>
        </template>

        <template #card-content>
            <div class="grid grid-cols-12 min-h-screen">
                <div class="col-span-3 h-full border-r border-gray-200">
                    <div class="flex justify-between p-4">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-gray-100">Roles</h3>

                        <Button icon="pi pi-plus" class="p-button-success" @click="createRoleModalIsOpen = true"></Button>
                    </div>

                    <div class="mt-2 min-h-full">
                        <Listbox v-model="selectedRole" :options="roles" optionLabel="name" placeholder="Seleccione un rol" class="!border-none" :pt="{listContainer: '!max-h-full'}">
                            <template #option="slotProps">
                                <div class="flex items-center">
                                    <Avatar :label="slotProps.option.name.slice(0, 2).toUpperCase()" size="large" class="mr-2" style="background-color: #dee9fc; color: #1a2551" shape="circle" />
                                    <span>{{ slotProps.option.name }}</span>
                                </div>
                            </template>
                        </Listbox>
                    </div>
                </div>

                <form @submit.prevent="submit" class="col-span-9 p-2 pt-24 h-full relative grid grid-cols-12">
                    <div class="flex justify-between mb-4 bg-gray-300 dark:bg-gray-800 p-4 rounded-xl rounded-tl-none rounded-tr-none absolute top-0 left-[20px] right-[20px]">
                        <div class="flex gap-4 items-center">
                            <span class="pi pi-fw pi-key !text-2xl"></span>
                            <div>
                                <span class="text-sm text-gray-500 font-bold">Nombre del Rol</span>
                                <p class="text-md font-bold" :class="{ 'italic': !selectedRole }">{{ selectedRole?.name || "Sin rol seleccionado" }}</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <Button icon="pi pi-fw pi-ellipsis-v" severity="secondary"></Button>
                            <Button severity="primary" :disabled="!permissionsHaveChanged" type="submit">Actualizar</Button>
                        </div>
                    </div>

                    <div v-if="selectedRole" class="col-span-8">
                        <div v-for="module in modules" :key="module.id" class="flex flex-col gap-4 p-4">
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-500 dark:text-gray-400 font-bold min-w-fit">{{ module.name }}</span>
                                <span class="w-full border-b border-gray-200 block dark:border-gray-700 mt-2"></span>
                            </div>
                            <div>
                                <Listbox
                                    v-model="formRolePermissions.permissions"
                                    :options="permissions.filter((p) => p.module_id === module.id)"
                                    optionLabel="name"
                                    optionValue="id"
                                    multiple
                                    checkmark
                                    fluid />
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedRole" class="col-span-4 p-4 border-l border-gray-200">
                        <div>
                            <p class="text text-gray-500 font-bold">Detalles del Rol</p>

                            <div class="flex items-center gap-4 py-4">
                                <Avatar :label="selectedRoleAvatarLabel" size="large" class="mr-2" style="background-color: #dee9fc; color: #1a2551" shape="circle" />
                                <span class="font-bold">{{ selectedRole.name }}</span>
                            </div>

                            <div class="mt-12">
                                <span class="text text-gray-500 font-bold">Descripción general del rol</span>

                                <div class="mt-4">
                                    <div v-for="(permissions, moduleName) in permissionsByModule" class="flex items-center gap-4 bg-gray-100 dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-200 rounded-xl rounded-bl-none rounded-br-none">
                                        <span class="pi pi-fw pi-key !text-2xl"></span>
                                        <div>
                                            <p class="text-sm font-bold text-primary-500">{{ moduleName.split("-").map((s) => s.charAt(0).toUpperCase() + s.substring(1)).join(" ") }}</p>
                                            <div class="flex flex-wrap gap-1 mt-2">
                                                <Tag v-for="permission in permissions" :key="permission.id" severity="primary" :value="permission.name.replace(moduleName, '')" rounded></Tag>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="!Object.keys(permissionsByModule).length" class="flex items-center gap-4 bg-gray-100 dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-200 rounded-xl rounded-bl-none rounded-br-none">
                                        <span class="pi pi-fw pi-key !text-2xl"></span>
                                        <div>
                                            <p class="text-sm font-bold text-secondary-500">Sin permisos</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <CreateRoleModal v-model:visible="createRoleModalIsOpen" @close="closeCreateRoleModal" />
        </template>
    </AppLayout>
</template>
