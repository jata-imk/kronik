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
    () => [selectedRole.value],
    () => {
        formRolePermissions.name = selectedRole.value?.name;
        formRolePermissions.permissions = selectedRole.value?.permissions.map(
            (p) => p.id,
        );
    },
);

const permissionsHaveChanged = computed(() => {
    const setUno = new Set(formRolePermissions.permissions);
    const setDos = new Set(selectedRole.value?.permissions.map((p) => p.id));
    return (
        setUno.difference(setDos).size > 0 || setDos.difference(setUno).size > 0
    );
});

const selectedRoleAvatarLabel = computed(
    () => selectedRole.value?.name?.slice(0, 2).toUpperCase() || "",
);

const createRoleModalIsOpen = ref(false);

const closeCreateRoleModal = () => {
    createRoleModalIsOpen.value = false;
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
                <div class="col-span-2 h-full border-r border-gray-200">
                    <div class="flex justify-between p-4">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-gray-100">Roles</h3>

                        <Button icon="pi pi-plus" class="p-button-success" @click="createRoleModalIsOpen = true"></Button>
                    </div>

                    <div class="mt-2">
                        <Listbox v-model="selectedRole" :options="roles" optionLabel="name" placeholder="Seleccione un rol" class="!border-none">
                            <template #option="slotProps">
                                <div class="flex items-center">
                                    <Avatar :label="slotProps.option.name.slice(0, 2).toUpperCase()" size="large" class="mr-2" style="background-color: #dee9fc; color: #1a2551" shape="circle" />
                                    <span>{{ slotProps.option.name }}</span>
                                </div>
                            </template>
                        </Listbox>
                    </div>
                </div>

                <div class="col-span-10 p-2 pt-24 h-full relative grid grid-cols-12">
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
                            <Button severity="primary" :disabled="!permissionsHaveChanged">Actualizar</Button>
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
                                    <div class="flex items-center gap-4 bg-gray-100 dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-200 rounded-xl rounded-bl-none rounded-br-none">
                                        <span class="pi pi-fw pi-key !text-2xl"></span>
                                        <div>
                                            <p class="text-sm font-bold text-primary-500">Clientes</p>
                                            <div class="flex gap-2 mt-2">
                                                <Tag value="Crear" rounded></Tag>
                                                <Tag value="Editar" rounded></Tag>
                                                <Tag value="Borrar" rounded></Tag>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4 bg-gray-100 dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-200">
                                        <span class="pi pi-fw pi-key !text-2xl"></span>
                                        <div>
                                            <p class="text-sm font-bold text-primary-500">Historial crediticio</p>
                                            <div class="flex gap-2 mt-2">
                                                <Tag value="Consultar" rounded></Tag>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-4 bg-gray-100 dark:bg-gray-800 p-4 border border-gray-200 dark:border-gray-200 rounded-xl rounded-tl-none rounded-tr-none">
                                        <span class="pi pi-fw pi-key !text-2xl"></span>
                                        <div>
                                            <p class="text-sm font-bold text-primary-500">Documentos</p>
                                            <div class="flex gap-2 mt-2">
                                                <Tag value="Subir" rounded></Tag>
                                                <Tag value="Descargar" rounded></Tag>
                                                <Tag value="Editar" rounded></Tag>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <CreateRoleModal v-model:visible="createRoleModalIsOpen" @close="closeCreateRoleModal" />
        </template>
    </AppLayout>
</template>
