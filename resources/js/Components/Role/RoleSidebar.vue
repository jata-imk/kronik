<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    roles: Array,
    selectedRole: Object,
});
const emit = defineEmits(["update:selectedRole", "add-role"]);

const localSelectedRole = ref(props.selectedRole);

watch(
    () => props.selectedRole,
    (val) => {
        localSelectedRole.value = val;
    },
);

watch(localSelectedRole, (val) => {
    emit("update:selectedRole", val);
});
</script>

<template>
    <div class="flex min-w-0 flex-col gap-2 border-b border-gray-200 xl:col-span-3 xl:border-b-0 xl:border-r">
        <div class="flex items-center justify-between p-4">
            <h3 class="text-xl font-medium text-gray-900 dark:text-gray-100 mr-2 xl:mr-0">Roles</h3>
            <Button icon="pi pi-plus" aria-label="Crear rol" @click="$emit('add-role')" />
        </div>
        <div class="min-w-0 w-full max-h-64 overflow-y-auto xl:max-h-none">
            <Listbox
                v-model="localSelectedRole"
                :options="roles"
                optionLabel="name"
                placeholder="Seleccione un rol"
                class="w-full xl:!border-none"
                :pt="{ listContainer: '!max-h-auto xl:!max-h-full' }"
            >
                <template #option="slotProps">
                    <div class="flex items-center">
                        <Avatar :label="slotProps.option.name.slice(0, 2).toUpperCase()" size="large" class="mr-2 !bg-primary-100 !text-primary-900" shape="circle" />
                        <span>{{ slotProps.option.name }}</span>
                    </div>
                </template>
            </Listbox>
        </div>
    </div>
</template>
