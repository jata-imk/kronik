<script setup>
import { computed, reactive, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { FilterMatchMode } from "@primevue/core/api";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import UserForm from "./Form.vue";

const page = usePage();
const users = computed(() => page.props.users);
const roles = ref(page.props.roles);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    id: { value: null, matchMode: FilterMatchMode.EQUALS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const selectedUser = ref(null);
const showForm = ref(false);

const handleClickConfig = (data) => {
    selectedUser.value = data;
    showForm.value = true;
};
</script>

<template>
    <AppLayout title="Usuarios" :pt="{ 'card-content-body': '!p-0' }">
        <template #card-header>
            <div class="flex items-center p-4">
                <Button icon="pi pi-arrow-left"  as="a" :href="route('admin.dashboard')"></Button>
                <h2 class="text-2xl font-bold ml-4">Configuraciones de usuarios</h2>
            </div>
        </template>

        <template #card-content>
            <DataTable :value="users" v-model:filters="filters" filter-display="row" :global-filter-fields="['name', 'email']">
                <Column field="id" header="id"></Column>
                <Column field="name" header="Nombre" sortable>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter"
                            placeholder="Buscar por nombre" />
                    </template>
                </Column>
                <Column field="email" header="Correo" sortable>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-model="filterModel.value" type="text" @input="filterCallback()" class="p-column-filter"
                            placeholder="Buscar por email" />
                    </template>
                </Column>
                <Column class="w-24 !text-end">
                    <template #body="{ data }">
                        <Button icon="pi pi-cog" severity="secondary" rounded @click="handleClickConfig(data)"></Button>
                    </template>
                </Column>
            </DataTable>

            <UserForm v-if="showForm" :user="selectedUser" :roles="roles"
                @close="showForm = false" />
        </template>
    </AppLayout>
</template>
