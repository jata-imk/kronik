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
    <div class="xl:col-span-3 flex flex-row gap-4 xl:flex-col pr-4 xl:pr-0 h-full border-b xl:border-b-0 xl:border-r border-gray-200">
        <div class="flex items-start xl:justify-between p-4">
            <h3 class="text-xl font-medium text-gray-900 dark:text-gray-100 mr-2 xl:mr-0">Roles</h3>
            <Button icon="pi pi-plus" class="p-button-success" @click="$emit('add-role')" />
        </div>
        <div class="mt-2 min-h-fit xl:min-h-full w-full xl:w-auto">
            <Listbox
                v-model="localSelectedRole"
                :options="roles"
                optionLabel="name"
                placeholder="Seleccione un rol"
                class="xl:!border-none"
                :pt="{ listContainer: '!max-h-auto xl:!max-h-full' }"
            >
                <template #option="slotProps">
                    <div class="flex items-center">
                        <Avatar :label="slotProps.option.name.slice(0, 2).toUpperCase()" size="large" class="mr-2" style="background-color: #dee9fc; color: #1a2551" shape="circle" />
                        <span>{{ slotProps.option.name }}</span>
                    </div>
                </template>
            </Listbox>
        </div>
    </div>
</template>